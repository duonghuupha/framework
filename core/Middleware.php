<?php
// core/Middleware.php

class Middleware
{
    /**
     * Dispatch middleware theo tên class (string).
     * $middlewareName là tên class như 'AuthMiddleware'
     */
    public static function handle($middlewareName)
    {
        $file = __DIR__ . '/' . $middlewareName . '.php';

        if (!file_exists($file)) {
            throw new Exception("Không tìm thấy middleware file: $file");
        }

        require_once $file;

        if (!class_exists($middlewareName)) {
            throw new Exception("Không tìm thấy class middleware: $middlewareName");
        }

        // Gọi static handle() của middleware (không truyền param)
        if (!method_exists($middlewareName, 'handle')) {
            throw new Exception("Middleware $middlewareName không có phương thức static handle()");
        }

        return $middlewareName::handle();
    }
}
