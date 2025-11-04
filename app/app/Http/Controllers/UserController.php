<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Auth;
use App\Support\Session;
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

        return response()->json([
            'success' => true,
        ]);
    }
}
