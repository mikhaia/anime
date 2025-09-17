<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Auth;
use App\Support\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        return redirect($redirect);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        return redirect($this->redirectUrl($request));
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
            $appUrl = rtrim((string) env('APP_URL', ''), '/');
            if ($appUrl !== '' && str_starts_with($redirect, $appUrl)) {
                $redirect = substr($redirect, strlen($appUrl));
                $redirect = $redirect === '' ? '/' : $redirect;
            } else {
                return '/';
            }
        }

        return str_starts_with($redirect, '/') ? $redirect : '/' . ltrim($redirect, '/');
    }
}
