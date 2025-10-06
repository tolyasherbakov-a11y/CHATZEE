<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $userModel = config("auth.providers.users.model");
        /** @var \App\Models\User $user */
        $user = new $userModel();
        $user->name = $data["name"];
        $user->email = $data["email"];
        $user->password = Hash::make($data["password"]);
        $user->save();

        // Default role = user (create if missing)
        if (method_exists($user, "assignRole")) {
            try {
                $user->assignRole("user");
            } catch (RoleDoesNotExist $e) {
                Role::findOrCreate("user");
                $user->assignRole("user");
            }
        }

        $token = $user->createToken("api")->plainTextToken;

        return response()->json([
            "user" => [
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
                "roles" => method_exists($user, "getRoleNames") ? $user->getRoleNames() : [],
            ],
            "token" => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $userModel = config("auth.providers.users.model");
        /** @var \App\Models\User|null $user */
        $user = $userModel::query()->where("email", $data["email"])->first();

        if (! $user || ! Hash::check($data["password"], $user->password)) {
            return response()->json(["message" => "Invalid credentials"], 422);
        }

        $token = $user->createToken("api")->plainTextToken;

        return response()->json([
            "user" => [
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
                "roles" => method_exists($user, "getRoleNames") ? $user->getRoleNames() : [],
            ],
            "token" => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
        return response()->noContent();
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            "roles" => method_exists($user, "getRoleNames") ? $user->getRoleNames() : [],
            "permissions" => method_exists($user, "getAllPermissions") ? $user->getAllPermissions()->pluck("name") : [],
        ]);
    }

    public function issueToken(Request $request)
    {
        $request->validate([
            "name" => "required|string",
            "abilities" => "array",
            "abilities.*" => "string",
        ]);

        $token = $request->user()->createToken(
            $request->string("name"),
            $request->input("abilities", ["*"])
        )->plainTextToken;

        return response()->json(["token" => $token], 201);
    }

    public function revokeToken(Request $request, int $id)
    {
        $token = $request->user()->tokens()->where("id", $id)->first();
        if (! $token) {
            return response()->json(["message" => "Token not found"], 404);
        }
        $token->delete();
        return response()->noContent();
    }
}