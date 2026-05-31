<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    $user = Auth::user();
    return view('home', compact('user'));
})->name('home');

Route::get('/login-as/{role}', function (string $role) {
    $emails = [
        'admin' => 'admin@school.com',
        'teacher' => 'teacher@school.com',
        'student' => 'student@school.com'
    ];
    
    if (!array_key_exists($role, $emails)) {
        abort(404);
    }
    
    $user = \App\Models\User::where('email', $emails[$role])->firstOrFail();
    Auth::login($user);
    
    return redirect('/operation-gpt');
})->name('login.as');

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');
