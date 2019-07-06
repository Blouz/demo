<?php
/**
 * 帖子
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Post
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/11
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v5\controllers;

use common\helpers\FastDFSHelper;
use frontend\controllers\RestController;
use frontend\models\i500_social\PostPhoto;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\PostCommentsThumbs;
use frontend\models\i500_social\PostContent;
use frontend\models\i500_social\PostForumOther;
use frontend\models\i500_social\UserBasicInfo;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

/**
 * Post
 *
 * @category Social
 * @package  Post
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class PostController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\Post';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['index'], $actions['update']);
        return $actions;
    }
    /**
     * 首页获取最新帖子
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $modelClass = $this->modelClass;
        $fields = ['id','mobile','forum_id','content','views','community_city_id','community_id','create_time'];
        $query = $modelClass::find()->select($fields)->with(['user'=>function($query) {
            $query->select(['nickname','avatar','mobile']);
        }])->with(['category'=>function($query) {
            $query->select(['name','id']);
        }])->with(['photo'=>function($query) {
            $query->select(['photo','post_id','width','height']);
        }]);
//        $query = $modelClass::find()->select($fields)->where($map);
        $data = $this->getPagedRows($query, ['order'=>'id desc']);
        if (!empty($data['item'])) {
            foreach ($data['item'] as $k => $v) {
                if (empty($v['category'])) {
//                    $data['item'][$k]['category'] = [
//                        'name'=>'官方',
//                        'id'=>108,
//                    ];
                    unset($data['item'][$k]);
                }

            }
            $data['item'] = array_values($data['item']);
        }
        $this->result['data'] = $data;
//        $model = $this->modelClass;
//        $provider = new ActiveDataProvider([
////            'query' => $model::find()->where(['pid'=>$pid]),
//            'query' => $model::find(),
//            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
//        ]);
        return $this->result;
    }
    /**
     * 发布帖子 //版块id 帖子内容 mobile content forum_id
     * @return array
     */
    public function actionCreate()
    {
        $model = new $this->modelClass;
//        $data = Yii::$app->request->post();
        $data = array();
        $data['mobile']=RequestHelper::post('mobile', '', '');
        $data['lng']=RequestHelper::post('lng', '0.000000', '');
        $data['lat']=RequestHelper::post('lat', '0.000000', '');
        $data['forum_id']=RequestHelper::post('forum_id', '', '');
        $data['content']= Common::userTextEncode(trim($_POST['content']));
        $data['community_city_id']=RequestHelper::post('community_city_id', '', '');
        $data['community_id']=RequestHelper::post('community_id', '', '');
        $video_url=RequestHelper::post('video_url', '', '');
        if(!empty($video_url)){
            $data['video_url'] =  Common::C('external').$video_url;
            $data['video_time'] =  RequestHelper::post('video_time',0, 'intval');
        }
        if (empty($data['content'])) {
            $this->result['code'] = 511;
            $this->result['message'] = '内容不能为空';
            return $this->result;
        }
        if(empty($data['forum_id']))
        {
            $data['forum_id'] = 108;
        }
//        var_dump($data);exit();
        $model->load($data, '');
        if (!$model->save(false)) {
            if ($model->hasErrors()) {
                return $model;
            } else {
                $this->result['code'] = 500;
                $this->result['message'] = '网络繁忙';
            }
        } else {
            //上传图片
//            if(!isset($data['video_url'])){
            if (!empty($_FILES)) {
                $fastDfs = new FastDFSHelper();
//                for ($_FILES; $i=0; $i++) {
//
//                }
                $keys = [
                    'post_id',
                    'mobile',
                    'photo',
                    'width',
                    'height',
                ];
                file_put_contents('/tmp/uploader.log', "执行时间：" . date('Y-m-d H:i:s') . " 数据：" . var_export($_FILES, true) . "\n", FILE_APPEND);

                foreach ($_FILES as $k => $v) {
                    $rs_data = $fastDfs->fdfs_upload($k);
                    $size = [];
                    if (!empty($v['name'])) {
                        $tmp = explode('.' ,$v['name']);
                        if (count($tmp) == 2) {
                            $size = explode('_' ,$tmp[0]);
//                            $size = explode('_' ,$tmp);

                        }
                    }
                    $width = ArrayHelper::getValue($size, 0, 0);
                    $height = ArrayHelper::getValue($size, 1, 0);

                    if ($rs_data) {
                        $data_img[] = [
                            $model->id,
                            $data['mobile'],
                            Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'],
                            $width,
                            $height
                        ];
//                        $url[] = Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'];
                    }
                }
                if (!empty($data_img)) {
                    file_put_contents('/tmp/uploader.log', "执行时间：" . date('Y-m-d H:i:s') . " 发布生活圈图片：" . var_export($data_img, true) . "\n", FILE_APPEND);
                    Yii::$app->db_social->createCommand()->batchInsert(PostPhoto::tableName(), $keys, $data_img)->execute();
                }
            }
//            }

        }
        return $this->result;

    }
    /**
     * 根据父分类id 获取子分类 默认为顶级分类
     */
    public function actionForum($forum_id)
    {
        $map = [];
        if (!empty($forum_id)) {
            $map['forum_id'] = $forum_id;
        }
        $modelClass = $this->modelClass;
        $fields = ['id','mobile','forum_id','content','views','community_city_id','community_id','create_time'];
        $query = $modelClass::find()->select($fields)->with(['user'=>function($query) {
                                        $query->select(['nickname','avatar','mobile']);
                                    }])->with(['category'=>function($query) {
                                        $query->select(['name','id']);
                                    }])->with(['photo'=>function($query) {
                                        $query->select(['photo','post_id','width','height']);
                                    }])->where($map);
//        $query = $modelClass::find()->select($fields)->where($map);
        $data = $this->getPagedRows($query, ['order'=>'id desc']);

        $this->result['data'] = $data;
        return $this->result;
    }
    /**
     * 新增评论
     * @return array
     */
    public function actionComments($post_id)
    {
        $query = PostComments::find()->select(['id','post_id','mobile','content','author_mobile','create_time'])
            ->with(['user'=>function($query) {
                $query->select(['mobile','nickname', 'avatar']);
            }])
            ->where(['post_id'=>$post_id, 'status'=>1]);
//            ->orderBy('id desc')->asArray()->all();

        $data = $this->getPagedRows($query, ['order'=>'id desc']);
        $this->result['data'] = $data;
        return $this->result;
    }



}
