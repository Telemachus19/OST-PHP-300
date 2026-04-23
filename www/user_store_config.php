<?php

define('PROFILE_PICTURE_MAX_BYTES', 5 * 1024 * 1024);
define('PROFILE_PICTURE_UPLOAD_DIR', __DIR__ . '/uploads');
define('PROFILE_PICTURE_WEB_DIR', 'uploads/');

define('DB_HOST', 'db');
define('DB_PORT', 3306);
define('DB_NAME', 'app');
define('DB_USER', 'app');
define('DB_PASS', 'app');

define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change-this-in-production');
define('JWT_TTL_SECONDS', (int) (getenv('JWT_TTL_SECONDS') ?: 3600));
