<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

//　目的地リスト検索用キーワードテーブルへの操作
class Placekeyword extends Model
{
    protected $table = 'placekeyword';
    
    /**
     * 目的: 目的地リスト検索用キーワードリストを取得
     * 引数: areaId　地域ID
     **/
    public function findByAreaId($areaId) :array
    {
        $items = \DB::table($this->table)->where('area_id',$areaId)->get()->toArray();
        return $items;
    }
}
