<?php
/**
 * 访客
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Post
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2016/8/9
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v9\controllers;

use Yii;
use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserVisitors;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\User;
use frontend\models\i500_social\IntegralLevel;
use common\helpers\CurlHelper;
use yii\helpers\ArrayHelper;
use yii\db\Query;
/**
 * Post
 *
 * @category Social
 * @package  Post
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class UservisitorsController extends BaseController
{
    /**
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
    * 添加访客
    * @param string $mobile  手机号
    * @param string $visitor_mobile  访客手机号
    * @return array
    */
    public function actionVisitor()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }  

        $visitor_mobile = RequestHelper::post('visitor_mobile', '', '');
        $user_id = RequestHelper::post('user_id', '', '');
        if (!empty($user_id)) {
           $visitor_mobile = User::find()->select(['mobile'])->where(['id'=>$user_id])->scalar();
        }

        if ($visitor_mobile != $mobile) {
            $visitor = UserVisitors::find()->select(['create_time'])
                       ->where(['mobile'=>$visitor_mobile,'visitor_mobile'=>$mobile])
                       ->andWhere(['between', 'create_time', date('Y-m-d'." 00:00:00"), date('Y-m-d'." 23:59:59")])
                       ->orderBy('create_time DESC')
                       ->asArray()
                       ->one();
            $create_time = date('Y-m-d H:i:s'); //当前时间           
            if (empty($visitor)) {
                $visitors = new UserVisitors();
                $visitors -> mobile = $visitor_mobile;
                $visitors -> visitor_mobile = $mobile;
                $visitors -> create_time = $create_time;
                $re = $visitors -> save();
                if (!$re) {
                    $this->returnJsonMsg('400', [], Common::C('code', '400'));
                } else {
                    //新访客做消息推送提示
                    if($visitor_mobile!=$mobile) {
                       $userinfo = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$mobile])->asArray()->one();
                        //获取要推送的channel_id
                        $channel_id = User::find()->select('channel_id')->where(['mobile'=>$visitor_mobile])->scalar();
                        // echo json_encode($channel_id);exit;
                        if(!empty($channel_id)) 
                        {
                            $channel = explode('-', $channel_id);
                            $data['device_type'] = ArrayHelper::getValue($channel, 0);
                            $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                            $data['type'] = 5;//新访客  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论
                            $data['title'] = $userinfo['nickname']."访问了您的空间";
                            $data['description'] = $userinfo['nickname']."访问了您的空间";
                            $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                            $re = CurlHelper::post($channel_url, $data);
                        }

                        $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile'=>$visitor_mobile])->scalar();
                        if(!empty($channel_id1))
                        {
                            $channel1 = explode('-', $channel_id1);
                            $data1['device_type'] = ArrayHelper::getValue($channel1, 0);
                            $data1['channel_id'] = ArrayHelper::getValue($channel1, 1);
                            $data1['type'] = 5;//新访客  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论
                            $data1['title'] = $userinfo['nickname']."访问了您的空间";
                            $data1['description'] = $userinfo['nickname']."访问了您的空间";
                            $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                            $re = CurlHelper::post($channel_url1, $data1);
                        }
                    }
                }
                $this->returnJsonMsg('200', [], Common::C('code', '200'));   
            } else {

                $this->returnJsonMsg('721', [], Common::C('code', '721'));
            }        
        }
    }    
    /**
     * 访客列表
     * @param string $mobile  手机号
     * @return array
     */
    public function actionVisitorslist()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page = RequestHelper::post('page', 1, 'intval');
        $size = RequestHelper::post('size', 10, 'intval');

        $read = UserVisitors::find()->select(['read'])->where(['mobile'=>$mobile])->column();
        if (in_array(0, $read)) {
            $res = UserVisitors::updateAll(['read'=>1],['mobile'=>$mobile,'read'=>0]);
            if (!$res) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
        }
        
        $data = [];
        $level = IntegralLevel::find()->select(['gradation','level_name'])->orderBy('gradation')->asArray()->all();
        $field[] = "visitor_mobile";
        $field[] = "create_time";
        $field['score'] = (new Query())->select('SUM(score)')->from("i500_integral")->where("mobile=visitor_mobile");
        $visitors_list = UserVisitors::find()->select($field)->where(['mobile'=>$mobile])
                        ->with(['user'=>function($query) { 
                            $query->select(['nickname','avatar','mobile','sex','personal_sign']);
                        }])
                        ->orderBy('create_time DESC')
                        ->offset(($page-1) * $size)
                        ->limit($size)
                        ->asArray()
                        ->all();    
                        
        if (empty($visitors_list)) {
            $this->returnJsonMsg('722', [], Common::C('code', '722'));
        } else {
            foreach ($visitors_list as $key =>$value) 
            {
                $data[$key]['visitor_mobile'] = $value['visitor_mobile'];
                $data[$key]['create_time'] = $value['create_time'];
                $data[$key]['user'] =  $value['user'];
                if(count($level)>0)
                {
                    for($i=0;$i<count($level);$i++)
                    {
                        if($value['score']>$level[$i]['gradation'])
                        {
                            continue;
                        }
                        else
                        {
                            $data[$key]['level_name'] = $level[$i]['level_name'];
                            break;
                        }
                    }
                }
                else
                {
                    $data[$key]['level_name'] = "0";
                }

            }
            
            $this->returnJsonMsg('200', $data, Common::C('code', '200'));
        }                                         
    }
    
    public function actionCountNewvisitor()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        $newvisitor = UserVisitors::find()->andWhere(['mobile'=>$mobile,'is_checked'=>0])->count('id');
        $this->returnJsonMsg('200', $newvisitor, Common::C('code', '200'));
    }
    public function actionVistorChecked()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $res = UserVisitors::updateAll(['is_checked'=>1],['mobile'=>$mobile]);
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
}