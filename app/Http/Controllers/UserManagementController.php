<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Area;
use App\Models\User;
use App\Services\ActivityLoggerService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $users = $this->userService->getAll($request->only(['search', 'role']));

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $areas = Area::active()->orderBy('name')->get(['id', 'code', 'name']);

        return view('users.create', compact('areas'));
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());

        $this->activityLogger->created('User', "User {$user->name} ({$user->email}) created", $user);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $user->load('areas');
        $areas = Area::active()->orderBy('name')->get(['id', 'code', 'name']);

        return view('users.edit', compact('user', 'areas'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->update($user, $request->validated());

        $this->activityLogger->updated('User', "User {$user->name} ({$user->email}) updated", $user);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        try {
            $this->userService->delete($user);
        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }

        $this->activityLogger->deleted('User', "User {$user->name} ({$user->email}) deleted", $user);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
