<?php
/**
 * v12 计步基类
 * PHP Version 5
 * @category  Social
 * @package   BASE
 * @author    wyy <wyy@i500m.com>
 * @time      2017/7/31
 */

namespace frontend\modules\v12\controllers;

use common\helpers\Common;
use frontend\models\i500_social\WalkActivity;
use frontend\models\i500_social\WalkActivityPart;
use frontend\models\i500_social\WalkPrizeClaim;
use common\helpers\CurlHelper;

class BaseWalkController extends BaseController {
    
    //当前进行中的活动id
    public $aid = 0;
    public $activity = array();
    //无需判断活动的控制器
    protected $nocheck = ['walk-prize']; 
    //上传成绩的开始时间
    public $submit_start = 0; 
    //上传成绩的结束时间，也是排行榜的计算开始时间
    public $submit_end = 0;
    //排行榜同计规则
    public $rank_rules = '';
    //上传间隔时间（秒）
    public $submit_gap = 1200;
    //开启排行榜缓存
    public $cache_off = false;
    //排行榜缓存时间
    public $cache_time = 0;
    //m活动url
    public $murl_index = '/walk-activity/index';
    //m活动结束url
    public $murl_prize = '/walk-activity/prize';
    //m排行榜url
    public $murl_step = '/walk-activity/step';
    //上传成绩开启消息队列
    public $submitwalk_off = false;
    //排行榜标题
    public $rank_title = '美好联祥健步走排行榜';
    //活动按钮
    public $walk_button = '查看健步走比赛情况';
    
    /**
     * 初始化
     * @return array
     */
    public function init() {
        parent::init();
        $this->submit_start = strtotime(date('Y-m-d 00:00:00'));
        $this->submit_end = strtotime(date('Y-m-d 22:00:00'));
        $this->rank_rules = '活动规则：排行榜统计时间为每天'.date('H:i',$this->submit_end).'~24:00';
        $time = $this->submit_end-time();
        $this->cache_time = $time<0 ? $time+60*60*24 : $time;
        if(!in_array($this->id, $this->nocheck)){
            //初始化当前活动
            $this->_GoingActivity();
        }
    }
    
    //获取正在进行的活动id
    private function _GoingActivity() {
        $model = new WalkActivity();
        $data = $model->getInfo(
            ['status'=>1],
            true,
            ['id','title','content','rules','start_time','end_time','part_start','part_end','submit_start','submit_end'],
            ['and',['<=','start_time',date('Y-m-d H:i:s')],['>=','end_time',date('Y-m-d H:i:s')]],
            'create_time DESC'
        );
        if(empty($data)){
            $this->returnJsonMsg('2143',[],Common::C('code','2143'));
        }
        $this->aid = $data['id'];
        $this->activity = $data;
    }
    
    //根据活动id判断该用户是否参与过该活动
    public function checkPartById($aid) {
        if (empty($aid)) {
            return false;
        }
        $part = WalkActivityPart::find()->select(['id'])->where(['mobile'=>$this->mobile,'aid'=>$aid])->asArray()->one();
        //未参与
        if(empty($part)){
            return false;
        }
        return true;
    }
    
    //设备号是否被绑定过，设备已被其他用户绑定返回true
    public function ImeiIsBound($aid,$imei) {
        $bound = WalkActivityPart::find()->select(['mobile'])->where(['imei'=>$imei,'aid'=>$aid])->scalar();
        //存在但不相等
        if (!empty($bound) && $bound!=$this->mobile) {
            return true;
        }
        return false;
    }
    
    //校验当前用户该活动设备号 ，不可用返回true
    public function checkImei($aid,$imei) {
        $part = WalkActivityPart::find()->select(['imei'])->where(['mobile'=>$this->mobile,'aid'=>$aid])->scalar();
        //当前用户未绑定设备号时返回true,绑定但不相等返回false
        if(!empty($part) && $part!=$imei){
            return false;
        }
        return true;
    }
    
    //根据活动id判断该用户是否选择暴走团
    public function checkTeamById($aid) {
        if (empty($aid)) {
            return false;
        }
        $part = WalkActivityPart::find()->select(['id'])->where(['mobile'=>$this->mobile,'aid'=>$aid,'is_team'=>[1,2]])->asArray()->one();
        //未选择
        if(empty($part)){
            return false;
        }
        return true;
    }
    
    //根据活动id判断该用户是否参保
    public function checkCanBaoById($aid) {
        if (empty($aid)) {
            return false;
        }
        $part = WalkActivityPart::find()->select(['id'])->where(['mobile'=>$this->mobile,'aid'=>$aid,'status'=>3])->asArray()->one();
        //未参保
        if(empty($part)){
            return false;
        }
        return true;
    }
    
    //是否中奖
    public function isClaim($mobile) {
        //队伍id
        $team_id = WalkActivityPart::find()->select(['team_id'])->where(['mobile'=>$mobile,'aid'=>$this->aid,'is_team'=>1])->scalar();
        $team_id = empty($team_id) ? -1 : $team_id;
        //当前用户是否中奖
        $is_have = WalkPrizeClaim::find()->select(['id'])
                   ->where(['type'=>1,'type_key'=>$mobile])
                   ->orWhere(['type'=>4,'type_key'=>$team_id])
                   ->count();
        if (empty($is_have)) {
            return false;
        }
        return true;
    }
    
    //获取上一个提交成绩的结束时间
    public function getPevTime() {
        $time = date('Y-m-d H:i:s',$this->submit_end);
        if (time()<$this->submit_end) {
            $time = date('Y-m-d H:i:s',$this->submit_end-60*60*24);
        }
        return $time;
    }

    /**
     * 计步提交成绩消息队列通道
     * @param string $data 内容
     * @return array
     */
    public function submitWalkChannel($data = array())
    {
        if (empty($data)) {
            return false;
        }
        $url = Common::C('channelHost').'activity/get-add';
        $rs = CurlHelper::post($url, $data, true);
        if (empty($rs['code']) || $rs['code']!=200) {
            return false;
        }
        return true;
    }
}
