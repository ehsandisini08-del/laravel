<?php

namespace App\Http\Controllers;

use App\Enums\RepairTaskStatus;
use App\Http\Requests\CompleteRepairTaskRequest;
use App\Http\Requests\StoreRepairTaskCommentRequest;
use App\Http\Requests\StoreRepairTaskRequest;
use App\Models\Customer;
use App\Models\RepairTask;
use App\Models\RepairTaskComment;
use App\Models\User;
use App\Notifications\NewRepairTaskNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RepairTaskController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->canManageTeknisiTasks()) {
            $tasks = RepairTask::with(['customer', 'assignedBy', 'takenBy'])
                ->latest()
                ->paginate(20);

            $stats = [
                'baru' => RepairTask::where('status', RepairTaskStatus::Baru)->count(),
                'proses' => RepairTask::where('status', RepairTaskStatus::Proses)->count(),
                'selesai_hari_ini' => RepairTask::where('status', RepairTaskStatus::Selesai)
                    ->whereDate('completed_at', today())
                    ->count(),
            ];
        } else {
            $tasks = RepairTask::with(['customer', 'assignedBy', 'takenBy'])
                ->where(function ($query) use ($user) {
                    $query->where('status', RepairTaskStatus::Baru)
                        ->orWhere('taken_by_user_id', $user->id);
                })
                ->latest()
                ->paginate(20);

            $stats = [
                'tersedia' => RepairTask::where('status', RepairTaskStatus::Baru)->count(),
                'tugas_saya' => RepairTask::where('status', RepairTaskStatus::Proses)
                    ->where('taken_by_user_id', $user->id)
                    ->count(),
                'selesai_bulan_ini' => RepairTask::where('status', RepairTaskStatus::Selesai)
                    ->where('taken_by_user_id', $user->id)
                    ->whereMonth('completed_at', now()->month)
                    ->count(),
            ];
        }

        return view('teknisi.tugas-perbaikan', compact('tasks', 'stats'));
    }

    public function create(): View
    {
        if (! auth()->user()->canManageTeknisiTasks()) {
            abort(403, 'Anda tidak memiliki akses untuk membuat tugas.');
        }

        $customers = Customer::with(['area', 'package'])
            ->orderBy('name')
            ->get();

        return view('teknisi.buat-tugas', compact('customers'));
    }

    public function store(StoreRepairTaskRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($request->customer_id);

            // Convert all values to proper types explicitly
            // This handles ANY type from database (string, numeric, null, decimal object, etc.)
            $latitude = $customer->latitude;
            $longitude = $customer->longitude;

            // Convert to string or null (SQLite accepts both)
            if ($latitude !== null) {
                $latitude = is_string($latitude) ? $latitude : (string) $latitude;
            }
            if ($longitude !== null) {
                $longitude = is_string($longitude) ? $longitude : (string) $longitude;
            }

            // Insert directly using DB::table to completely bypass Eloquent casting
            $taskId = DB::table('repair_tasks')->insertGetId([
                'customer_id' => (int) $customer->id,
                'assigned_by_user_id' => (int) auth()->id(),
                'nama_customer' => (string) $customer->name,
                'alamat' => (string) $customer->address,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'no_telp' => (string) $customer->phone,
                'keterangan' => (string) $request->keterangan,
                'status' => 'baru',
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);

            // Load the created task
            $task = RepairTask::find($taskId);

            RepairTaskComment::create([
                'repair_task_id' => $task->id,
                'user_id' => auth()->id(),
                'comment' => 'Tugas dibuat oleh '.auth()->user()->name,
                'is_system' => true,
            ]);

            $teknisiUsers = User::where('role', User::ROLE_TEKNISI)->get();
            Notification::send($teknisiUsers, new NewRepairTaskNotification($task));

            DB::commit();

            return redirect()->route('teknisi.repair-tasks.index')
                ->with('success', 'Tugas perbaikan berhasil dibuat dan notifikasi telah dikirim ke teknisi.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal membuat tugas: '.$e->getMessage());
        }
    }

    public function show(RepairTask $task): View
    {
        if (! auth()->user()->canAccessTeknisi()) {
            abort(403);
        }

        $task->load(['customer', 'assignedBy', 'takenBy', 'comments.user']);

        return view('teknisi.repair-tasks.show', compact('task'));
    }

    public function take(RepairTask $task): RedirectResponse
    {
        $user = auth()->user();

        if (! $task->canBeTakenBy($user)) {
            return back()->with('error', 'Tugas ini tidak dapat diambil.');
        }

        try {
            DB::beginTransaction();

            $task->update([
                'taken_by_user_id' => $user->id,
                'status' => RepairTaskStatus::Proses,
                'taken_at' => now(),
            ]);

            RepairTaskComment::create([
                'repair_task_id' => $task->id,
                'user_id' => $user->id,
                'comment' => 'Tugas diambil oleh '.$user->name,
                'is_system' => true,
            ]);

            DB::commit();

            return redirect()->route('teknisi.repair-tasks.show', $task)
                ->with('success', 'Tugas berhasil diambil. Silakan kerjakan tugas ini.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal mengambil tugas: '.$e->getMessage());
        }
    }

    public function complete(CompleteRepairTaskRequest $request, RepairTask $task): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $data = [
                'keterangan_teknisi' => $request->keterangan_teknisi,
                'status' => RepairTaskStatus::Selesai,
                'completed_at' => now(),
            ];

            if ($request->hasFile('foto_bukti')) {
                $path = $request->file('foto_bukti')->store(
                    'repair-tasks/'.date('Y/m'),
                    'public'
                );
                $data['foto_bukti'] = $path;
            }

            $task->update($data);

            RepairTaskComment::create([
                'repair_task_id' => $task->id,
                'user_id' => auth()->id(),
                'comment' => 'Tugas diselesaikan oleh '.auth()->user()->name,
                'is_system' => true,
            ]);

            DB::commit();

            return redirect()->route('teknisi.repair-tasks.index')
                ->with('success', 'Tugas berhasil diselesaikan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyelesaikan tugas: '.$e->getMessage());
        }
    }

    public function storeComment(StoreRepairTaskCommentRequest $request, RepairTask $task): RedirectResponse
    {
        RepairTaskComment::create([
            'repair_task_id' => $task->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
            'is_system' => false,
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function destroy(RepairTask $task): RedirectResponse
    {
        if (! auth()->user()->canManageTeknisiTasks()) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus tugas.');
        }

        try {
            if ($task->foto_bukti && Storage::disk('public')->exists($task->foto_bukti)) {
                Storage::disk('public')->delete($task->foto_bukti);
            }

            $task->delete();

            return redirect()->route('teknisi.repair-tasks.index')
                ->with('success', 'Tugas berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus tugas: '.$e->getMessage());
        }
    }
}
