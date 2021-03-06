<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Master;
use App\Models\Objective;
use App\Models\Placelist;
use App\Http\Service\googleApiService;
use Illuminate\Support\Facades\Config;


//　旅行プランに関する操作
class travelplanController extends Controller
{
    /**
	 * 目的: 旅行プランの生成
	 * @param String $spoint_name 出発地名
	 * @param String $spoint 出発地座標
	 * @param String $main_objectiveId メイン目的ID
     * @param String $sub_objectiveId サブ目的ID
	 * @param String $areaId 地域ID
	 *
	 **/
    public function getTravelPlan($spoint_name,$spoint,$main_objectiveId,$sub_objectiveId,$areaId) :array
    {
        $maxTime = 0;
        $objective = new Objective();
        $placelist = new Placelist();
        $master = new Master();
        $main_obj = $objective->getById($main_objectiveId);
        $sub_obj = $objective->getById($sub_objectiveId);
        $main_place = $placelist->findByAreaIdAndMAinObjectId($main_objectiveId,$areaId);
        $sub_place = $placelist->findByAreaIdAndSubObjectId($main_objectiveId,$sub_objectiveId,$areaId);
        $latlng = $master->findByKbnAndSubId(config('api.database.master.area.kbn'),$areaId)[0]->str_field01;

        $from = $spoint;
        $count = 0;
        $insData = array();
        $plancount = 0;

        while(true){
            //メイン目的が最大回数､もしくはメイン目的の行き先がない場合
            //かつサブ目的の行き先がない場合､もしくはメイン目的とサブ目的が同じ
            //もしくは最大滞在時間がマイナスの場合
            if((($main_obj[0]->maxcount <= $count || count($main_place)==0)
                && (count($sub_place)==0 || $main_objectiveId==$sub_objectiveId))
                || $maxTime < 0){
                break;
            }

            if($plancount%2==0 && $main_obj[0]->maxcount > $count && count($main_place)!=0){
                $count++;
                //$obj = $main_obj;
                $list = &$main_place;
                $purpose = $main_objectiveId;
            }else{
                if($main_objectiveId!=$sub_objectiveId){
                    //$obj = $sub_obj;
                    $list = &$sub_place;
                    $purpose = $sub_objectiveId;
                }
            }

            //行先が決定するまでか目的地リストが空になるまでループ
            while(count($list)>0){
                //行先を決定する
                $tolist = $this->getPlace($list);
                //行き先の情報をセット
                $to = $tolist->lat .",". $tolist->lng;
                //目的地への移動時間を取得
                $time_ja = $this->setRow($from,$to,$maxTime,$latlng);
                //移動時間が正しく取得できているかのチェック
                if($time_ja != ""){
                    //プランを1行作成する
                    $pushData=array(
                        'time_ja'=>$time_ja,
                        'name'=>$tolist->name,
                        'address'=>$tolist->address,
                        'number'=>$tolist->phone_number,
                        'site-url'=>$tolist->site_url,
                        'latlng'=>$to,
                        'purpose'=>intval($purpose),
                    );
                    array_push($insData,$pushData);

                    //目的地毎に設定された目的地での滞在時間を減少させる
                    $maxTime -= intval($tolist->stay_second);
                    if($maxTime == 0){
                        $maxTime = -1;
                    }
                    //目的地(to)を出発地(from)に入れる
                    $from = $to;
                    $plancount++;    
                    break;
                }
            }
        }
        //帰りの経路の算出
        $to = $spoint;
        $time_ja = $this->setRow($from,$to,$maxTime,$latlng);
        $pushData = array(
            'time_ja'=>$time_ja,
            'name'=>$spoint_name,
            'address'=>$to,
        );
        array_push($insData,$pushData);
        return $insData;
    }

    /**
	 * 目的: 目的地をランダムに取得
	 * @param array $list 目的地リスト
	 *
	 **/
    public function getPlace(&$list) :object
    {
        $max = count($list)-1;
        $rnd = mt_rand(0,$max);
        $res = $list[$rnd];
        unset($list[$rnd]);
        $list = array_values($list);
        return $res;
    }

    /**
	 * 目的: 経路を取得し、プランを退避する
	 * @param String $from 出発地住所
     * @param String $to　目的地住所
     * @param int $maxTime 最大滞在時間
     * @param String $latlng 初期出発地エラー用固定出発地
	 *
	 **/
    public function setRow(&$from,&$to,&$maxTime,$latlng) :String
    {
        $googleApi = new googleApiService();
        $res = $googleApi->getDirectionList($from,$to);
        if(empty($res)){
            if($maxTime < 0){
                $to = $latlng;
                $res = $googleApi->getDirectionList($from,$to);
                $row = $res[0]->legs[0]->duration->text;
            }else if($maxTime == 0){
                $from = $latlng;
                $row = "";
            }else{
                $row = "";
            }
        }else{
            //最大滞在時間が設定されていれば､移動時間を減少させる
            //設定されていなければ最大滞在時間を設定
            if($maxTime != 0){
                $maxTime -= intval($res[0]->legs[0]->duration->value);
            }else{
                $maxTime = config('api.plan_setting.stay_scond_max');
            }
            //目的地への移動時間をセット
            $row = $res[0]->legs[0]->duration->text;
        }
        return $row;
    }

    /**
     * 目的: プランの移動時間を再計算する
     * @param String $latlng 緯度経度リスト(:区切り)
     **/
    public function getTimeSet($latlng) :array
    {
        $googleApi = new googleApiService();
        $array = explode(":",$latlng);
        $max = count($array)-1;
        $error_flg = 0;
        $result = array();
        $res = $googleApi->getDirectionList($array[$max],$array[0]);
        for($i=0;$i<=$max;$i++){
            if(empty($res)){
                //経路がなくなった場合の処理
                $error_flg = 1;
            }else{
                $row = $res[0]->legs[0]->duration->text;
                array_push($result,$row);
            }
            if($i == $max || $error_flg == 1){
                break;
            }
            $res = $googleApi->getDirectionList($array[$i],$array[$i+1]);
        }
        $result['error_flg'] = $error_flg;
        return $result;
    }
}
