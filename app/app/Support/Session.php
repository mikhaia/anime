<?php

namespace App\Support;

class Session
{
    public static function start(): void
    {
        if (!app()->bound('session')) {
            throw new \RuntimeException('Session store is not bound to the container.');
        }

        /** @var \Illuminate\Session\Store $store */
        $store = app('session');

        if (! $store->isStarted()) {
            $store->start();
        }
    }

    public static function put(string $key, $value): void
    {
        self::start();

        app('session')->put($key, $value);
    }

    public static function get(string $key, $default = null)
    {
        self::start();

        return app('session')->get($key, $default);
    }

    public static function forget(string $key): void
    {
        self::start();

        app('session')->forget($key);
    }

    public static function has(string $key): bool
    {
        self::start();

        return app('session')->has($key);
    }

    public static function flash(string $key, $value): void
    {
        self::start();

        app('session')->flash($key, $value);
    }

    public static function getFlash(string $key, $default = null)
    {
        self::start();

        return app('session')->get($key, $default);
    }

    public static function allFlashes(): array
    {
        self::start();

        $store = app('session');
        $keys = $store->get('_flash.old', []);
        $flashes = [];

        foreach ($keys as $key) {
            $flashes[$key] = $store->get($key);
        }

        return $flashes;
    }
}
