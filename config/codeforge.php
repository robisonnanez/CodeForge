<?php

return [
    'ui_theme' => env('VITE_UI_THEME', 'laravel'),
    'bootstrap_admin_email' => env('CODEFORGE_BOOTSTRAP_ADMIN_EMAIL', 'superadmin@codeforge.local'),
    'bootstrap_admin_password' => env('CODEFORGE_BOOTSTRAP_ADMIN_PASSWORD'),
    'registration_enabled' => (bool) env('CODEFORGE_REGISTRATION_ENABLED', false),
    'demo_features_enabled' => (bool) env('CODEFORGE_DEMO_FEATURES_ENABLED', false),
    'repositories_root' => env('CODEFORGE_REPOSITORIES_ROOT', storage_path('app/codeforge/repositories')),
    'repositories_trash_root' => env('CODEFORGE_REPOSITORIES_TRASH_ROOT', storage_path('app/codeforge/trash')),
    'git_timeout_seconds' => (int) env('CODEFORGE_GIT_TIMEOUT_SECONDS', 30),
    'git_idle_timeout_seconds' => (int) env('CODEFORGE_GIT_IDLE_TIMEOUT_SECONDS', 10),
    'max_blob_bytes' => (int) env('CODEFORGE_MAX_BLOB_BYTES', 2_000_000),
    'max_archive_bytes' => (int) env('CODEFORGE_MAX_ARCHIVE_BYTES', 100_000_000),
    'max_diff_bytes' => (int) env('CODEFORGE_MAX_DIFF_BYTES', 5_000_000),
    'maintenance_lock_path' => env('CODEFORGE_MAINTENANCE_LOCK_PATH', storage_path('framework/codeforge-maintenance.lock')),
    'hook_socket_path' => env('CODEFORGE_HOOK_SOCKET_PATH', storage_path('framework/hook-ingest.sock')),
    'post_receive_hook' => env('CODEFORGE_POST_RECEIVE_HOOK', '/usr/local/bin/codeforge-post-receive'),
    'ssh_host' => env('CODEFORGE_SSH_HOST', parse_url((string) env('APP_URL', 'http://127.0.0.1'), PHP_URL_HOST) ?: '127.0.0.1'),
    'ssh_port' => (int) env('CODEFORGE_SSH_PORT', 2230),
    'ssh_user' => env('CODEFORGE_SSH_USER', 'git'),
    'http_clone_enabled' => (bool) env('CODEFORGE_HTTP_CLONE_ENABLED', false),
];
