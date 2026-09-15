<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Default accounts. Officer and Super Admin can NEVER be self-registered
 * (FR-A5, BR-07) — they only exist because they are seeded or provisioned
 * by an existing Super Admin.
 */
class UserSeeder extends Seeder
{
    /** Bulk student/staff population — for pagination/search/perf testing (see ItemSeeder, ClaimSeeder). */
    protected const BULK_STUDENT_STAFF = 500;

    /** Percent of the bulk population left suspended, to exercise the 'active' account gate (FR-F1). */
    protected const SUSPENDED_RATE = 5;

    public function run(): void
    {
        // ── Super Admin ──
        $admin = User::firstOrCreate(
            ['email' => 'admin@mwangatech.ac.tz'],
            [
                'name'      => 'Admin',
                'reg_no'    => 'UNI/ADMIN/001',
                'phone'     => '+255700000001',
                'user_type' => null,           // not a report population (see migration comment)
                'password'  => Hash::make('Admin@12345'), // hashed automatically by the model's 'hashed' cast
                'status'    => User::STATUS_ACTIVE,
            ]
        );
        $admin->syncRoles([User::ROLE_ADMIN]);

        // ── Lost & Found Officer ──
        $officer = User::firstOrCreate(
            ['email' => 'office@mwangatech.ac.tz'],
            [
                'name'      => 'University Office',
                'reg_no'    => 'UNI/OFFICE/001',
                'phone'     => '+255700000002',
                'user_type' => null,
                'password'  => Hash::make('Officer@12345'),
                'status'    => User::STATUS_ACTIVE,
            ]
        );
        $officer->syncRoles([User::ROLE_OFFICER]);

        // ── Demo Student ──
        $student = User::firstOrCreate(
            ['email' => 'biggie@gmail.com'],
            [
                'name'      => 'Biggie Kasese',
                'reg_no'    => 'UNI/BCOM/2024/042',
                'phone'     => '+255700000003',
                'user_type' => User::TYPE_STUDENT,
                'password'  => Hash::make('biggie@12345'),
                'status'    => User::STATUS_ACTIVE,
            ]
        );
        $student->syncRoles([User::ROLE_STUDENT_STAFF]);

        // ── Demo Staff member (same role, different user_type) ──
        $staff = User::firstOrCreate(
            ['email' => 'allen@gmail.com'],
            [
                'name'      => 'Allen Mwakalinga',
                'reg_no'    => 'UNI/STAFF/2019/07',
                'phone'     => '+255700000004',
                'user_type' => User::TYPE_STAFF,
                'password'  => Hash::make('allen@12345'),
                'status'    => User::STATUS_ACTIVE,
            ]
        );
        $staff->syncRoles([User::ROLE_STUDENT_STAFF]);

        // ── Extra officers — realistic multi-officer claim/notification load for testing ──
        foreach ([2, 3] as $n) {
            $extraOfficer = User::firstOrCreate(
                ['email' => "office{$n}@mwangatech.ac.tz"],
                [
                    'name'      => "University Office {$n}",
                    'reg_no'    => "UNI/OFFICE/00{$n}",
                    'phone'     => "+25570000000{$n}",
                    'user_type' => null,
                    'password'  => Hash::make('Officer@12345'),
                    'status'    => User::STATUS_ACTIVE,
                ]
            );
            $extraOfficer->syncRoles([User::ROLE_OFFICER]);
        }

        $this->bulkStudentStaff();
    }

    /**
     * Fast-path bulk creation for pagination/search/perf testing — role assignment is done
     * as a single bulk insert into model_has_roles rather than 500 individual assignRole()
     * calls, since every row gets the same role.
     */
    protected function bulkStudentStaff(): void
    {
        $role = Role::where('name', User::ROLE_STUDENT_STAFF)->where('guard_name', 'web')->firstOrFail();

        $users = User::factory()
            ->count(self::BULK_STUDENT_STAFF)
            ->state(fn () => [
                'status' => random_int(1, 100) <= self::SUSPENDED_RATE ? User::STATUS_SUSPENDED : User::STATUS_ACTIVE,
            ])
            ->create();

        $users->map(fn (User $user) => [
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ])->chunk(300)->each(
            fn ($chunk) => DB::table('model_has_roles')->insert($chunk->all())
        );
    }
}
