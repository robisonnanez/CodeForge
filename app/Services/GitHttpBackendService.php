<?php

namespace App\Services;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GitHttpBackendService
{
    public function response(
        Request $request,
        Repository $repository,
        ?User $actor,
        string $path,
        string $service,
    ): StreamedResponse {
        $contentType = $request->isMethod('POST')
            ? "application/x-{$service}-result"
            : "application/x-{$service}-advertisement";
        $environment = [
            'GIT_PROJECT_ROOT' => (string) config('codeforge.repositories_root'),
            'GIT_HTTP_EXPORT_ALL' => '1',
            'PATH_INFO' => '/'.$repository->storage_uuid.'.git/'.$path,
            'REQUEST_METHOD' => $request->getMethod(),
            'QUERY_STRING' => $request->getQueryString() ?? '',
            'CONTENT_TYPE' => (string) $request->header('Content-Type', ''),
            'CONTENT_LENGTH' => (string) $request->server('CONTENT_LENGTH', ''),
            'REMOTE_USER' => $actor?->username ?? 'anonymous',
            'REMOTE_ADDR' => $request->ip() ?? '',
            'PATH' => '/usr/bin:/bin',
            'GIT_CONFIG_NOSYSTEM' => '1',
            'GIT_TERMINAL_PROMPT' => '0',
        ];

        return response()->stream(function () use ($request, $environment): void {
            $process = proc_open(
                ['/usr/lib/git-core/git-http-backend'],
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['file', '/dev/null', 'w'],
                ],
                $pipes,
                null,
                $environment
            );

            if (! is_resource($process)) {
                return;
            }

            $requestStream = $request->getContent(true);

            if (is_resource($requestStream)) {
                stream_copy_to_stream($requestStream, $pipes[0]);
            }

            fclose($pipes[0]);
            $headersComplete = false;

            while (($line = fgets($pipes[1])) !== false) {
                if (! $headersComplete) {
                    if (rtrim($line, "\r\n") === '') {
                        $headersComplete = true;
                    }

                    continue;
                }

                echo $line;
                flush();
            }

            fclose($pipes[1]);
            proc_close($process);
        }, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-cache, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
