<?php
require __DIR__ . '/../app/lib/Database.php';
require __DIR__ . '/../app/lib/Auth.php';
require __DIR__ . '/../app/lib/Helpers.php';
Auth::startSessionSecure();
Auth::logout();
redirect(base_url('login.php'));
