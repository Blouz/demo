<?php
/**
 * 计步活动开始
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/6/21
 */
namespace frontend\modules\v12\controllers;

use common\helpers\Common;
use Yii;

class WalkController extends BaseWalkController {
    //app首次进入
    public function actionIndex() {
        //参与未开始
        if($this->activity['part_start']>date('Y-m-d H:i:s')){
            $this->returnJsonMsg('2136',[],Common::C('code','2136'));
        }
        //已参与过活动
        if($this->checkPartById($this->aid)){
            $this->returnJsonMsg('2131',[],Common::C('code','2131'));
        }
        $data['id'] = $this->aid;
        $data['title'] = '首届沈阳邻居节';
        $data['title2'] = '“健走赚首付”火热进行中';
        
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
}