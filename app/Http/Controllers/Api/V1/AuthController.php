<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('default')->plainTextToken;

        return response()->json([
            'id'    => (string) $user->getKey(),
            'name'  => $user->name,
            'email' => $user->email,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('default')->plainTextToken;

        return response()->json([
            'id'    => (string) $user->getKey(),
            'name'  => $user->name,
            'email' => $user->email,
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        $u = $request->user();

        return response()->json([
            'id'    => (string) $u->getKey(),
            'name'  => $u->name,
            'email' => $u->email,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function issueToken(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $token = $request->user()->createToken($data['name'])->plainTextToken;

        return response()->json(['token' => $token], 201);
    }

    public function revokeToken(Request $request, string $id)
    {
        $request->user()->tokens()->whereKey($id)->delete();

        return response()->json(['message' => 'Token revoked']);
    }
}
