<?php

use App\Role;
use Illuminate\Database\Seeder;

class RoleTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // $role_broker = new Role();
    // $role_broker->name = 'ADMB';
    // $role_broker->description = 'Local Broker Admin';
    // $role_broker->save();

    // $role_admin = new Role();
    // $role_admin->name = 'ADMD';
    // $role_admin->description = 'DMA Admin';
    // $role_admin->save();


    // $role_admin = new Role();
    // $role_admin->name = 'OPRB';
    // $role_admin->description = 'DMA Operator';
    // $role_admin->save();

    // $role_admin = new Role();
    // $role_admin->name = 'TRDB';
    // $role_admin->description = 'Local Broker Trader';
    // $role_admin->save();

    // Role::create([
    //   'name' => 'ADMB',
    //   'guard_name' => 'web',
    //   'description' => 'DMA Admin',
    // ]);

    // Role::create([
    //   'name' => 'ADMBU',
    //   'guard_name' => 'web',
    //   'description' => 'Local Broker User',
    // ]);

    // Role::create([
    //   'name' => 'ADMD',
    //   'guard_name' => 'web',
    //   'description' => 'DMA Admin',
    // ]);

    // Role::create([
    //   'name' => 'OPRB',
    //   'guard_name' => 'web', 
    //   'description' => 'DMA Operator',
    // ]);

    // Role::create([
    //   'name' => 'TRDB',
    //   'guard_name' => 'web',
    //   'description' => 'Local Broker Trader',
    // ]);

    Role::create([
      'description' => 'DMA Admin	',
      'guard_name' => 'web',
      'name' => 'ADMD',
    ]);
    Role::create([
      'description' => 'DMA Operator',
      'guard_name' => 'web',
      'name' => '	OPRD',
    ]);
    Role::create([
      'description' => 'Outbound Foreign Broker	',
      'guard_name' => 'web',
      'name' => 'BRKF',
    ]);
    Role::create([
      'description' => 'Settlement Agent	',
      'guard_name' => 'web',
      'name' => 'AGTS',
    ]);
    Role::create([
      'description' => 'Local Broker Admin',
      'guard_name' => 'web',
      'name' => 'ADMB',
    ]);
    Role::create([
      'description' => 'Local Broker Operator	',
      'guard_name' => 'web',
      'name' => 'OPRB',
    ]);
    Role::create([
      'description' => 'Local Broker Trader',
      'guard_name' => 'web',
      'name' => 'TRDB',
    ]);
    Role::create([
      'description' => 'FIX Router',
      'guard_name' => 'web',
      'name' => 'FIXR',
    ]);
  }
}
