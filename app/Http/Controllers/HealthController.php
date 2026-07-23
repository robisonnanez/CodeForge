<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'application' => true,
            'database' => $this->check(fn () => DB::select('select 1')),
            'redis' => $this->check(fn () => Redis::connection()->ping()),
            'storage' => $this->storageIsHealthy(),
            'worker' => (int) Cache::get('codeforge:worker-heartbeat', 0) >= now()->subMinutes(3)->timestamp,
        ];
        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function check(callable $callback): bool
    {
        try {
            $callback();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function storageIsHealthy(): bool
    {
        $root = (string) config('codeforge.repositories_root');

        return is_dir($root) && ! is_link($root) && is_readable($root) && is_writable($root);
    }
}
