@extends('layouts.lite')

@section('content')
    <div class="form user-edit-screen">
        <form method="POST" action="/users/edit" enctype="multipart/form-data" class="user-edit-form">
            @csrf
            <div>
                <label for="name">Имя</label>
                <input type="text" class="text-input" id="name" name="name" value="{{ old('name', $user->name) }}"
                    required>
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" class="text-input" id="email" name="email"
                    value="{{ old('email', $user->email) }}" required>
            </div>
            <div>
                <label for="avatar">Аватар</label>
                <input type="file" id="avatar" name="avatar" accept="image/*" class="file-input">
            </div>
            @if ($user->avatar_path)
                <div class="avatar-preview">
                    <img src="{{ asset($user->avatar_path) }}" alt="avatar" width="200" height="200">
                </div>
            @endif
            <div>
                <label for="password">Новый пароль</label>
                <input type="password" class="text-input" id="password" name="password" autocomplete="new-password">
            </div>
            <div class="bottom">
                <button type="submit">Сохранить</button>
            </div>
        </form>
    </div>
@endsection
