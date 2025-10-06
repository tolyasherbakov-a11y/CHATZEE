<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    public function __invoke(Request $request)
    {
        $u = $request->user();

        $roles = method_exists($u, "getRoleNames") ? $u->getRoleNames() : collect();
        $perms = method_exists($u, "getAllPermissions") ? $u->getAllPermissions()->pluck("name") : collect();

        $reverb = config("broadcasting.connections.reverb");
        // ВАЖНО: не возвращаем секреты
        $reverbPublic = [
            "key"    => $reverb["key"] ?? null,
            "host"   => $reverb["host"] ?? request()->getHost(),
            "port"   => $reverb["port"] ?? 80,
            "scheme" => $reverb["scheme"] ?? "http",
            "path"   => $reverb["path"] ?? "/reverb",
        ];

        $s3 = [
            "disk"   => config("filesystems.default"),
            "bucket" => config("filesystems.disks.s3.bucket"),
            "region" => config("filesystems.disks.s3.region"),
            "cdn"    => config("filesystems.disks.s3.url") ?: null,
        ];

        return response()->json([
            "app" => [
                "name" => config("app.name"),
                "env"  => app()->environment(),
            ],
            "user" => [
                "id"    => $u->id,
                "name"  => $u->name,
                "email" => $u->email,
                "avatar_url" => $u->avatar_url ?? null,
                "roles" => $roles->values(),
                "permissions" => $perms->values(),
            ],
            "reverb" => $reverbPublic,
            "s3"     => $s3,
            "features" => [
                "chat" => true,
                "typing" => true,
                "presence" => true,
            ],
        ]);
    }
}