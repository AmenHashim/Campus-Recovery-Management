<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the three CPRMS roles and their permissions (FR-A4, FR-A5, BR-07).
 *
 * Permissions are defined now — even though most features arrive in later
 * sprints — so that authorization is declarative from day one and controllers
 * can call $user->can('verify claims') instead of hard-coding role checks.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset Spatie's cached roles/permissions before seeding
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Report Management (FR-B*)
            'report items',            // submit own lost/found reports
            'view items',              // browse/search the found-item pool
            'file guest reports',      // Officer files on behalf of a guest (BR-02, BR-08)
            'manage item status',      // Officer moves items through the lifecycle (FR-B6)

            // Claims (FR-D*)
            'submit claims',
            'verify claims',           // Officer approves/rejects with physical ID (BR-03)

            // Administration (FR-F*)
            'manage users',            // FR-F1
            'manage reference data',   // categories & locations — FR-F2
            'view analytics',          // FR-F3
            'view audit logs',         // FR-F4
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Student / Staff ──
        $studentStaff = Role::firstOrCreate(['name' => User::ROLE_STUDENT_STAFF, 'guard_name' => 'web']);
        $studentStaff->syncPermissions([
            'report items',
            'view items',
            'submit claims',
        ]);

        // ── Lost & Found Officer ── (operations)
        $officer = Role::firstOrCreate(['name' => User::ROLE_OFFICER, 'guard_name' => 'web']);
        $officer->syncPermissions([
            'view items',
            'file guest reports',
            'manage item status',
            'verify claims',
        ]);

        // ── Super Admin ── (governance) — gets everything
        $Admin = Role::firstOrCreate(['name' => User::ROLE_ADMIN, 'guard_name' => 'web']);
        $Admin->syncPermissions(Permission::all());
    }
}
