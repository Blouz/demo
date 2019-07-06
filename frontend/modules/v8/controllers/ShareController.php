<?php
/**
 * 分享
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    huangdekui <huangdekui@i500m.com>
 * @time      17/02/25
 * @copyright 2017 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      huangdekui@i500m.com
 */
namespace frontend\modules\v8\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\ShareLog;

class ShareController extends BaseController
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
     * 分享数量及记录
     * @param string  $mobile         电话
     * @param string  $user_mobile    被分享人手机号
     * @param string  $post_id        帖子ID
     * @return json
 	 * @author huangdekui <huangdekui@i500m.com>
     */
    public function actionIndex()
    {   
        $mobile = RequestHelper::post('mobile','','');
        if(empty($mobile)){
            $this->returnJsonMsg('604',[],Common::C('code','604'));
        }
        if(!Common::validateMobile($mobile)){
            $this->returnJsonMsg('605',[],Common::C('code','605'));
        }

        $user_mobile = RequestHelper::post('user_mobile','','');
        if(empty($user_mobile)){
            $this->returnJsonMsg('604',[],Common::C('code','604'));
        }

        $post_id = RequestHelper::post('post_id','0','intval');
        if(empty($post_id)){
            $this->returnJsonMsg('706',[],Common::C('code','706'));
        }

        $res = Post::updateAllCounters(['share_num'=>+1],['id'=>$post_id,'mobile'=>$user_mobile]);
        if($res){
            $shareLog = new ShareLog();
            $shareLog -> mobile = $mobile;
            $shareLog -> user_mobile = $user_mobile;
            $shareLog -> post_id = $post_id;
            $res1 = $shareLog ->save();
        }
        $this->returnJsonMsg('200', [] , Common::C('code', '200'));
    }
}
