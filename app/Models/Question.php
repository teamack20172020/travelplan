<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
//
    protected $table = 'question';
    
    public function getAll(){	
        $items = \DB::table($this->table)->get();
        return $items;
    }
}
