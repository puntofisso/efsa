<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Favourites extends Model
{
    //
    protected $fillable = ['user_id', 'fav_identifier'];

}
