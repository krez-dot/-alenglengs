<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'token_name' => ['nullable', 'string', 'max:100'],
        ]);

        if (! Auth::guard('web')->validate(['email' => $data['email'], 'password' => $data['password']])) {
            return response()->json(['message' => 'The provided credentials are invalid.'], 401);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $abilities = $user->isAdmin()
            ? ['*']
            : ['products:read', 'sales:read', 'sales:create'];
        $token = $user->createToken($data['token_name'] ?? 'API token', $abilities);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Token revoked.']);
    }
}
