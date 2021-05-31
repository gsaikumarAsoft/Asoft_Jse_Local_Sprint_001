<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
  use Notifiable, HasRoles, HasApiTokens;

  /**
   * The attributes that are mass assignable.
   *
   * @var array 
   */
  protected $fillable = [
    'name', 'email', 'password', 'status', 'local_broker_id'
  ];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [
    'password', 'remember_token',
  ];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
  ];

  public function roles()
  {
    return $this->belongsToMany('App\Role');
  }
  /**
   * @param string|array $roles
   */
  public function authorizeRoles($roles)
  {
    if (is_array($roles)) {
      return $this->hasAnyRole($roles) ||
        abort(401, 'This action is unauthorized.');
    }
    return $this->hasRole($roles) ||
      abort(401, 'This action is unauthorized.');
  }
  /**
   * Check multiple roles
   * @param array $roles
   */
  public function hasAnyRole($roles)
  {
    return null !== $this->roles()->whereIn('name', $roles)->first();
  }
  /**
   * Check one role
   * @param string $role
   */
  public function hasRole($role)
  {
    return null !== $this->roles()->where('name', $role)->first();
  }
  public function local_broker()
  {
    return $this->belongsTo(LocalBroker::class);
  }
  public function role()
  {
    return $this->belongsTo(Role::class);
  }
  public function permission()
  {
    return $this->hasMany(Permission::class);
  }

  public function broker()
  {
    return $this->hasMany(BrokerUser::class, 'user_id');
  }

  public function trading_account()
  {
    return $this->hasMany(BrokerTradingAccount::class, 'user_id');
  }
  
}
