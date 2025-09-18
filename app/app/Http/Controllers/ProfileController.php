<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Auth;
use App\Support\Session;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            Session::flash('login_error', 'Авторизуйтесь, чтобы редактировать профиль.');
            Session::flash('open_login_modal', true);
            Session::flash('login_redirect', '/profile');

            return redirect('/');
        }

        return view('profile', [
            'user' => $user,
            'errors' => Session::getFlash('profile_errors', []),
            'success' => Session::getFlash('profile_success'),
            'old' => Session::getFlash('profile_old', []),
        ])->render();
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            Session::flash('login_error', 'Авторизуйтесь, чтобы редактировать профиль.');
            Session::flash('open_login_modal', true);
            Session::flash('login_redirect', '/profile');

            return redirect('/');
        }

        $name = trim((string) $request->input('name'));
        $password = (string) $request->input('password');
        $confirm = (string) $request->input('password_confirmation');
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Введите имя.';
        }

        if ($password !== '' || $confirm !== '') {
            if ($password === '') {
                $errors['password'] = 'Введите пароль.';
            }

            if ($confirm === '') {
                $errors['password_confirmation'] = 'Повторите пароль.';
            }

            if ($password !== '' && strlen($password) < 6) {
                $errors['password'] = 'Пароль должен содержать минимум 6 символов.';
            }

            if ($password !== '' && $confirm !== '' && $password !== $confirm) {
                $errors['password_confirmation'] = 'Пароли не совпадают.';
            }
        }

        $avatarFile = $request->file('avatar');
        $avatarPath = null;

        if ($avatarFile instanceof UploadedFile) {
            [$avatarPath, $avatarError] = $this->storeAvatar($user, $avatarFile);

            if ($avatarError !== null) {
                $errors['avatar'] = $avatarError;
            }
        }

        if (!empty($errors)) {
            Session::flash('profile_errors', $errors);
            Session::flash('profile_old', [
                'name' => $name,
            ]);

            return redirect('/profile');
        }

        $user->name = $name;

        if ($password !== '') {
            $user->password = Hash::make($password);
        }

        if ($avatarPath !== null) {
            $this->removeAvatar($user->avatar_path);
            $user->avatar_path = $avatarPath;
        }

        $user->save();

        Session::flash('profile_success', 'Профиль успешно обновлён.');

        return redirect('/profile');
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    protected function storeAvatar(User $user, UploadedFile $file): array
    {
        if (!extension_loaded('gd')) {
            return [null, 'Обработка изображений недоступна на сервере.'];
        }

        if (!$file->isValid()) {
            return [null, 'Не удалось загрузить файл.'];
        }

        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if ($mime === null || !in_array($mime, $allowedMimeTypes, true)) {
            return [null, 'Загрузите изображение в формате JPG, PNG или WebP.'];
        }

        $fileSize = $file->getSize();
        if ($fileSize !== null && $fileSize > 5 * 1024 * 1024) {
            return [null, 'Размер файла не должен превышать 5 МБ.'];
        }

        $imageData = @file_get_contents($file->getRealPath());
        if ($imageData === false) {
            return [null, 'Не удалось прочитать файл изображения.'];
        }

        $sourceImage = @imagecreatefromstring($imageData);
        if ($sourceImage === false) {
            return [null, 'Не удалось обработать изображение.'];
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        if ($width <= 0 || $height <= 0) {
            imagedestroy($sourceImage);
            return [null, 'Не удалось обработать изображение.'];
        }

        $cropSize = min($width, $height);
        $cropX = (int) max(0, floor(($width - $cropSize) / 2));
        $cropY = (int) max(0, floor(($height - $cropSize) / 2));

        $croppedImage = $sourceImage;

        if ($width !== $height || $cropX !== 0 || $cropY !== 0) {
            $croppedImage = imagecrop($sourceImage, [
                'x' => $cropX,
                'y' => $cropY,
                'width' => $cropSize,
                'height' => $cropSize,
            ]);

            imagedestroy($sourceImage);

            if ($croppedImage === false) {
                return [null, 'Не удалось обработать изображение.'];
            }
        }

        $avatarImage = imagecreatetruecolor(100, 100);

        if ($avatarImage === false) {
            imagedestroy($croppedImage);
            return [null, 'Не удалось обработать изображение.'];
        }

        imagealphablending($avatarImage, false);
        imagesavealpha($avatarImage, true);

        if (!imagecopyresampled(
            $avatarImage,
            $croppedImage,
            0,
            0,
            0,
            0,
            100,
            100,
            imagesx($croppedImage),
            imagesy($croppedImage)
        )) {
            imagedestroy($croppedImage);
            imagedestroy($avatarImage);

            return [null, 'Не удалось обработать изображение.'];
        }

        imagedestroy($croppedImage);

        try {
            $randomSuffix = bin2hex(random_bytes(8));
        } catch (Exception $exception) {
            imagedestroy($avatarImage);
            return [null, 'Не удалось сохранить изображение.'];
        }

        $relativePath = 'data/avatars/avatar_' . $user->getKey() . '_' . $randomSuffix . '.png';
        $storagePath = app()->basePath('public/' . $relativePath);
        $directory = dirname($storagePath);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            imagedestroy($avatarImage);
            return [null, 'Не удалось сохранить изображение.'];
        }

        if (!imagepng($avatarImage, $storagePath)) {
            imagedestroy($avatarImage);
            return [null, 'Не удалось сохранить изображение.'];
        }

        imagedestroy($avatarImage);

        return [$relativePath, null];
    }

    protected function removeAvatar(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $fullPath = app()->basePath('public/' . ltrim($path, '/'));

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
