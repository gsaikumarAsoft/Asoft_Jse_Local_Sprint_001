<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{

    protected $guarded = ['id'];

    public function users()
{
  return $this->belongsToMany(User::class);
}

public function client()
{
  return $this->belongsToMany(BrokerClient::class);
}

public function broker_user()
{
  return $this->belongsToMany(BrokerUser::class);
}
}
