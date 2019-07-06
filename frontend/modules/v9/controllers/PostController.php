<?php
/**
 * 帖子
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Post
 * @author    duzongyan <duzongyan@i500m.com>
 * @time      2017/3/6
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      duzongyan@i500m.com
 */
namespace frontend\modules\v9\controllers;

use Yii;
use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\User;
use yii\db\Query;
use common\helpers\CurlHelper;
use yii\helpers\ArrayHelper;
/**
 * Post
 *
 * @category Social
 * @package  Post
 * @author   liuyanwei <duzongyan@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     duzongyan@i500m.com
 */
class PostController extends BaseController
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
     * 删除帖子
     * @return array
     */
    public function actionPostDeleted()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile,'step'=>8])->asArray()->one();
        if (!$step) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $post_id = RequestHelper::post('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        } 

        $post = Post::find()->select(['is_public'])->where(['id'=>$post_id])->scalar();
        if((int)$post == 1) {
            $this->returnJsonMsg('742', [], Common::C('code', '742'));
        }
        
        $result = UserBasicInfo::find()->select(['is_pioneer'])->where(['mobile'=>$mobile])->asArray()->one();
        if ($result['is_pioneer'] == 1 || $result['is_pioneer'] == 2) {
            $data = Post::updateAll(['is_deleted'=>1],['id'=>$post_id]);
            if (!$data) {
                return $this->returnJsonMsg('500',[],'网络繁忙');
            }
        } else {
           return $this->returnJsonMsg('741',[],Common::C('code', '741'));
        }
        $this->returnJsonMsg('200',[], Common::C('code', '200'));
    }
}
