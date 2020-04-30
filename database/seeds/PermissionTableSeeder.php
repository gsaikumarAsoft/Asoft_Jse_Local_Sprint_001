<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

          // Clear cache
          app()['cache']->forget('spatie.permission.cache');
        $permissions = [


 
            'create-broker-user',
            'read-broker-user',
            'update-broker-user',
            'delete-broker-user',
            'approve-broker-user',

            'create-broker-client',
            'read-broker-client',
            'update-broker-client',
            'delete-broker-client',
            'approve-broker-client',

            'create-broker-order',
            'read-broker-order',
            'update-broker-order',
            'delete-broker-order',
            'approve-broker-order',
 

 
         ];
 
 
        //  Permission::truncate();
         foreach ($permissions as $permission) {
 
              Permission::updateOrCreate(
                  ['name' => $permission]
                );
 
         }
    }
}
