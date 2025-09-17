<?php

namespace Tests;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use ReflectionClass;

class AuthControllerRedirectTest extends TestCase
{
    protected function invokeRedirect(Request $request): string
    {
        $controller = new AuthController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('redirectUrl');
        $method->setAccessible(true);

        return $method->invoke($controller, $request);
    }

    protected function setAppUrl(?string $url): void
    {
        if ($url === null) {
            putenv('APP_URL');
            unset($_ENV['APP_URL'], $_SERVER['APP_URL']);
        } else {
            putenv('APP_URL=' . $url);
            $_ENV['APP_URL'] = $url;
            $_SERVER['APP_URL'] = $url;
        }
    }

    public function test_redirect_accepts_absolute_url_with_same_host_and_port(): void
    {
        $this->setAppUrl('http://localhost');

        $request = Request::create('/', 'POST', [
            'redirect' => 'http://localhost:8080/list?mode=favorites',
        ]);

        $result = $this->invokeRedirect($request);

        $this->assertSame('/list?mode=favorites', $result);
    }

    public function test_redirect_rejects_absolute_url_with_different_host(): void
    {
        $this->setAppUrl('http://localhost:8080');

        $request = Request::create('/', 'POST', [
            'redirect' => 'http://example.com/profile',
        ]);

        $result = $this->invokeRedirect($request);

        $this->assertSame('/', $result);
    }

    public function test_redirect_uses_request_host_when_app_url_is_not_set(): void
    {
        $this->setAppUrl(null);

        $request = Request::create('/', 'POST', [
            'redirect' => 'http://localhost:8080/dashboard',
        ], [], [], [
            'HTTP_HOST' => 'localhost:8080',
            'SERVER_PORT' => 8080,
        ]);

        $result = $this->invokeRedirect($request);

        $this->assertSame('/dashboard', $result);
    }
}
