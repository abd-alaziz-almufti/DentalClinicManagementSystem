<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $user = \App\Models\User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'user'  => $user->load('branch', 'doctorProfile'),
        'roles' => $user->getRoleNames(),
        'token' => $user->createToken('api')->plainTextToken,
    ]);
});
