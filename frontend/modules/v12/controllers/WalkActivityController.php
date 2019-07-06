<?php
/**
 * 计步活动
 * PHP Version 5
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/7/31 14:30
 */

namespace frontend\modules\v12\controllers;

use common\helpers\Common;
use frontend\models\i500_social\WalkTeam;
use frontend\models\i500_social\WalkZrankCommunity;
use frontend\models\i500_social\WalkZrankDistrict;
use frontend\models\i500_social\WalkZrankPersion;
use frontend\models\i500_social\WalkZrankTeamRen;
use frontend\models\i500_social\WalkZrankTeamZou;
use frontend\models\i500m\District;
use frontend\models\i500m\Community;
use yii\data\Pagination;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\WalkActivityPart;
use frontend\models\i500_social\WalkSubmit;
use frontend\models\i500_social\WalkAdv;
use frontend\models\i500_social\UserCertification;
use common\helpers\TxyunHelper;

class WalkActivityController extends BaseWalkController
{
    /**
     * 活动页面
     * @return array
     */
    public function actionIndex()
    {
        $data = [];
        $data['walkbutton'] = $this->walk_button;
        //设备号
        $imei = RequestHelper::post('imei','','trim');
        if (empty($imei)) {
            $this->returnJsonMsg('2148',[],Common::C('code','2148'));
        }
        //设备号不匹配
        if(!$this->checkImei($this->aid,$imei)){
            $this->returnJsonMsg('201',[$data],Common::C('code','2149'));
        }
        //设备号被绑定过
        if($this->ImeiIsBound($this->aid,$imei)){
            $this->returnJsonMsg('201',[$data],Common::C('code','2150'));
        }
        //上传成绩到时，活动结束
        if (time()>strtotime($this->activity['submit_end'])) {
            $this->_show3($data);
        //已加入暴走团，请上传成绩
        } elseif ($this->checkTeamById($this->aid)) {
            $this->_show2($data);
        //已参与活动,请加入暴走团
        } elseif ($this->checkPartById($this->aid)) {
            $this->_show4($data);
        //未参与活动,请参与
        } else {
            $this->_show1($data);
        }
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    //未参与活动，且未结束
    private function _show1(&$data) {
        $data['status'] = 1;
        $data['href'] = 'http://m.i500m.com'.$this->murl_index;
        $data['item'] = [
            'id' => $this->activity['id'],
        ];
        //是否参加了活动
        $part = WalkActivityPart::find()->select(['id'])
                ->where(['mobile'=>$this->mobile,'aid'=>$this->aid])
                ->asArray()->scalar();
        $data['item']['off_join'] = 1;
        //参与未开始或已结束
        if(time()<strtotime($this->activity['part_start']) || time()>strtotime($this->activity['part_end'])){
            $data['item']['off_join'] = 0;
        }
    }
    
    //已参与活动，且未结束
    private function _show2(&$data) {
        $data['status'] = 2;
        //计步广告
        $data['adv'] = WalkAdv::find()->select(['image'])->orderBy('create_time Desc')->asArray()->all();
        //可上传成绩时间
        $date1 = date('n月j日',strtotime($this->activity['submit_start']));
        $date2 = date('n月j日',strtotime($this->activity['submit_end']));
        $date3 = date('n月j日',strtotime($this->getPevTime()));
        //个人排名
        $rank = WalkZrankPersion::find()->select(['rank'])->where(['mobile'=>$this->mobile])->scalar();
        $rank = empty($rank) ? 0 : (int)$rank;
        //团队步数日冠军
        $team_max_name = WalkZrankTeamZou::find()->select(['name'])->orderBy('rank asc')->scalar();
        $team_max_name = empty($team_max_name) ? '无' : $team_max_name;
        //团队人数日冠军
        $part_max_name = WalkZrankTeamRen::find()->select(['name'])->orderBy('rank asc')->scalar();
        $part_max_name = empty($part_max_name) ? '无' : $part_max_name;
        
        $data['item'] = [
            'id' => $this->activity['id'],
            'title' => $this->activity['title'],
            'between_date' => "{$date1}~{$date2}",
            'between_time' => "每日".date('H:i',$this->submit_start)."~".date('H:i',$this->submit_end),
            'rank' => $rank,
            'rolling' => "{$date3}团队步数日冠军：{$team_max_name}，{$date3}团队人数日冠军：{$part_max_name}！",
        ];
        
        //当前用户是否中奖
        $data['item']['is_have'] = 0;
        if ($this->isClaim($this->mobile)) {
            $data['item']['is_have'] = 1;
        }
    }
    
    //活动结束
    private function _show3(&$data) {
        $data['status'] = 3;
        $data['href'] = 'http://m.i500m.com'.$this->murl_prize;
        $data['item'] = [
            'id' => $this->activity['id'],
        ];
        //当前用户是否中奖
        $data['item']['is_have'] = 0;
        if ($this->isClaim($this->mobile)) {
            $data['item']['is_have'] = 1;
        }
    }
    
    //暴走团列表
    private function _show4(&$data) {
        $data['status'] = 4;
        $data['item'] = [
            'id' => $this->activity['id'],
        ];
    }

    /**
     *  暴走列表带搜索的
     * @return array
     */
    public function actionTeamList()
    {
        $where = [];
        //暴走团名称
        $name = RequestHelper::post('name','','');
        //名称
        if(!empty($name)){
            $where = ['like','name',$name];
        }
        //查询暴走团列表
        $list = WalkTeam::find()->select(['id','name','first_letter'])
                ->where(['is_del'=>0])
                ->andWhere($where)
                ->orderBy('first_letter ASC,name ASC,create_time ASC')
                ->asArray()
                ->all();
        $data['list'] = $list;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     *  参加暴走团
     * @return array
     */
    public function actionWalkingTeam()
    {
        $aid = RequestHelper::post('aid','','intval');
        //参加没参加活动
        if(!$this->checkPartById($aid)){
            $this->returnJsonMsg('2142',[],Common::C('code','2142'));
        }
        //已参加暴走团
        if($this->checkTeamById($aid)){
            $this->returnJsonMsg('2147',[],Common::C('code','2147'));
        }
        //暴走团ID
        $team_id = RequestHelper::post('team_id','','intval');
        if (empty($team_id)) {
           $this->returnJsonMsg('2144',[],Common::C('code','2144'));
        }
        //是否选择参加暴走团
        if($team_id == -1){//没参加暴走团
            WalkActivityPart::updateAll(['is_team'=>2],['aid'=>$aid,'mobile'=>$this->mobile]);
        } else {//参加暴走团
            //暴走团是否存在
            $team = WalkTeam::findOne(['id'=>$team_id]);
            if(empty($team)){
                $this->returnJsonMsg('2145',[],Common::C('code','2145'));
            }
            WalkActivityPart::updateAll(['is_team'=>1,'team_id'=>$team_id,'team_time'=>date('Y-m-d H:i:s')],['aid'=>$aid,'mobile'=>$this->mobile]);
        }
        $this->returnJsonMsg('200',[],Common::C('code','200'));
    }

    /**
     * 参与不需要参保
     * @return array
     */
    public function actionJoinActivity()
    {
        //活动ID
        $aid = RequestHelper::post('aid','','intval');
        if (empty($aid) || $aid!=$this->aid) {
            $this->returnJsonMsg('2130',[],Common::C('code','2130'));
        }
        //设备号
        $imei = RequestHelper::post('imei','','trim');
        if (empty($imei)) {
            $this->returnJsonMsg('2148',[],Common::C('code','2148'));
        }
        //参与开始
        if(time()<strtotime($this->activity['part_start'])){
            $this->returnJsonMsg('2136',[],Common::C('code','2136'));
        }
        //参与结束
        if(time()>strtotime($this->activity['part_end'])){
            $this->returnJsonMsg('2137',[],Common::C('code','2137'));
        }
        //已参与过活动
        if($this->checkPartById($aid)){
            $this->returnJsonMsg('2131',[],Common::C('code','2131'));
        }
        //设备号已被绑定
        if($this->ImeiIsBound($aid,$imei)){
            $this->returnJsonMsg('2150',[],Common::C('code','2150'));
        }
        
        $data = [
            'aid'=>$aid,
            'uid'=>$this->uid,
            'mobile'=>$this->mobile,
            'status'=>2,
            'imei' => $imei,
        ];
        $walkActivityPart = new WalkActivityPart();
        $res = $walkActivityPart->insertInfo($data);
        if(!$res){
            $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        $this->returnJsonMsg('200',[],Common::C('code','200'));
    }

    //参与投保 是否实名认证 是则直接参与
    public function actionSetJoinActivity()
    {
        //真实姓名
        $realname = RequestHelper::post('realname', '', 'trim');
        if (empty($realname)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //身份证号
        $user_card = RequestHelper::post('user_card', '', 'trim');
        if (empty($user_card)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //活动ID
        $aid = RequestHelper::post('aid','','intval');
        if (empty($aid) || $aid!=$this->aid) {
            $this->returnJsonMsg('2130',[],Common::C('code','2130'));
        }
        //设备号
        $imei = RequestHelper::post('imei','','trim');
        if (empty($imei)) {
            $this->returnJsonMsg('2148',[],Common::C('code','2148'));
        }
        //参与开始
        if(time()<strtotime($this->activity['part_start'])){
            $this->returnJsonMsg('2136',[],Common::C('code','2136'));
        }
        //参与结束
        if(time()>strtotime($this->activity['part_end'])){
            $this->returnJsonMsg('2137',[],Common::C('code','2137'));
        }
        //已参与过活动
        if($this->checkPartById($aid)){
            $this->returnJsonMsg('2131',[],Common::C('code','2131'));
        }
        //设备号已被绑定
        if($this->ImeiIsBound($aid,$imei)){
            $this->returnJsonMsg('2150',[],Common::C('code','2150'));
        }

        //获取用户信息对象
        $info = UserCertification::findOne(['mobile'=>$this->mobile]);
        if (empty($info) || (!empty($info->status) && $info->status != 2)) {//用户未认证信息
            //获取用户信息对象
            $card = UserCertification::findOne(['user_card'=>$user_card]);
            //身份证号已存在
            if (!empty($card) && $card->mobile!=$this->mobile) {
                $this->returnJsonMsg('620', [], '身份证号已存在');
            }
            //实名认证审核失败
            if (!$this->credit_antifraud_verify($user_card, $realname)) {
               $this->returnJsonMsg('1061', [], Common::C('code', '1061'));
            }
            $info = empty($info) ? new UserCertification() : $info;
            $info->uid = $this->uid;
            $info->mobile = $this->mobile;
            $info->realname = $realname;
            $info->user_card = $user_card;
            $info->status = 2;
            $res = $info->save();
            //保存失败
            if (empty($res)) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
        }

        $data = [
            'aid'=>$aid,
            'uid'=>$this->uid,
            'mobile'=>$this->mobile,
            'status'=>3,
            'ginseng_time'=>date('Y-m-d H:i:s'),
            'imei' => $imei,
        ];
        $walkActivityPart = new WalkActivityPart();
        $res = $walkActivityPart->insertInfo($data);
        if(!$res){
            $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        UserBasicInfo::updateAll(['nickname'=>$realname,'realname'=>$realname,'update_time'=>date('Y-m-d H:i:s')],['mobile'=>$this->mobile]);
        //修改用户腾讯云信息
        TxyunHelper::Edit_userinfo($this->uid,['nickname'=>$realname]);
        
        $data = [];
        $data['status'] = (string)$info->status;
        $data['realname'] = $info->realname;
        $data['user_card'] = substr($info->user_card,0,3).'******'.substr($info->user_card,-4);
        
        $this->returnJsonMsg('200',[$data],"您的用户名已修改为:{$info->realname}");
    }

    /**
     * 上传步数
     * @return array
     */
    public function actionUploadStep()
    {
        //活动ID
        $aid = RequestHelper::post('aid','','intval');
        if (empty($aid) || $aid!=$this->aid) {
            $this->returnJsonMsg('2130',[],Common::C('code','2130'));
        }
        //步数
        $num = RequestHelper::post('num','','intval');
        if (empty($num)) {
            $this->returnJsonMsg('2132',[],Common::C('code','2132'));
        }
        //设备号
        $imei = RequestHelper::post('imei','','trim');
        if (empty($imei)) {
            $this->returnJsonMsg('2148',[],Common::C('code','2148'));
        }
        //设备号不匹配
        if(!$this->checkImei($aid,$imei)){
            $this->returnJsonMsg('2149',[],Common::C('code','2149'));
        }
        //当前是否已经在上传时间之内
        if(time()<$this->submit_start || time()>$this->submit_end){
            $this->returnJsonMsg('2133','',Common::C('code','2133'));
        }
        //上传成绩开始
        if(time()<strtotime($this->activity['submit_start'])){
            $this->returnJsonMsg('2138',[],Common::C('code','2138'));
        }
        //上传成绩结束
        if(time()>strtotime($this->activity['submit_end'])){
            $this->returnJsonMsg('2139',[],Common::C('code','2139'));
        }
        //是否选择暴走团
        if(!$this->checkTeamById($aid)){
            $this->returnJsonMsg('2146',[],Common::C('code','2146'));
        }
        
        $last_sub = WalkSubmit::find()->select('create_time')->where(['tdate'=>date('Y-m-d'),'mobile'=>$this->mobile,'aid'=>$aid,'status'=>1])->asArray()->one();
        //最后一次上传时间间隔不能小于20分钟
        if (!empty($last_sub) && strtotime($last_sub['create_time'])>strtotime("-{$this->submit_gap} seconds")) {
            $this->returnJsonMsg('2140',[],Common::C('code','2140').',时间间隔为'.ceil($this->submit_gap/60).'分钟');
        }

        //用户所在团队 mobile aid
        $team_id = WalkActivityPart::find()->select(['team_id'])->where(['aid'=>$aid,'mobile'=>$this->mobile,'is_team'=>1])->scalar();
        $team_id = empty($team_id)?'0':$team_id;

        $data = [
            'aid'         => $aid,
            'uid'         => $this->uid,
            'mobile'      => $this->mobile,
            'num'         => $num,
            'tdate'       => date('Y-m-d'),
            'status'      => 1,
            'district_id' => $this->district_id,
            'city_id'     => $this->city_id,
            'province_id' => $this->province_id,
            'community_id'       => $this->community_id,
            'communitygroups_id' => $this->communitygroups_id,
            'team_id'=>$team_id
        ];
        //上传成绩开启消息队列
        if ($this->submitwalk_off) {
            $res = $this->submitWalkChannel($data);
        } else {
            //修改之前上传的。规则：统计今天在规定时间内上传的最后一次步数（status=1）
            WalkSubmit::updateAll(['status'=>2],['tdate'=>date('Y-m-d'),'mobile'=>$this->mobile,'aid'=>$aid,'status'=>1]);
            //插入新成绩
            $walk_submit = new WalkSubmit();
            $res = $walk_submit->insertInfo($data);
        }
        if(empty($res)){
            $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        
        $this->returnJsonMsg('200',[],"已成功上传今日步数：{$num}步");
    }

    /**
     * 个人排行榜
     * @return array|mixed
     */
    public function actionPersionList()
    {
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        //个数
        $limit = RequestHelper::post('limit', 10, 'intval');

        //查询个人排行榜列表
        $model = WalkZrankPersion::find()->select(['nums','name','avatar','rank'])->where(['<=','rank',100]);

        $count = $model->count();
        $list = $model->orderBy('rank ASC,nums DESC')->offset(($page-1)*$limit)->limit($limit)->asArray()->all();
        foreach ($list as $key=>$val) {
            $val['nums'] = Common::formatNumber($val['nums'], 2, '万');
            $list[$key] = $val;
        }
        $data['list'] = $list;
        //分页
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($limit, true);
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        //分享排行链接
        $data['href'] = \Yii::$app->params['mHttpsUrl'].$this->murl_step;
        $data['item_null'] = 0;
        //查询当前用户用户步数
        $persion = WalkZrankPersion::find()->select(['nums','name','avatar','rank'])
                    ->where(['mobile'=>$this->mobile])
                    ->asArray()
                    ->one();

        if(empty($persion['name']) || empty($persion['avatar'])){
            //查询当前用户信息
            $user = UserBasicInfo::find()->select(['nickname','avatar'])->where(['mobile'=>$this->mobile])->asArray()->one();
            $persion['name'] = empty($user['nickname'])?'':$user['nickname'];
            $persion['avatar'] = empty($user['avatar'])?'':$user['avatar'];
        }

        $item  = [
            'nums'  =>  empty($persion['nums'])   ? '0' : Common::formatNumber($persion['nums'], 2, '万'),
            'name'  =>  empty($persion['name'])   ? '' : $persion['name'],
            'avatar'=>  empty($persion['avatar'])     ? '' : $persion['avatar'],
            'rank'  =>  empty($persion['rank'])   ? '0' : $persion['rank'],
            'is_canbao'  => $this->checkCanBaoById($this->aid) ? 1 : 0,
        ];

        //规则
        $data['rules'] = $this->rank_rules;
        $data['title'] = $this->rank_title;
        $data['item'] = $item;
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 团队步数榜
     * @return array
     */
    public function actionTeamStepList()
    {
        //分页
        $page = RequestHelper::post('page',1,'intval');
        $limit = RequestHelper::post('limit',10,'intval');

        //查询团队步数排行榜列表
        $model = WalkZrankTeamZou::find()->select(['nums','name','rank'])->where(['<=','rank',100]);

        $count = $model->count();
        $list = $model->orderBy('rank ASC,nums DESC')->offset(($page-1)*$limit)->limit($limit)->asArray()->all();
        foreach ($list as $key=>$val) {
            $val['nums'] = Common::formatNumber($val['nums'], 2, '万');
            $list[$key] = $val;
        }
        $data['list'] = $list;
        //分页
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($limit, true);
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        //分享排行链接
        $data['href'] = \Yii::$app->params['mHttpsUrl'].$this->murl_step;
        $data['item_null'] = 0;
        //查询当前所在的团队ID team_id
        $team_id = WalkActivityPart::find()->select(['team_id'])
                    ->where(['aid'=>$this->aid,'mobile'=>$this->mobile,'is_team'=>1])
                    ->asArray()
                    ->scalar();
        $team_id = empty($team_id)?'0':$team_id;

        //查询当前团队的步数
        $team_zou = WalkZrankTeamZou::find()->select(['nums','rank'])
                    ->where(['team_id'=>$team_id])
                    ->asArray()
                    ->one();

        //是否加入团队
        if(empty($team_id)){
            $data['item_null'] = 1;
            $name = '';
        } else {
            $name = WalkTeam::find()->select(['name'])->where(['id'=>$team_id])->asArray()->scalar();
            $name = empty($name)?'':$name;
        }

        $item = [
            'nums'=>empty($team_zou['nums'])?'0':Common::formatNumber($team_zou['nums'], 2, '万'),
            'name'=>$name,
            'rank'=>empty($team_zou['rank'])?'0':$team_zou['rank'],
        ];

        //规则
        $data['rules'] = $this->rank_rules;
        $data['title'] = $this->rank_title;
        $data['item'] = $item;
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 团队人数榜
     * @return array
     */
    public function actionTeamNumberList()
    {
        //分页
        $page = RequestHelper::post('page',1,'intval');
        $limit = RequestHelper::post('limit',10,'intval');

        //查询团队人数排行榜列表
        $model = WalkZrankTeamRen::find()->select(['nums','name','rank'])->where(['<=','rank',100]);

        $count = $model->count();
        $list = $model->orderBy('rank ASC,nums DESC')->offset(($page-1)*$limit)->limit($limit)->asArray()->all();
        foreach ($list as $key=> $val) {
            $val['nums'] = Common::formatNumber($val['nums'], 2, '万');
            $list[$key] = $val;
        }
        $data['list'] = $list;
        //分页
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($limit, true);
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        //分享排行链接
        $data['href'] = \Yii::$app->params['mHttpsUrl'].$this->murl_step;
        $data['item_null'] = 0;
        //查询当前所在的团队ID team_id
        $team_id = WalkActivityPart::find()->select(['team_id'])
            ->where(['aid'=>$this->aid,'mobile'=>$this->mobile,'is_team'=>1])
            ->asArray()
            ->scalar();
        $team_id = empty($team_id)?'0':$team_id;

        //查询当前团队的步数
        $team_ren = WalkZrankTeamRen::find()->select(['nums','rank'])
                    ->where(['team_id'=>$team_id])
                    ->asArray()
                    ->one();

        //是否加入团队
        if(empty($team_id)){
            $data['item_null'] = 1;
            $name = '';
        } else {
            $name = WalkTeam::find()->select(['name'])->where(['id'=>$team_id])->asArray()->scalar();
            $name = empty($name)?'':$name;
        }

        $item = [
            'nums'=> empty($team_ren['nums'])?'0':Common::formatNumber($team_ren['nums'], 2, '万'),
            'name'=> $name,
            'rank'=> empty($team_ren['rank'])?'0':$team_ren['rank'],
        ];

        //规则
        $data['rules'] = $this->rank_rules;
        $data['title'] = $this->rank_title;
        $data['item'] = $item;
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 小区排行榜
     * @return array|mixed
     */
    public function actionCommunityList()
    {
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        //个数
        $limit = RequestHelper::post('limit', 10, 'intval');

        //查询小区排行榜列表
        $model = WalkZrankCommunity::find()->select(['name','nums','rank'])->where(['<=','rank',30]);

        $count = $model->count();
        $list = $model->orderBy('rank ASC,nums DESC')->offset(($page-1)*$limit)->limit($limit)->asArray()->all();
        foreach ($list as $key=>$val) {
            $val['nums'] = Common::formatNumber($val['nums'], 2, '万');
            $list[$key] = $val;
        }
        $data['list'] = $list;
        //分页
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($limit, true);
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        //分享排行链接
        $data['href'] = \Yii::$app->params['mHttpsUrl'].$this->murl_step;
        $data['item_null'] = 0;

        //查询当前小区用户步数
        $community = WalkZrankCommunity::find()->select(['name','nums','rank'])
                    ->where(['community_id'=>$this->community_id])
                    ->asArray()
                    ->one();

        if(empty($community['name'])) {
            $community['name'] = Community::find()->select(['name'])->where(['id'=>$this->community_id])->asArray()->scalar();
        }

        $item = [
            'nums'=> empty($community['nums'])?'0':Common::formatNumber($community['nums'], 2, '万'),
            'name'=> empty($community['name'])?'':$community['name'],
            'rank'=> empty($community['rank'])?'0':$community['rank'],
        ];

        //规则
        $data['rules'] = $this->rank_rules;
        $data['title'] = $this->rank_title;
        $data['item'] = $item;
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 行政区排行榜
     * @return array|mixed
     */
    public function actionDistrictList()
    {
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        //个数
        $limit = RequestHelper::post('limit', 10, 'intval');

        //查询小区排行榜列表
        $model = WalkZrankDistrict::find()->select(['nums','name','rank'])->where(['<=','rank',13]);

        $count = $model->count();
        $list = $model->orderBy('rank ASC,nums DESC')->offset(($page-1)*$limit)->limit($limit)->asArray()->all();
        foreach ($list as $key=>$val) {
            $val['nums'] = Common::formatNumber($val['nums'], 2, '万');
            $list[$key] = $val;
        }
        $data['list'] = $list;
        //分页
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($limit, true);
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        //分享排行链接
        $data['href'] = \Yii::$app->params['mHttpsUrl'].$this->murl_step;
        $data['item_null'] = 0;

        //查询当前行政区用户步数
        $district = WalkZrankDistrict::find()->select(['name','nums','rank'])
                    ->where(['district_id'=>$this->district_id])
                    ->asArray()
                    ->one();

        if(empty($district['name'])) {
            $district['name'] = District::find()->select(['name'])->where(['id'=>$this->district_id])->asArray()->scalar();
        }

        $item = [
            'nums'=> empty($district['nums'])?'0':Common::formatNumber($district['nums'], 2, '万'),
            'name'=> empty($district['name'])?'':$district['name'],
            'rank'=> empty($district['rank'])?'0':$district['rank'],
        ];
        //规则
        $data['rules'] = $this->rank_rules;
        $data['title'] = $this->rank_title;
        $data['item'] = $item;
        $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
}