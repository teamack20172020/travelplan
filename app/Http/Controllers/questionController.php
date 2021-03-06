<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Questionpoint;
use App\Models\Questionanalyze;

/*
 *　質問に関する操作
 * 
*/
class questionController extends Controller
{
    //
    public function getQuestionList() :array
    {
        $question = new Question();
        $items = $question->getAll();
        return $items;
    }

    public function getQuestionRes($answer) :array
    {
        $answerList = explode("q",$answer);
        $questionpoint = new Questionpoint();
        $items = $questionpoint->getAnswerRes($answerList);
        return $items;
    }

    public function saveQuestionAnalyze($objectiveId,$answer) :void
    {
        $questionanalyze = new Questionanalyze();
        $maxId = $questionanalyze->getMaxAnswerId() + 1;
        $answerList = explode("q",$answer);
        $insData = [];
        for($i=0;$i<count($answerList);$i++){
            $question_id = intval(substr($answerList[$i],0,4));
            $answerFlg = substr($answerList[$i],-1,1);
            array_push($insData ,[
                            "answer_id"=>$maxId,
                            "objective_id"=>$objectiveId,
                            "question_id"=>$question_id,
                            "answer"=>$answerFlg]);
        }
        $questionanalyze->saveList($insData);
    }
}
