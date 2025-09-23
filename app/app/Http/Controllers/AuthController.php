<?php

namespace App\Http\Controllers;

use App\Models\DeviceLogin;
use App\Models\User;
use App\Support\Auth;
use App\Support\DeviceIdentifier;
use App\Support\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $redirect = $this->redirectUrl($request);

        if ($email === '' || $password === '') {
            $this->storeLoginError('Введите email и пароль.', $email);
            return redirect($redirect);
        }

        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($password, $user->password)) {
            $this->storeLoginError('Неверный email или пароль.', $email);
            return redirect($redirect);
        }

        Auth::login($user);

        $deviceHash = DeviceIdentifier::hashForRequest($request);

        DeviceLogin::updateOrCreate(
            [
                'device_hash' => $deviceHash,
                'user_id' => $user->getKey(),
            ],
            [
                'ip_address' => (string) $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
                'last_used_at' => Carbon::now(),
            ]
        );

        return redirect($redirect);
    }

    public function logout(Request $request): RedirectResponse
    {
        $redirect = $this->redirectUrl($request);

        $user = Auth::user();
        if ($user) {
            $deviceHash = DeviceIdentifier::hashForRequest($request);

            DeviceLogin::where('device_hash', $deviceHash)
                ->where('user_id', $user->getKey())
                ->delete();
        }

        Auth::logout();

        return redirect($redirect);
    }

    public function switchUser(Request $request): string
    {
        $deviceHash = DeviceIdentifier::hashForRequest($request);

        $logins = DeviceLogin::with('user')
            ->where('device_hash', $deviceHash)
            ->orderByDesc('last_used_at')
            ->get();

        $redirect = trim((string) $request->input('redirect', ''));

        return view('switch-user', [
            'deviceLogins' => $logins,
            'redirectTarget' => $redirect,
            'loginRedirect' => $redirect !== '' ? $redirect : '/',
        ])->render();
    }

    public function loginFromDevice(Request $request): RedirectResponse
    {
        $redirectTarget = trim((string) $request->input('redirect', ''));
        $redirect = $this->redirectUrl($request);
        $loginId = (int) $request->input('login_id');

        if ($loginId <= 0) {
            return $this->redirectBackToSwitchUser($redirectTarget);
        }

        $deviceHash = DeviceIdentifier::hashForRequest($request);

        $deviceLogin = DeviceLogin::with('user')
            ->where('device_hash', $deviceHash)
            ->where('id', $loginId)
            ->first();

        if (!$deviceLogin || !$deviceLogin->user) {
            return $this->redirectBackToSwitchUser($redirectTarget);
        }

        $user = $deviceLogin->user;

        Auth::login($user);

        $deviceLogin->forceFill([
            'ip_address' => (string) $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
            'last_used_at' => Carbon::now(),
        ])->save();

        return redirect($redirect);
    }

    protected function redirectBackToSwitchUser(string $redirectTarget): RedirectResponse
    {
        $query = $redirectTarget !== ''
            ? '?redirect=' . urlencode($redirectTarget)
            : '';

        return redirect('/switch-user' . $query);
    }

    public function showRegister(): string
    {
        return view('register', [
            'errors' => Session::getFlash('register_errors', []),
            'old' => Session::getFlash('register_old', []),
            'success' => Session::getFlash('register_success'),
        ])->render();
    }

    public function register(Request $request): RedirectResponse
    {
        $name = trim((string) $request->input('name'));
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $confirm = (string) $request->input('password_confirmation');

        $redirect = $this->redirectUrl($request);
        $errors = $this->validateRegistration($name, $email, $password, $confirm);

        if (!empty($errors)) {
            Session::flash('register_errors', $errors);
            Session::flash('register_old', [
                'name' => $name,
                'email' => $email,
            ]);

            return redirect('/register');
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        Auth::login($user);

        $deviceHash = DeviceIdentifier::hashForRequest($request);

        DeviceLogin::updateOrCreate(
            [
                'device_hash' => $deviceHash,
                'user_id' => $user->getKey(),
            ],
            [
                'ip_address' => (string) $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
                'last_used_at' => Carbon::now(),
            ]
        );

        return redirect($redirect !== '' ? $redirect : '/');
    }

    protected function storeLoginError(string $message, string $email = ''): void
    {
        Session::flash('login_error', $message);
        Session::flash('open_login_modal', true);
        if ($email !== '') {
            Session::flash('login_email', $email);
        }
    }

    protected function validateRegistration(string $name, string $email, string $password, string $confirm): array
    {
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Введите имя.';
        }

        if ($email === '') {
            $errors['email'] = 'Введите email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Введите корректный email.';
        } elseif (User::where('email', $email)->exists()) {
            $errors['email'] = 'Пользователь с таким email уже существует.';
        }

        if ($password === '') {
            $errors['password'] = 'Введите пароль.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Пароль должен содержать минимум 6 символов.';
        }

        if ($password !== $confirm) {
            $errors['password_confirmation'] = 'Пароли не совпадают.';
        }

        return $errors;
    }

    protected function redirectUrl(Request $request): string
    {
        $redirect = trim((string) $request->input('redirect'));

        if ($redirect === '') {
            return '/';
        }

        if (str_starts_with($redirect, 'http://') || str_starts_with($redirect, 'https://')) {
            $appUrl = (string) env('APP_URL', '');
            $redirectParts = parse_url($redirect) ?: [];

            if (empty($redirectParts['host'])) {
                return '/';
            }

            $allowedHost = '';
            $allowedScheme = null;
            $allowedPort = null;

            if ($appUrl !== '') {
                $appParts = parse_url($appUrl) ?: [];
                $allowedHost = strtolower((string) ($appParts['host'] ?? ''));
                $allowedScheme = $appParts['scheme'] ?? null;
                $allowedPort = $appParts['port'] ?? null;
            } else {
                $allowedHost = strtolower($request->getHost());
                $allowedScheme = $request->getScheme();
                $allowedPort = $request->getPort();
            }

            $targetHost = strtolower((string) $redirectParts['host']);
            $targetScheme = $redirectParts['scheme'] ?? null;
            $targetPort = $redirectParts['port'] ?? null;

            if ($allowedHost !== '' && $targetHost !== $allowedHost) {
                return '/';
            }

            if ($allowedScheme !== null && $targetScheme !== null && $allowedScheme !== $targetScheme) {
                return '/';
            }

            if ($allowedPort !== null && $targetPort !== null && $allowedPort !== $targetPort) {
                return '/';
            }

            $path = $redirectParts['path'] ?? '/';
            $path = $path === '' ? '/' : $path;
            $query = isset($redirectParts['query']) ? '?' . $redirectParts['query'] : '';
            $fragment = isset($redirectParts['fragment']) ? '#' . $redirectParts['fragment'] : '';

            $redirect = $path . $query . $fragment;
        }

        return str_starts_with($redirect, '/') ? $redirect : '/' . ltrim($redirect, '/');
    }
}
