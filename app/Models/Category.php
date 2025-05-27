<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $fillable = ['name', 'slug'];
    public $timestamps = false;
    public function products()
    {
        return $this->hasMany('App\Models\Product');
    }
}
