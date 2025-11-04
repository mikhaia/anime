<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $name = trim($request->input('name'));
        $password = trim($request->input('password'));

        $user = User::where('name', $name)
            ->orWhere('email', $name)
            ->first();
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Неверное имя или пароль.',
            ]);
        }

        Auth::login($user);

        return response()->json([
            'success' => true,
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $name = trim($request->input('name'));
        $email = strtolower(trim($request->input('email')));
        $password = trim($request->input('password'));

        if (User::where('name', $name)->orWhere('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь с такими данными уже существует.',
            ]);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        Auth::login($user);

        return response()->json([
            'success' => true,
        ]);
    }
}
