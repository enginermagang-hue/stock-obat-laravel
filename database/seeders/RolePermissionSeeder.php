<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-own-data',
            'view-master',
            'manage-master',
            'view-lplpo',
            'create-lplpo',
            'verify-lplpo',
            'view-permintaan',
            'create-permintaan',
            'verify-permintaan',
            'receive-goods',
            'create-distribution',
            'approve-distribution',
            'confirm-distribution',
            'manage-stock',
            'input-opname',
            'verify-opname',
            'manage-rko',
            'manage-anggaran',
            'approve-po',
            'view-reports',
            'view-audit-log',
            'manage-prediksi',
            'manage-users',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $map = [
            'puskesmas' => [
                'view-own-data', 'view-lplpo', 'create-lplpo',
                'view-permintaan', 'create-permintaan',
                'confirm-distribution', 'view-reports', 'manage-prediksi',
            ],
            'gudang' => [
                'view-master', 'manage-master', 'receive-goods',
                'create-distribution', 'manage-stock', 'input-opname', 'view-reports',
            ],
            'distribusi' => [
                'view-master', 'create-distribution', 'view-reports',
            ],
            'apoteker' => [
                'view-own-data', 'view-master', 'manage-master',
                'view-lplpo', 'view-permintaan',
                'verify-lplpo', 'verify-permintaan', 'receive-goods',
                'create-distribution', 'approve-distribution', 'manage-stock',
                'input-opname', 'verify-opname', 'manage-rko', 'manage-anggaran',
                'approve-po', 'view-reports', 'view-audit-log', 'manage-prediksi',
            ],
            'admin' => $permissions,
        ];

        foreach ($map as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($rolePermissions);
        }
    }
}
