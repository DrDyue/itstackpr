<?php

return [
    'disk' => env('BACKUP_DISK', 'local'),
    'directory' => env('BACKUP_DIRECTORY', 'backups'),
    'max_upload_kb' => (int) env('BACKUP_MAX_UPLOAD_KB', 102400),
];
