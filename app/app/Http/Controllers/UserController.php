<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Favorite;
use App\Models\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $logins = json_decode(Cookie::get('users'), true);
        $emails = array_keys($logins);
        $users = User::whereIn('email', $emails)->get(['id', 'name', 'email', 'avatar_path']);

        return view('lite.users', [
            'users' => $users,
        ]);
    }

    public function switch(Request $request, $id)
    {
        $logins = json_decode(Cookie::get('users'), true);
        $emails = array_keys($logins);

        $user = User::whereIn('email', $emails)->where('id', $id)->first();
        if (!$user) {
            return redirect('/users')
                ->with('success', false)
                ->with('error', 'Невозможно переключиться на данного пользователя. Авторизуйтесь заново.');
        }

        $this->auth($user);
        return redirect('/');
    }

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

        $this->auth($user);

        return response()->json([
            'success' => true,
            'message' => 'Успешный вход.',
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
            'message' => 'Пользователь успешно создан.',
        ]);
    }

    public function favorite(Request $request): JsonResponse
    {
        $animeId = (int) $request->input('anime_id');
        $favorite = filter_var($request->input('favorite'), FILTER_VALIDATE_BOOLEAN);
        $user = Auth::user();

        if ($favorite) {
            Favorite::firstOrCreate([
                'user_id' => $user->id,
                'anime_id' => $animeId,
            ]);
        } else {
            Favorite::where('user_id', $user->id)
                ->where('anime_id', $animeId)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'favorited' => (bool) $favorite,
            'message' => $favorite ? 'Добавлено в избранное' : 'Удалено из избранного',
        ]);
    }

    private function auth($user)
    {
        $secret = Str::random(10);
        $loginsCookie = Cookie::get('users');
        if (!$loginsCookie) {
            $logins = [];
        } else {
            $logins = json_decode($loginsCookie, true);
        }
        $logins[$user->email] = $secret;
        Cookie::queue('users', json_encode($logins), 525600); // 1 year
        Login::create([
            'email' => $user->email,
            'secret' => $secret,
        ]);

        Auth::login($user);
    }
}
