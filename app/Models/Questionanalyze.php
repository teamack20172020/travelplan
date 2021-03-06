<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 質問分析テーブルへの操作
class Questionanalyze extends Model
{
    // テーブル名
    protected $table = 'questionanalyze';

    /**
     * 目的: 回答IDの最大値を取得
     * 
     **/
    public function getMaxAnswerId() :int
    {
        return intval(\DB::table($this->table)->max("answer_id"));
    }
    
    /**
     * 目的: 回答結果の保存
     * @param array $dataList 回答結果
     **/
    public function saveList($dataList) :void
    {
        \DB::table($this->table)->insert($dataList);
    }
}
