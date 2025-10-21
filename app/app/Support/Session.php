<?php

namespace App\Support;

use function config;

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
        if (session_status() !== PHP_SESSION_ACTIVE && ($cookieName = config('session.cookie')) !== null) {
            session_name($cookieName);
        }

        $lifetimeMinutes = (int) config('session.lifetime', 120);
        $expireOnClose = (bool) config('session.expire_on_close', false);
        $lifetimeSeconds = $expireOnClose ? 0 : $lifetimeMinutes * 60;

        ini_set('session.gc_maxlifetime', (string) max($lifetimeSeconds, 1));
        ini_set('session.cookie_lifetime', (string) $lifetimeSeconds);

        $params = [
            'lifetime' => $lifetimeSeconds,
            'path' => config('session.path', '/'),
            'secure' => (bool) config('session.secure', false),
            'httponly' => (bool) config('session.http_only', true),
        ];

        if (($domain = config('session.domain')) !== null) {
            $params['domain'] = $domain;
        }

        if (($sameSite = config('session.same_site')) !== null) {
            $params['samesite'] = $sameSite;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $cookie = $params;
            unset($cookie['lifetime']);
            $cookie['expires'] = $expireOnClose ? 0 : time() + $lifetimeSeconds;

            setcookie(session_name(), session_id(), $cookie);

            return;
        }

        session_set_cookie_params($params);
    }
}
