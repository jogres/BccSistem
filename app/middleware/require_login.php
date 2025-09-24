<?php
Auth::startSessionSecure();
if (!Auth::check()) {
    header('Location: ' . base_url('login.php'));
    exit;
}
