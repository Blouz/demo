<?php
/**
 * 用户注册登录完善信息
 *
 * PHP Version 10
 *
 * @category  Social
 * @package   Service
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017/03/21
 * @copyright 2016 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */
namespace frontend\modules\v10\controllers;

use Yii;
use yii\db\Query;
use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\CurlHelper;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500m\Community;
use frontend\models\i500m\City;
use frontend\models\i500_social\Logincommunity;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\VerificationCode;
use frontend\models\i500_social\Identify;
use frontend\models\i500_social\User;
use frontend\models\i500_social\InviteCode;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\UserApplyCommunity;

class UserInfoPerfectedController extends BaseController
{
    /**
     * 注册时添加/更新用户信息
     * return array()
    **/
    public function actionIndex()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $userinfo = array();
        $code = array();
        //查询用户相关资料
        $user = User::find()->select(['i500_user.id','i500_user.step','i500_user_basic_info.id as info_id'])
                            ->join('left join','i500_user_basic_info','i500_user_basic_info.mobile=i500_user.mobile')
                            ->where(['i500_user.mobile'=>$mobile])
                            ->asArray()
                            ->one();

        $step = 0;//当前步骤
        $next_step = 0; //下一步
        $field = array(); //返回字段赋值
        $field1 = array(); //返回家庭信息字段赋值
        if($user['step']!=NULL) ///是否存在该帐户
        {
            if($user['info_id']==NULL) ///是否有该帐户相关信息 没有添加
            {
                $new_user_info = new UserBasicInfo();
                $new_user_info->mobile = $mobile;
                $new_user_info->save(false);
            }
            else  //有做修改
            {   
                $this->_checkstep($mobile,$this->get_step);
                switch ($this->get_step)
                {
                    case 1: 
                            $next_step = $this->step1($field);//真实姓名
                            break;
                    case 2: 
                            $next_step = $this->step2($field);//小区id
                            break;
                    case 3: 
                            $next_step = $this->step3($field);//小区id
                            break;        
                    case 4: 
                            $next_step =$this->step4($field);//地址
                            break;
                    case 5: 
                            $next_step = $this->step5($code);//认证码
                            break;
                    case 9: 
                            $next_step = $this->step9();//家庭成员信息
                            break;
                    default:
                            $this->returnJsonMsg('4003',[], '请输入当前有效步骤');
                }

                if(!empty($field))//修改信息不为空
                {  
                   $info_res = UserBasicInfo::updateAll($field,['mobile'=>$mobile]);
                   
                   $user_res = User::updateAll(['step'=>$next_step,'create_time'=>date('Y-m-d H:i:s',time())],['mobile'=>$mobile]);
                   if($user_res==1)
                   {
                       $this->_addident("addr",$mobile);//添加相应积分
                       $step = $next_step;
                   }
                }

                if(!empty($code))//验证码有效,认证成功
                {
                    $user_res = User::updateAll(['step'=>$next_step],['mobile'=>$mobile]);
                    if($user_res==1)
                    {
                        $step = $next_step;
                    }
                }
            }
        } 
       $data[]['step'] = (int)$step;
        $this->returnJsonMsg('200', $data, 'SUCCESS'); 

    }
    
    //完善步骤
    private function step1(&$field = array())
    {   
        $step = $this->get_step;
        $user_name = UserBasicInfo::find()->select(['realname'])->where(['mobile'=>$this->mobile])->scalar();
        if (!empty($this->realname)) {
            $field['nickname'] = $this->realname;
            $field['realname'] = $this->realname;
            $field['create_time'] = date('Y-m-d H:i:s',time());
        } else if (!empty($user_name)) {
            $field['nickname'] = $user_name;
            $field['realname'] = $user_name;
            $field['create_time'] = date('Y-m-d H:i:s',time());
        } else {
            $this->returnJsonMsg('4002',[], '姓名不能为空');
        }
        $next_step = (int)$step + 1;   
        return $next_step;
    }
    private function step2(&$field = array())
    {
        $step = $this->get_step;

        $field['province_id'] = $this->province_id;
        $field['city_id'] = $this->city_id;
        $field['district_id'] = $this->area_id;
        $field['create_time'] = date('Y-m-d H:i:s',time());
        if(!empty($this->community_id)) 
        {   
            $field['last_community_id'] = $this->community_id;                       	
        }
        else
        {
            $this->returnJsonMsg('4006',[], '社区id不能为空');
        }
        $next_step = (int)$step + 2;
        return $next_step;
    }

    private function step3(&$field = array())
    {
        $step = $this->get_step;
       
        $field['create_time'] = date('Y-m-d H:i:s',time());
        if(!empty($this->community_id)) 
        {   
            $field['last_community_id'] = $this->community_id;                          
        } else {
            $field['province_id'] = $this->province_id;                     
            $field['city_id'] = $this->city_id;
            $field['district_id'] = $this->area_id;
        }
        $next_step = (int)$step + 1;
        return $next_step;
    }

    private function step4(&$field = array())
    {
        $step = $this->get_step;

        if(!empty($this->address)) 
        {
            $field['address'] = $this->address;
            $next_step = (int)$step + 1;   
            return $next_step;
        }
        else
        {
            $this->returnJsonMsg('4007',[], '地址不能为空');
        }
    }
    private function step5(&$field = array())
    {
        //认证码验证
        $step = $this->get_step;

        if(!empty($this->num)) 
        {
            //获取用户所在小区
            $userBasicInfo = new UserBasicInfo();
            $community_id = $userBasicInfo->getInfo(['mobile'=>$this->mobile],true,['last_community_id']);
            $ident = Identify::find()->select(['community_id','expire_time','mobile'])
//                     ->where(['mobile'=> $this->mobile, 'num'=>$this->num])
                     ->where(['num'=>$this->num,'status'=>1])
                     ->andWhere(['<>','progress',3])
                     ->asArray()
                     ->one();

            $valid_time =date("Y-m-d H:i:s",strtotime($ident['expire_time']));
            $current_time =  date("Y-m-d H:i:s", time());
            
            if(empty($ident)) 
            {
                $this->returnJsonMsg('661', [], Common::C('code', '661'));
            }
            else
            {
                if (empty($ident['mobile'])) {
                    if($ident['community_id'] !== $community_id['last_community_id']){
                        $this->returnJsonMsg('669', [], Common::C('code', '669'));
                    }
                } else if($ident['mobile'] !== $this->mobile) {
                    $this->returnJsonMsg('670', [], Common::C('code', '670'));
                }

                if ($current_time > $valid_time) {
                    $this->returnJsonMsg('660', [], Common::C('code', '660'));
                } else {
                    $field[] = $ident;
                    //验证
                    $resu = User::updateAll(['is_verification_code'=> 1],['mobile'=>$this->mobile]);
                    //修改验证通过的
                    if(empty($ident['mobile'])){
                        $res = Identify::updateAll(['progress'=>3,'mobile'=>$this->mobile],['community_id'=>$community_id['last_community_id'],'num'=>$this->num]);
                    } else {
                        $res = Identify::updateAll(['progress'=>3],['mobile'=>$this->mobile]);
                    }
                    if($resu || $res)//验证通过
                    {
                        $step = (int)$step + 1;
                        
                    }
                    return $step;
                }
            }
        } else {
            $this->returnJsonMsg('668', [], Common::C('code', '668'));
        }
    }
    private function step9()
    {
        //家庭组验证
        $step = $this->get_step;
        $status = UserFriends::find()->select(['status'])->where(['uid'=>$this->mobile,'relation_status'=>2])->scalar();
        if ($status == "") {
            $user_info = $this->info();
            $new_family_info = new UserFriends();
            $new_family_info->uid = $this->mobile;
            $new_family_info->fid = $this->fmobile;
            $new_family_info->relation = $this->relation;
            $new_family_info->relation_status = 2;
            $new_family_info->status = 0;
            $new_family_info->create_time = date('Y-m-d H:i:s',time());
            $res = $new_family_info->save(false);
            $user_res = User::updateAll(['step'=>$step,'create_time'=>date('Y-m-d H:i:s',time())],['mobile'=>$this->mobile]);
            if($res && $user_res){
                $this->channel();
                $this->returnJsonMsg('4009',[], '请求已发送');    
            }
        } else if ($status == 0) {
            if (!empty($this->fmobile) && !empty($this->fname) && !empty($this->relation)) {
                $user_info = $this->info();
                $res = UserFriends::updateAll(['fid'=>$this->fmobile,'relation'=>$this->relation,'create_time'=>date('Y-m-d H:i:s',time())],['uid'=>$this->mobile,'relation_status'=>2]);
                if($res){
                    $this->channel();
                    $this->returnJsonMsg('4009',[], '请求已发送');
                }   
            }
            $this->returnJsonMsg('4010',[], '等待家人同意');
        }
    }

    public function info() {
        $user_info = User::find()->select(['i500_user.id','i500_user.step','i500_user_basic_info.id as info_id','i500_user_basic_info.province_id','i500_user_basic_info.city_id','i500_user_basic_info.district_id','i500_user_basic_info.last_community_id','i500_user_basic_info.address'])
                                ->join('left join','i500_user_basic_info','i500_user_basic_info.mobile=i500_user.mobile')
                                ->where(['i500_user.mobile'=>$this->fmobile])
                                ->andWhere(['i500_user.step'=>8])
                                ->andWhere(['i500_user_basic_info.realname'=>$this->fname])
                                ->asArray()
                                ->one();
        if (empty($user_info)) {
            $this->returnJsonMsg('4008',[], '家人未在社区');
        }       
        return $user_info;             
    }

    public function channel() {
        $userinfo = new UserBasicInfo();
        $username = $userinfo::find()->select(['nickname'])->where(['mobile'=>$this->mobile])->asArray()->one();
        //获取要推送的channel_id
        if (empty($this->fmobile)) {
            $this->fmobile = UserFriends::find()->select(['fid'])->where(['uid'=>$this->mobile,'relation_status'=>2])->scalar();
        }
        $channel_id = User::find()->select('xg_channel_id')->where(['mobile'=>$this->fmobile])->scalar();
        if(!empty($channel_id))
        {
            $channel = explode('-', $channel_id);
            $data['device_type'] = ArrayHelper::getValue($channel, 0);
            $data['channel_id'] = ArrayHelper::getValue($channel, 1);
            $data['type'] = 15;//添加好友标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区
            $data['title'] = $username['nickname'].'请求进入你的家庭组';
            $data['description'] = $username['nickname'].'请求进入你的家庭组';
            $channel_url = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
            $re = CurlHelper::post($channel_url, $data);
            if ($re) {
                return true;
            }
        } 
    }

    public function actionSend(){
        $channel_id = User::find()->select('xg_channel_id')->where(['mobile'=>$this->fmobile])->scalar();
        if (empty($channel_id)) {
            $this->returnJsonMsg('200',[], Common::C('code', '200'));
        } else {
            $res = $this->channel();
            if ($res) {
                $this->returnJsonMsg('200',[], Common::C('code', '200'));
            } else {
                $this->returnJsonMsg('401',[], Common::C('code', '401'));
            }
        } 
    }

    public function actionAdd(){
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        if (empty($this->province_id)) {
            $this->returnJsonMsg('4015',[], '省id不能为空');
        }
        if (empty($this->city_id)) {
            $this->returnJsonMsg('4016',[], '市id不能为空');
        } 
        if (empty($this->area_id)) {
            $this->returnJsonMsg('4017',[], '区域id不能为空');
        }
        if (empty($this->community_name)) {
            $this->returnJsonMsg('4018',[], '小区名称不能为空');
        }
        $count = UserApplyCommunity::find()->select(['id'])->where(['mobile'=>$mobile])->count();
        if ($count == 1) {
            $this->returnJsonMsg('4013',[], '用户已提交过小区');
        }

        $arr = Community::find()->select('*')
                                ->where(['name' => $this->community_name, 'province'=>$this->province_id, 'city'=>$this->city_id, 'district'=>$this->area_id])
                                ->asArray()
                                ->one();
        if(!empty($arr)) {
            $this->returnJsonMsg('4014',[], '用户提交小区已存在');
        }

        $city_name = City::find()->select(['name'])->where(['id'=>$this->city_id])->scalar();
        $link = "http://restapi.amap.com/v3/place/text?key=3ced5cece929533198add93dac2a62d3&keywords=".$this->community_name."&types=12&city=".$city_name."&citylimit=ture&output=JSON";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回    
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回  
        $r = curl_exec($ch);  
        curl_close($ch); 
        $json_data = json_decode($r,TRUE);  
        //var_dump($json_data);exit;
        $lng = '0.00000';
        $lat = '0.00000';
        if(isset($json_data['pois']['0']['location'])) {
            list($lng,$lat) = explode(',', $json_data['pois']['0']['location']);
        }
        $new_community = new Community();
        $new_community->name = $this->community_name;
        $new_community->province = $this->province_id;
        $new_community->city = $this->city_id;
        $new_community->district = $this->area_id;
        $new_community->lng = $lng;
        $new_community->lat = $lat;
        $re = $new_community->save();
        if($re){
            $cid = \Yii::$app->db_500m->getLastInsertId();
        }
        $new_user_community = new UserApplyCommunity();
        $new_user_community->cid = $cid;
        $new_user_community->mobile = $mobile;
        $new_user_community->community_name = $this->community_name;
        $new_user_community->province_id = $this->province_id;
        $new_user_community->city_id = $this->city_id;
        $new_user_community->district_id = $this->area_id;
        $res = $new_user_community->save();
        if ($res) {
            $url = \Yii::$app->params['channelHost'] . 'v1/community/edit-community?community_id=' . $cid;
            CurlHelper::get($url, 'channel');
            $field['province_id'] = $this->province_id;
            $field['city_id'] = $this->city_id;
            $field['district_id'] = $this->area_id;
            $field['last_community_id'] = $cid;                          
            $info_res = UserBasicInfo::updateAll($field,['mobile'=>$mobile]);
            if($info_res==1)
            {
               $user_res = User::updateAll(['step'=>4],['mobile'=>$mobile]);
               if($user_res==1)
               {
                   $this->_addident("addr",$mobile);//添加相应积分
               }
            }
            $data['community_id'] = $cid;
            $data['community_name'] = $this->community_name;

            $this->returnJsonMsg('4011',$data, '小区名称提交成功');
        } else {
            $this->returnJsonMsg('4012',[], '小区名称提交失败');
        }
    }

    private function _checkstep($mobile ,$get_step) {
        $user = UserBasicInfo::find()->select(['realname','province_id','city_id','district_id','last_community_id','address','is_verify'])->where(['mobile'=>$mobile])->asArray()->one();
        switch ($get_step) {
            case 2: 
                    if($user['realname'] == "")
                    $this->returnJsonMsg('4020',['user_step'=>1], '用户信息未完善');
                    break;
            case 3: 
                    if($user['realname'] == "")
                    $this->returnJsonMsg('4020',['user_step'=>1], '用户信息未完善');
                    break;        
            case 4: 
                    if($user['realname'] == "")
                     $this->returnJsonMsg('4020',['user_step'=>1], '用户信息未完善');

                    if(empty($user['last_community_id']) || empty($user['province_id']) || empty($user['city_id']) || empty($user['district_id']))
                     $this->returnJsonMsg('4020',['user_step'=>3], '用户信息未完善');
                    break;
            case 5: 
                    if($user['realname'] == "")
                    $this->returnJsonMsg('4020',['user_step'=>1], '用户信息未完善');
                    if(empty($user['last_community_id']) || empty($user['province_id']) || empty($user['city_id']) || empty($user['district_id']))
                    $this->returnJsonMsg('4020',['user_step'=>3], '用户信息未完善');
                    if($user['address'] == "")
                    $this->returnJsonMsg('4020',['user_step'=>4], '用户信息未完善');
                    break;
            case 9: 
                    if($user['realname'] == "")
                   $this->returnJsonMsg('4020',['user_step'=>1], '用户信息未完善');
                    break;
        }
    }
    
    /**
     * 注册时添加/更新用户信息  (明信片改为位置认证，app去掉家庭组页面)  2010822
    */
    public function actionIndexNew() {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        //认证状态
        $code = false;
        //查询用户相关资料
        $user = User::find()->select([
                    'i500_user.id','i500_user.step','i500_user_basic_info.id as info_id',
                    'realname','province_id','city_id','district_id','last_community_id','address','is_verify'
                ])
                ->join('left join','i500_user_basic_info','i500_user_basic_info.mobile=i500_user.mobile')
                ->where(['i500_user.mobile'=>$mobile])
                ->asArray()
                ->one();
        $step = empty($this->get_step) ? 0 : $this->get_step;//当前步骤
        $next_step = 0; //下一步
        $field = array(); //返回字段赋值
        ///是否存在该帐户
        if (empty($user)) {
           $this->returnJsonMsg('404', [], '用户不存在'); 
        }
        //是否有该帐户相关信息 没有则添加
        if (empty($user['info_id'])) {
            $new_user_info = new UserBasicInfo();
            $new_user_info->mobile = $mobile;
            $new_user_info->save(false);
        }
        $data = [];
        //根据当前step获取下一个值
        switch ($step) {
            //第一步，真实姓名
            case 1: 
                    if (!empty($this->realname)) {
                        $field['nickname'] = $this->realname;
                        $field['realname'] = $this->realname;
                        $field['create_time'] = date('Y-m-d H:i:s',time());
                    } else if (!empty($user['realname'])) {
                        $field['nickname'] = $user['realname'];
                        $field['realname'] = $user['realname'];
                        $field['create_time'] = date('Y-m-d H:i:s',time());
                    } else {
                        $this->returnJsonMsg('511',[], '姓名不能为空');
                    }
                    $next_step = 2;
                    break;
            //第二步，填写城市，填写小区
            case 2: 
                    if(empty($user['realname'])) {
                        $this->returnJsonMsg('4020',[array('step'=>1)], '用户信息未完善');
                    }
                    if(empty($this->province_id) || empty($this->city_id) || empty($this->area_id)) {
                        $this->returnJsonMsg('4020',[array('step'=>2)], '省市区未选择');
                    }
                    if(empty($this->community_id)) {
                        $this->returnJsonMsg('4006',['step'=>2], '社区id不能为空');
                    }
                    $field['province_id'] = $this->province_id;
                    $field['city_id'] = $this->city_id;
                    $field['district_id'] = $this->area_id;
                    $field['last_community_id'] = $this->community_id;
                    $field['create_time'] = date('Y-m-d H:i:s',time());
                    $next_step = 5;
                    //省市区 小区
                    $model = new Community();
                    $comm = $model->find()->select(['name','province','city','district'])->where(['id'=>$this->community_id])
                            ->with(['province'=>function($query){
                                $query->select(['name','id']);
                            }])->with(['city'=>function($query){
                                $query->select(['name','id']);
                            }])->with(['district'=>function($query){
                                $query->select(['name', 'id']);
                            }])->asArray()->one();
                    $data['ad_name'] = empty($comm['province']['name'])?'':$comm['province']['name'];
                    $data['ad_name'] .= empty($comm['city']['name'])?'':$comm['city']['name'];
                    $data['ad_name'] .= empty($comm['district']['name'])?'':$comm['district']['name'];
                    $data['ad_name'] .= empty($comm['name'])?'':$comm['name'];
                    break;
            //第四步，填写地址
            case 4: 
                    if(empty($this->address)) {
                        $this->returnJsonMsg('511',[], '地址不能为空');
                    }
                    $field['address'] = $this->address;
                    $next_step = 6;
                    break;
            //第三步，位置认证
            case 5: 
                    if(empty($user['last_community_id']) || empty($user['province_id']) || empty($user['city_id']) || empty($user['district_id'])) {
                        $this->returnJsonMsg('4020',[array('step'=>2)], '省市区或小区未选择');
                    }
                    //用户已认证需填写详细地址，应跳过一下判断
                    if ($user['step']==4) {
                        $step = 4;
                        break;
                    }
                    $lng1 = RequestHelper::post('lng', 0, 'floatval');
                    if (empty($lng1)) {
                        $this->returnJsonMsg('801', [], Common::C('code', '801'));
                    }
                    $lat1 = RequestHelper::post('lat', 0, 'floatval');
                    if (empty($lat1)) {
                        $this->returnJsonMsg('802', [], Common::C('code', '802'));
                    }
                    $coor = Community::find()->select(['lng', 'lat'])->where(['id'=>$user['last_community_id']])->asArray()->one();
                    if(empty($coor['lng']) || empty($coor['lat'])) {
                        $this->returnJsonMsg('404', [], '当前小区经纬度错误,请联系客服！');
                    }
                    $lng2 = $coor['lng'];
                    $lat2 = $coor['lat'];
                    //两经纬度距离
                    $calc = Common::getDistanceNew($lat1, $lng1, $lat2, $lng2);
                    //距离过大 2公里
                    if($calc < 0 || $calc > 2000) {
                        $err = array('calc'=>$calc,'lng2'=>$lng2,'lat2'=>$lat2);
                        $this->returnJsonMsg('820', [$err], '您当前位置与所填地址不符，等下再试？');
                    }
                    
                    $code = true;
                    $next_step = 4;
                    break;
            //家庭组
            case 9: 
                    $this->step9();//家庭成员信息
                    break;
            default:
                    $this->returnJsonMsg('4003',[], '请输入当前有效步骤');
        }
        //修改信息不为空
        if (!empty($field)) {
            //设置小区建设者
            /*
            if (!empty($field['last_community_id'])) {
                $pion_mobile = UserBasicInfo::find()->select(['mobile'])->where(['last_community_id' => $field['last_community_id'], 'is_pioneer' => '1'])->asArray()->one();
                if (empty($pion_mobile)) {
                    $field['is_pioneer'] = 1;
                }
            }
            */
            $info_res = UserBasicInfo::updateAll($field,['mobile'=>$mobile]);
            $user_res = User::updateAll(['step'=>$next_step,'create_time'=>date('Y-m-d H:i:s',time())],['mobile'=>$mobile]);
            if (!empty($info_res) && !empty($user_res)) {
                $this->_addident("addr",$mobile);//添加相应积分
                $step = $next_step;
            }
        }
        //跳过认证只改step
        if (!empty($code)) {
            $user_res = User::updateAll(['step'=>$next_step],['mobile'=>$mobile]);
            if (!empty($user_res)) {
                $step = $next_step;
            }
        }
        $data['step'] = (int)$step;
        $this->returnJsonMsg('200', [$data], 'SUCCESS'); 
    }
    
    /**
     * 自建小区 2010822
     */
    public function actionAddNew(){
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        if (empty($this->province_id)) {
            $this->returnJsonMsg('631',[], Common::C('code', '631'));
        }
        if (empty($this->city_id)) {
            $this->returnJsonMsg('632',[], Common::C('code', '632'));
        } 
        if (empty($this->area_id)) {
            $this->returnJsonMsg('633',[], Common::C('code', '633'));
        }
        if (empty($this->community_name)) {
            $this->returnJsonMsg('511',[], '小区名称不能为空');
        }
        //该用户自建小区个数
        $count = UserApplyCommunity::find()->select(['id'])->where(['mobile'=>$mobile])->count();
        if ($count>=3) {
            $this->returnJsonMsg('821',[], Common::C('code', '821'));
        }
        //用户已提交
        $arr = Community::find()->select('*')
               ->where(['name' => $this->community_name, 'province'=>$this->province_id, 'city'=>$this->city_id, 'district'=>$this->area_id])
               ->asArray()
               ->one();
        if(!empty($arr)) {
            $this->returnJsonMsg('822',[], Common::C('code', '822'));
        }
        $new_user_community = new UserApplyCommunity();
        $new_user_community->cid = 0;//为0时后台需要审核并添加到小区表，以便自建小区者选择
        $new_user_community->mobile = $mobile;
        $new_user_community->community_name = $this->community_name;
        $new_user_community->province_id = $this->province_id;
        $new_user_community->city_id = $this->city_id;
        $new_user_community->district_id = $this->area_id;
        $new_user_community->status = 0;//未审核
        $new_user_community->is_inform_user = 0;//未通知用户
        $res = $new_user_community->save();
        if ($res) {
            $data['community_name'] = $this->community_name;
            $this->returnJsonMsg('200',[$data], '小区名称提交成功');
        } else {
            $this->returnJsonMsg('400',[], '小区名称提交失败');
        }
    }
}

?>