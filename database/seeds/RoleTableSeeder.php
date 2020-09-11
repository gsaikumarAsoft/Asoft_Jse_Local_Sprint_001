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
      'description' => 'Settlement Bank	',
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
