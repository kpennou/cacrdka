<?php
return [
  'env' => getenv('APP_ENV') ?: 'prod',
  'app_url' => getenv('APP_URL') ?: '',
  'db' => [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'name' => getenv('DB_NAME') ?: 'cacrdka',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
  ],
  'upload_dir' => getenv('UPLOAD_DIR') ?: (__DIR__ . '/../storage/uploads'),
  'max_upload_mb' => (int)(getenv('MAX_UPLOAD_MB') ?: 5),
];
