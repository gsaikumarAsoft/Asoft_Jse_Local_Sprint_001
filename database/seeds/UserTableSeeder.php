<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Role;
use Spatie\Permission\Models\Permission;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_ADMD = Role::where('name', 'ADMD')->first();
        $role_OPRD = Role::where('name', 'OPRD')->first();
        $role_BRKF = Role::where('name', 'BRKF')->first();
        $role_AGTS = Role::where('name', 'AGTS')->first();
        $role_ADMB = Role::where('name', 'ADMB')->first();
        $role_OPRB = Role::where('name', 'OPRB')->first();
        $role_BRKF = Role::where('name', 'BRKF')->first();
        $role_TRDB = Role::where('name', 'TRDB')->first();



        $admin = new User();
        $admin->name = 'JSE DMA Admin';
        $admin->email = 'admin@innovate10x.com';
        $admin->password = bcrypt('Secur!ty12310x');
        $admin->status = 'Approved';
        $admin->save();
        $admin->roles()->attach($role_ADMD);

        $admin = new User();
        $admin->name = 'JSE DMA Admin1';
        $admin->email = 'admin1@innovate10x.com';
        $admin->password = bcrypt('Secur!ty12310x');
        $admin->status = 'Approved';
        $admin->save();
        $admin->roles()->attach($role_ADMD);

        $admin = new User();
        $admin->name = 'JSE DMA Admin2';
        $admin->email = 'admin2@innovate10x.com';
        $admin->password = bcrypt('Secur!ty12310x');
        $admin->status = 'Approved';
        $admin->save();
        $admin->roles()->attach($role_ADMD);

        $admin = new User();
        $admin->name = 'JSE DMA Admin3';
        $admin->email = 'admin3@innovate10x.com';
        $admin->password = bcrypt('Secur!ty12310x');
        $admin->status = 'Approved';
        $admin->save();
        $admin->roles()->attach($role_ADMD);


        $admin = new User();
        $admin->name = 'JSE DMA Admin4';
        $admin->email = 'admin4@innovate10x.com';
        $admin->password = bcrypt('Secur!ty12310x');
        $admin->status = 'Approved';
        $admin->save();
        $admin->roles()->attach($role_ADMD);

        // $broker = new User();
        // $broker->name = 'Local Broker Admin';
        // $broker->email = 'local_broker@JMMB.com';
        // $broker->status = 'Approved';
        // $broker->password = bcrypt('password');
        // $broker->save();
        // $broker->roles()->attach($role_ADMB);
        // // $broker->hasPermissionTo('broker-delete');
        // $broker->givePermissionTo(['create-broker-user', 'read-broker-user', 'update-broker-user', 'delete-broker-user', 'approve-broker-user']);



        // $admin = new User();
        // $admin->name = 'Local Broker Operator';
        // $admin->email = 'operator@innovate10x.com';
        // $admin->status = 'Approved';
        // $admin->password = bcrypt('operatorpass');
        // $admin->save();
        // $admin->roles()->attach($role_OPRB);


        // $admin = new User();
        // $admin->name = 'Local Broker trader';
        // $admin->email = 'trader@innovate10x. com';
        // $admin->status = 'Approved';
        // $admin->password = bcrypt('traderpass');
        // $admin->save();
        // $admin->roles()->attach($role_TRDB);


    }
}
