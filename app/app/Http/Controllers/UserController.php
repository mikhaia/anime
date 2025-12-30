<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Favorite;
use App\Models\Login;
use App\Models\PasswordReset;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    /**
     * Показать форму редактирования профиля (Lite)
     */
    public function edit(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/users');
        }
        return view('lite.user_edit', [
            'user' => $user,
        ]);
    }

    /**
     * Обновить профиль пользователя (Lite)
     */
    public function update(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            exit('Unauthorized');
        }

        $user = User::find($authUser->id);
        if (!$user) {
            exit('User not found');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|max:2048',
            'password' => 'nullable|string|min:6',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                unlink(public_path($user->avatar_path));
            }
            $avatar = $request->file('avatar');
            $path = 'data/avatars/' . $user->id . '.' . $avatar->getClientOriginalExtension();
            Image::make($avatar)->fit(200, 200)->save($path);
            $user->avatar_path = $path;
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $this->auth($user);

        return redirect('/users')->with('success', 'Профиль обновлен!');
    }

    public function index(Request $request)
    {
        $logins = json_decode(Cookie::get('users') ?? '[]', true);
        $emails = array_keys($logins);
        $users = User::whereIn('email', $emails)->get(['id', 'name', 'email', 'avatar_path']);

        return view('lite.users', [
            'users' => $users,
        ]);
    }

    public function switch(Request $request, $id)
    {
        $logins = json_decode(Cookie::get('users') ?? '[]', true);
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

    public function recover(Request $request): JsonResponse
    {
        $email = strtolower(trim($request->input('email')));

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь с таким email не найден.',
            ]);
        }

        // Удалить старые токены для этого пользователя
        PasswordReset::where('user_id', $user->id)->delete();

        // Создать новый токен
        $token = Str::random(64);
        PasswordReset::create([
            'user_id' => $user->id,
            'token' => $token,
            'created_at' => now(),
            'expires_at' => now()->addHours(1),
        ]);

        // Отправить письмо с ссылкой на восстановление
        $resetUrl = url('/lite/reset/' . $token);
        Mail::to($user->email)->send(new PasswordResetMail($user, $resetUrl));

        return response()->json([
            'success' => true,
            'message' => 'Ссылка для восстановления пароля отправлена на email.',
        ]);
    }

    public function showReset(Request $request, $token)
    {
        $reset = PasswordReset::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$reset) {
            return redirect('/')->with('error', 'Ссылка восстановления истекла или невалидна.');
        }

        return view('lite.reset', [
            'token' => $token,
        ]);
    }

    public function reset(Request $request)
    {
        $token = trim($request->input('token'));
        $password = trim($request->input('password'));

        $reset = PasswordReset::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'Ссылка восстановления истекла или невалидна.',
            ]);
        }

        $user = $reset->user;
        $user->update([
            'password' => Hash::make($password),
        ]);

        // Удалить токен
        $reset->delete();

        // Авторизовать пользователя и отправить на главную
        $this->auth($user);

        return redirect('/');
    }
}
