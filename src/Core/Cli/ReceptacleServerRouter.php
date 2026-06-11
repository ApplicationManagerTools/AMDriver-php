<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Cli;

/**
 * Front controller for PHP built-in server (`-t` points to directory containing this file).
 */
final class ReceptacleServerRouter
{
    public static function dispatch(): void
    {
        $kernelFile = getenv('AM_DRIVER_RECEPTACLE_KERNEL_FILE') ?: '';
        if ('' === $kernelFile || !is_file($kernelFile)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Receptacle kernel file not configured']);

            return;
        }

        /** @var ReceptacleHttpKernel $kernel */
        $kernel = require $kernelFile;

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $parts = explode('_', substr($key, 5));
                $parts = array_map(static function (string $part): string {
                    return ucfirst(strtolower($part));
                }, $parts);
                $headers[implode('-', $parts)] = [(string) $value];
            }
        }

        [$status, $body] = $kernel->handle(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            file_get_contents('php://input') ?: '',
            $headers,
        );

        http_response_code($status);
        header('Content-Type: application/json');
        echo $body;
    }
}

ReceptacleServerRouter::dispatch();
