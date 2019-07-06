<?php
/**
 * 帖子
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Post
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2017-03-21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v10\controllers;

use Yii;
use yii\db\Query;
use yii\data\Pagination;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\CurlHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\User;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserBasicInfo;
/**
 * Post
 *
 * @category Social
 * @package  Post
 * @author   yaoxin <yaoxin@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     yaoxin@i500m.com
 */
class PostTopicController extends BaseController
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
     * 邻居说话题
     * @return array
     */
    public function actionTopic()
    {

        $mobile = RequestHelper::post('mobile', '', '');

        $mobile = "15735830215";
     
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
        $community_id = UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$mobile])->scalar();

        $topicList = ServiceCategory::find()->select(['id','name','description','image'])
                                            ->with(['categorytopic'=>function($query) {
                                                $query->select(['id','pid','name','description','image'])->where(['is_deleted'=>2,'status'=>2,'type'=>2, 'is_topic'=>1]);
                                            }])
                                            ->where(['is_deleted'=>2,'status'=>2,'type'=>2, 'is_topic'=>1,'pid'=>0])
                                            ->orderBy('sort Desc')
                                            ->asArray()
                                            ->all();
        foreach ($topicList as $k => $v) {
            if(!empty($v['categorytopic'])) {
                foreach($v['categorytopic'] as $key => $value) {
                    $post = Post::find()->select('views,SUM(views) as read_volume')
                                        ->where(['forum_id'=>$value['id'],'status'=>2,'is_deleted'=>2,'community_id'=>$community_id,'is_public'=>0])
                                        ->orWhere(['status'=>2,'is_deleted'=>2,'is_public'=>1, 'forum_id'=>$value['id']])
                                        ->asArray()
                                        ->all();
                    $topicList[$k]['categorytopic'][$key]['read_volume'] = $post['0']['read_volume'];
                    if(empty($topicList[$k]['categorytopic'][$key]['read_volume'])) {
                        $topicList[$k]['categorytopic'][$key]['read_volume'] = 0;
                    }
                    $count = Post::find()->select(['id'])
                                         ->where(['forum_id'=>$value['id'],'status'=>2,'is_deleted'=>2,'community_id'=>$community_id,'is_public'=>0])
                                         ->orWhere(['status'=>2,'is_deleted'=>2,'is_public'=>1, 'forum_id'=>$value['id']])
                                         ->count();
                    $topicList[$k]['categorytopic'][$key]['count'] = $count;
                }
            }
        }
        $this->returnJsonMsg('200', $topicList, Common::C('code', '200'));
    }
}
