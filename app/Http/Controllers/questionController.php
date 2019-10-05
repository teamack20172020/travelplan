<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Objective;
use App\Models\Questionparam;

/*
 *　質問に応じて目的を取得する処理
 * 
*/
class questionController extends Controller
{
    //
    public function getQuestionList(){	
        $question = new Question();
        $items = $question->getAll();
        return $items;
    }

    public function getObjectiveList(){	
        $objective = new Objective();
        $items = $objective->getAll();
        return $items;
    }

    public function saveQuestionparam($questionparam,$objectiveId){	
        $questionparam = new Questionparam();
        $questionparam->saveRow($questionparam,$objectiveId);
    }
}