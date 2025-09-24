<?php
final class Request {
    public static function postString(string $key, bool $trim=true): string {
        $v = $_POST[$key] ?? '';
        $v = is_string($v) ? $v : '';
        return $trim ? trim($v) : $v;
    }
    public static function postArray(string $key): array {
        $v = $_POST[$key] ?? [];
        return is_array($v) ? $v : [];
    }
}
