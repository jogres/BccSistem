<?php
if (!Auth::isAdmin()) {
    http_response_code(403);
    die('Acesso negado.');
}
