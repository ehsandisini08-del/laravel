<?php

namespace App\Http\Controllers;

use App\Services\ActivityLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class UpdateController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index()
    {
        return view('update.index', [
            'branch' => $this->git('git rev-parse --abbrev-ref HEAD'),
            'commit' => $this->git('git rev-parse --short HEAD'),
            'lastCommitAt' => $this->git('git log -1 --format=%ci'),
            'running' => file_exists(storage_path('app/update.lock')),
            'lastUpdate' => $this->lastUpdate(),
            'logTail' => $this->tail(storage_path('logs/update.log'), 60),
        ]);
    }

    public function run(Request $request)
    {
        if (file_exists(storage_path('app/update.lock'))) {
            return redirect()->route('update.index')
                ->with('error', 'Update sedang berjalan. Tunggu hingga selesai.');
        }

        $log = storage_path('logs/update.log');

        if (! is_writable(dirname($log))) {
            return redirect()->route('update.index')
                ->with('error', 'Direktori log update tidak dapat ditulis ('.dirname($log).'). Periksa permission www-data.');
        }

        file_put_contents($log, '');

        $command = 'nohup '.$this->phpCliBinary().' artisan app:update >> '.$log.' 2>&1 &';

        Process::path(base_path())->run($command);

        $this->activityLogger->updated('System', 'Aplikasi di-update dari dashboard', null, [
            'triggered_by' => auth()->id(),
        ]);

        return redirect()->route('update.index')
            ->with('success', 'Update dimulai di latar belakang. Pantau status di halaman ini.');
    }

    public function status()
    {
        return response()->json([
            'running' => file_exists(storage_path('app/update.lock')),
            'last_update' => $this->lastUpdate(),
            'log_tail' => $this->tail(storage_path('logs/update.log'), 60),
        ]);
    }

    protected function phpCliBinary(): string
    {
        $candidates = [
            PHP_BINDIR.DIRECTORY_SEPARATOR.'php',
            '/usr/bin/php',
            '/usr/local/bin/php',
            'php',
        ];

        foreach ($candidates as $path) {
            $result = Process::path(base_path())
                ->run(escapeshellarg($path)." -r 'echo PHP_SAPI;'");

            if ($result->successful() && trim($result->output()) === 'cli') {
                return $path;
            }
        }

        return 'php';
    }

    protected function git(string $command): ?string
    {
        $result = Process::path(base_path())->run($command);

        return $result->successful() ? trim($result->output()) : null;
    }

    protected function lastUpdate(): ?array
    {
        $path = storage_path('app/update-status.json');

        if (! file_exists($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    protected function tail(string $path, int $lines): string
    {
        if (! file_exists($path)) {
            return '';
        }

        $content = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return implode("\n", array_slice($content, -$lines));
    }
}
