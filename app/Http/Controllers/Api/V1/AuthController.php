<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Authenticate user and return token.
     *
     * @throws AuthenticationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Throw native Laravel exception which maps to UNAUTHENTICATED (401)
            throw new AuthenticationException('Invalid email or password.');
        }

        $token = $user->createToken('api')->plainTextToken;

        return $this->respondSuccess([
            'user' => new UserResource($user->load('branch', 'doctorProfile.specialty')),
            'token' => $token,
        ], 'Logged in successfully.');
    }

    /**
     * Revoke current user's token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->respondSuccess(null, 'Logged out successfully.');
    }

    /**
     * Retrieve the authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('branch', 'doctorProfile.specialty');
        return $this->respondSuccess(new UserResource($user));
    }
}
