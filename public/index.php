<?php
require __DIR__ . '/../app/lib/Database.php';
require __DIR__ . '/../app/lib/Auth.php';
require __DIR__ . '/../app/lib/Helpers.php';
Auth::startSessionSecure();
if (Auth::check()) {
    header('Location: ' . base_url('dashboard.php'));
} else {
    header('Location: ' . base_url('login.php'));
}
