<?php

namespace App\Support;

class Session
{
    protected static bool $flashInitialized = false;

    public static function start(): void
    {
        self::extendLifetime();

        if (session_status() === PHP_SESSION_NONE) {
            if (headers_sent()) {
                if (!isset($_SESSION)) {
                    $_SESSION = [];
                }
            } else {
                session_start();
            }
        }

        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [
                'current' => [],
                'next' => [],
            ];
        } else {
            $_SESSION['_flash'] = array_merge([
                'current' => [],
                'next' => [],
            ], $_SESSION['_flash']);
        }

        if (!self::$flashInitialized) {
            $_SESSION['_flash']['current'] = $_SESSION['_flash']['next'] ?? [];
            $_SESSION['_flash']['next'] = [];
            self::$flashInitialized = true;
        }
    }

    public static function put(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public static function flash(string $key, $value): void
    {
        self::ensureFlashInitialized();
        $_SESSION['_flash']['next'][$key] = $value;
    }

    public static function getFlash(string $key, $default = null)
    {
        self::ensureFlashInitialized();
        return $_SESSION['_flash']['current'][$key] ?? $default;
    }

    public static function allFlashes(): array
    {
        self::ensureFlashInitialized();
        return $_SESSION['_flash']['current'];
    }

    protected static function ensureFlashInitialized(): void
    {
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [
                'current' => [],
                'next' => [],
            ];
        }
        if (!self::$flashInitialized) {
            $_SESSION['_flash']['current'] = $_SESSION['_flash']['next'] ?? [];
            $_SESSION['_flash']['next'] = [];
            self::$flashInitialized = true;
        }
    }

    protected static function extendLifetime(): void
    {
        $lifetime = 60 * 60 * 24 * 365; // 1 year

        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.cookie_lifetime', (string) $lifetime);

        $params = session_get_cookie_params();
        $params['lifetime'] = $lifetime;

        if (session_status() === PHP_SESSION_ACTIVE) {
            setcookie(session_name(), session_id(), [
                'expires' => time() + $lifetime,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'] ?? false,
                'httponly' => $params['httponly'] ?? false,
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);

            return;
        }

        session_set_cookie_params($params);
    }
}
