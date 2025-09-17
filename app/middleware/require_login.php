<?php
Auth::startSessionSecure();
if (!Auth::check()) {
    redirect(base_url('login.php'));
}
