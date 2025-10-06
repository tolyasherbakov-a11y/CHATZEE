<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guards = ["web", "sanctum"]; // создаём под оба гарда

        $perms = ["chat.send", "chat.read", "profile.update"];

        foreach ($guards as $guard) {
            foreach ($perms as $perm) {
                Permission::findOrCreate($perm, $guard);
            }

            $userRole  = Role::findOrCreate("user", $guard);
            $adminRole = Role::findOrCreate("admin", $guard);

            $userRole->syncPermissions(["chat.send","chat.read","profile.update"]);
            $adminRole->syncPermissions(Permission::where("guard_name", $guard)->get());
        }
    }
}