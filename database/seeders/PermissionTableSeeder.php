<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Arr;

class PermissionTableSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $apis = [
            'area',
            'arriavalActual',
            'arrivalPlan',
            // 'auth',
            'country',
            'crushingActual',
            'customer',
            'material',
            'photo',
            'product',
            'production',
            'productionPlan',
            'role',
            'user',
            'stockCrushed'
        ];
        $pemissions = ['create', 'read', 'update', 'delete'];
        foreach ($apis as $api) {
            foreach ($pemissions as $permission) {
                $name = $api . '-' . $permission;
                $article = Permission::where('name',  $name)->exists();
                if ($article != true)
                    Permission::create(['name' => $name]);
            }
        }
        // create roles and assign existing permissions
        $role = Role::where('name',  'Staff')->first();
        if ($role == null)
            $role = Role::create(['name' => 'Staff']);
        $apifilter = ['user', 'role'];
        $permissionfilter = ['delete'];
        foreach ($apis as $api) {
            // filter some permission
            if (!in_array($api, $apifilter)) {
                foreach ($pemissions as $permission) {

                    if (!in_array($permission, $permissionfilter)) {
                        $name = $api . '-' . $permission;
                        // echo $name." ";
                        $role->givePermissionTo($name);
                    }
                }
            }
        }
        $role = Role::where('name',  'Admin')->first();
        if ($role == null)
            $role = Role::create(['name' => 'Admin']);
        $apifilter = ['user', 'role'];

        foreach ($apis as $api) {
            if (!in_array($api, $apifilter)) {
                foreach ($pemissions as $permission) {
                    $name = $api . '-' . $permission;
                    $role->givePermissionTo($name);
                }
            }
            $role = Role::where('name',  'Super-Admin')->first();
            if ($role == null)
                $role = Role::create(['name' => 'Super-Admin']);
            $user = \App\Models\User::find(1);
            $user->assignRole($role);
            $user = \App\Models\User::find(2);
            $user->assignRole($role);
        }
        // $role2 = Role::create(['name' => 'Admin']);

        // $role2->givePermissionTo('material-create');
        // $role2->givePermissionTo('material-read');
        // $role2->givePermissionTo('material-update');
        // $role2->givePermissionTo('material-delete');

        // $role2->givePermissionTo('user-create');
        // $role2->givePermissionTo('user-read');
        // $role2->givePermissionTo('user-update');

        // $role3 = Role::create(['name' => 'Super-Admin']);
        // gets all permissions via Gate::before rule; see AuthServiceProvider


        // $user = \App\Models\User::find(1);
        // $user->assignRole($role3);
        // $user = \App\Models\User::find(2);
        // $user->assignRole($role2);
        // $user = \App\Models\User::find(3);
        // $user->assignRole($role1);
        // // create demo users
        // $user = \App\Models\User::factory()->create([
        //     'name' => 'Example User',
        //     'email' => 'test@example.com',
        // ]);
        // $user->assignRole($role1);

        // $user = \App\Models\User::factory()->create([
        //     'name' => 'Example Admin User',
        //     'email' => 'admin@example.com',
        // ]);
        // $user->assignRole($role2);

        // $user = \App\Models\User::factory()->create([
        //     'name' => 'Example Super-Admin User',
        //     'email' => 'superadmin@example.com',
        // ]);
        // $user->assignRole($role3);
    }
}
