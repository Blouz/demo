<?php
/**
 * 帖子
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
namespace frontend\modules\v6\controllers;

use Yii;
use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\PostCommentsThumbs;
use frontend\models\i500_social\PostContent;
use frontend\models\i500_social\PostForumOther;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\User;
use frontend\models\i500_social\ServiceCategory;
use common\helpers\CurlHelper;
use yii\helpers\ArrayHelper;
/**
 * Post
 *
 * @category Social
 * @package  Post
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
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
     * 邻居说话题
     * @return array
     */
    public function actionTopic()
    {
        $topicList = ServiceCategory::find()->select(['id','name','description','image'])->where(['is_deleted'=>2,'type'=>2,'status'=>2,'is_topic'=>1])->asArray()->all();
        foreach ($topicList as $k => $v) {
            $img_path = \Yii::$app->params['imgUrl'];
            $topicList[$k]['image'] = $img_path.$v['image'];
            $topicList[$k]['discussion_nm'] = "0";
            $topicList[$k]['views_nm'] = "0";
            $discussion_nm = Post::find()->select(['*'])->where(['forum_id'=>$v['id'],'status'=>2,'is_deleted'=>2])->count();
            if(!empty($discussion_nm)){
                $topicList[$k]['discussion_nm'] = $discussion_nm;
            }

            $connection = \Yii::$app->db_social;
            $command=$connection->createCommand("SELECT SUM(views) FROM `i500_post` where forum_id = ".$v['id']." AND status = 2 AND is_deleted =2");
            $views_nm =  $command->queryScalar();
            if(!empty($views_nm)){
                $topicList[$k]['views_nm'] = $views_nm;
            }
        }
        $this->returnJsonMsg('200', $topicList, Common::C('code', '200'));
    }


    /**
     * 邻居说首页帖子列表
     * @return array
     */
    public function actionPostlist()
    {
        $community_id = RequestHelper::get('community_id', 0, 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $community_city_id = RequestHelper::get('community_city_id', 0, 'intval');
        if (empty($community_city_id)) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }

        $page = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '10', 'intval');
        $fields = ['id','mobile','forum_id','content','views','community_city_id','community_id','create_time','thumbs'];

        $wheres = ['community_id'=>$community_id,'community_city_id'=>$community_city_id,'status'=>2,'is_deleted'=>2];
        $forum_id = RequestHelper::get('forum_id', 0, 'intval');
        if (!empty($forum_id)) {
            $wheres["forum_id"] = $forum_id;
        }

        $query = Post::find()->select($fields)->where($wheres)->with(['user'=>function($query) {
            $query->select(['nickname','avatar','mobile','sex']);
        }])->with(['photo'=>function($query) {
            $query->select(['photo','post_id','width','height']);
        }]);

        $count = $query->count();
        $post_list = $query->orderBy('create_time DESC')->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
        $data =  array();

        $pages=new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);
        $data['item'] = $post_list;

        if (!empty($data['item'])) {
            foreach ($data['item'] as $k => $v) {
                if ($v['forum_id'] != 0) {
                    $category = ServiceCategory::find()->select(['id','name'])->where(['id'=>$v['forum_id']])->one();
                    $data['item'][$k]['category'] = array();
                    if (!empty($category)) {
                        $data['item'][$k]['category']['name'] = $category['name'];
                        $data['item'][$k]['category']['id'] = $category['id'];
                    }
                }
                $data['item'][$k]['content'] = Common::sens_filter_word($data['item'][$k]['content']);
                $comments = PostComments::find()->select(['*'])->where(['post_id'=>$v['id']])->count();
                $data['item'][$k]['comments'] = $comments;
            }
            $data['item'] = array_values($data['item']);
        }
        $data['count'] = $count;
        $data['pageCount']=$pages->pageCount;
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
    }

    /**
     * 邻居说用户帖子列表
     * @return array
     */
    public function actionUserPostlist()
    {
        $mobile = RequestHelper::get('user_mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '10', 'intval');
        $fields = ['id','mobile','forum_id','content','views','community_city_id','community_id','create_time','thumbs'];
        $query = Post::find()->select($fields)->where(['mobile'=>$mobile,'status'=>2,'is_deleted'=>2])->with(['user'=>function($query) {
            $query->select(['nickname','avatar','mobile','sex']);
        }])->with(['photo'=>function($query) {
            $query->select(['photo','post_id','width','height']);
        }]);

        $count = $query->count();
        $post_list = $query->orderBy('create_time DESC')->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
        $data =  array();

        $pages=new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);
        $data['item'] = $post_list;

        if (!empty($data['item'])) {
            foreach ($data['item'] as $k => $v) {
                if ($v['forum_id'] != 0) {
                    $category = ServiceCategory::find()->select(['id','name'])->where(['id'=>$v['forum_id']])->one();
                    $data['item'][$k]['category'] = array();
                    if (!empty($category)) {
                        $data['item'][$k]['category']['name'] = $category['name'];
                        $data['item'][$k]['category']['id'] = $category['id'];
                    }
                }
                $data['item'][$k]['content'] = Common::sens_filter_word($data['item'][$k]['content']);
                $comments = PostComments::find()->select(['*'])->where(['post_id'=>$v['id']])->count();
                $data['item'][$k]['comments'] = $comments;
            }
            $data['item'] = array_values($data['item']);
        }
        $data['count'] = $count;
        $data['pageCount']=$pages->pageCount;
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
    }

    /**
     * 邻居说话题  详细
     * @return array
     */
    public function actionTopicInfo()
    {
        $category_id = RequestHelper::get('category_id', '0', 'intval');
		if (empty($category_id)) {
            $this->returnJsonMsg('701', [], Common::C('code', '701'));
        }
        $topicInfo = ServiceCategory::find()->select(['id','category_id','name','description','image'])->where(['id'=>$category_id,'status'=>2,'is_deleted'=>2,'type'=>2,'is_topic'=>1])->with(['category'=>function($query){
			$query->select(['name','image'])->where(['status'=>2,'is_deleted'=>2]);
		}])->asArray()->one();
        if(empty($topicInfo)){
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        $img_path = \Yii::$app->params['imgUrl'];
		if(empty($topicInfo['category'])){
			$topicInfo['category']['name'] = "";
			$topicInfo['category']['image'] = "";
		}else{
			//话题详情页分类入口 图片
			$topicInfo['category']['image'] =$img_path.$topicInfo['category']['image'];
		}
        $topicInfo['image'] = $img_path.$topicInfo['image'];
		$topicInfo['discussion_nm'] = "0";
        $topicInfo['views_nm'] = "0";
        $discussion_nm = Post::find()->select(['*'])->where(['forum_id'=>$topicInfo['id'],'status'=>2,'is_deleted'=>2])->count();
        if(!empty($discussion_nm)){
            $topicInfo['discussion_nm'] = $discussion_nm;
        }

        $connection = \Yii::$app->db_social;
        $command=$connection->createCommand("SELECT SUM(views) FROM `i500_post` where forum_id = ".$topicInfo['id']." AND status = 2 AND is_deleted =2");
        $views_nm =  $command->queryScalar();
        if(!empty($views_nm)){
            $topicInfo['views_nm'] = $views_nm;
        }
        $this->returnJsonMsg('200', $topicInfo, Common::C('code', '200'));
    }


    /**
     * 邻居说话题对应帖子
     * @return array
     */
    public function actionTopicPostlist()
    {
        $category_id = RequestHelper::get('category_id', '0', 'intval');
        if (empty($category_id)) {
            $this->returnJsonMsg('701', [], Common::C('code', '701'));
        }

        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '10', 'intval');
        $fields = ['id','mobile','forum_id','content','views','community_city_id','community_id','create_time','thumbs'];
        $query = Post::find()->select($fields)->where(['forum_id'=>$category_id,'status'=>2,'is_deleted'=>2])->with(['user'=>function($query) {
            $query->select(['nickname','avatar','mobile','sex']);
        }])->with(['photo'=>function($query) {
            $query->select(['photo','post_id','width','height']);
        }]);

        $count = $query->count();
        $post_list = $query->orderBy('top ASC,create_time DESC')->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
        $data =  array();

        $pages=new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);
        $data['item'] = $post_list;

        if (!empty($data['item'])) {
            foreach ($data['item'] as $k => $v) {
                if ($v['forum_id'] != 0) {
                    $category = ServiceCategory::find()->select(['id','name'])->where(['id'=>$v['forum_id']])->one();
                    $data['item'][$k]['category'] = array();
                    if (!empty($category)) {
                        $data['item'][$k]['category']['name'] = $category['name'];
                        $data['item'][$k]['category']['id'] = $category['id'];
                    }
                }
                $data['item'][$k]['content'] = Common::sens_filter_word($data['item'][$k]['content']);
                $comments = PostComments::find()->select(['*'])->where(['post_id'=>$v['id']])->count();
                $data['item'][$k]['comments'] = $comments;
            }
            $data['item'] = array_values($data['item']);
        }
        $data['count'] = $count;
        $data['pageCount']=$pages->pageCount;
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
    }
   

    /**
     * 帖子详情
     * @return array
     */
    public function actionDetails()
    {
        $post_id = RequestHelper::get('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }
        $fields = ['id','mobile','forum_id','content','views','community_city_id','community_id','create_time','thumbs'];
        $post_info =Post::find()->select($fields)->where(['id'=>$post_id,'status'=>2,'is_deleted'=>2])->with(['user'=>function($query) {
            $query->select(['nickname','avatar','mobile','sex']);
        }])->with(['photo'=>function($query) {
            $query->select(['photo','post_id','width','height']);
        }])->asArray()->one();

        $this->_setPostNumber($post_id, $post_info['views']+1, '2');
        if (empty($post_info)) {
            $this->returnJsonMsg('707', [], Common::C('code', '707'));
        }
        if ($post_info['forum_id'] != 0) {
            $category = ServiceCategory::find()->select(['id','name'])->where(['id'=>$post_info['forum_id']])->asArray()->one();
            $post_info['category'] = array();
            if (!empty($category)) {
                $post_info['category']['name'] = $category['name'];
                $post_info['category']['id'] = $category['id'];
            }
        }

        $post_info['content'] =  Common::userTextDecode(Common::sens_filter_word($post_info['content'])) ;
        $comments = PostComments::find()->select(['*'])->where(['post_id'=>$post_info['id']])->count();
        $post_info['comments'] = $comments;
	    $this->returnJsonMsg('200', $post_info, Common::C('code', '200'));
    }

    /**
     * 刪除帖子
     * @return array
     */
    public function actionDeletePost()
    {
        $post_id = RequestHelper::post('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }

        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $post_info =Post::find()->select('id')->where(['mobile'=>$mobile,'id'=>$post_id,'status'=>2,'is_deleted'=>2])->asArray()->one();
        if (empty($post_info)) {
            $this->returnJsonMsg('707', [], Common::C('code', '707'));
        }
        $post_model = new Post();
        $post_where['id'] = $post_id;
        $post_update['is_deleted'] = 1;
        $re = $post_model->updateAll($post_update, $post_where);
        if(!$re){
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }


    /**
     * 新增评论
     * @return array
     */
    public function actionComment()
    {
        $token = RequestHelper::post('token', '', '');
        if (empty($token)) {
            $this->returnJsonMsg('507', [], Common::C('code', '507'));
        }
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $author_mobile = RequestHelper::post('author_mobile', '', '');
        if (empty($author_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($author_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $post_id = RequestHelper::post('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }
        $content = RequestHelper::post('content', '', '');
        if (empty($content)) {
            $this->returnJsonMsg('715', [], Common::C('code', '715'));
        }
        $post_comment_model = new PostComments();
        $post_comment_model -> mobile = $mobile;
        $post_comment_model -> author_mobile = $author_mobile;
        $post_comment_model -> post_id = $post_id;
        $post_comment_model -> content = $content;
        $re = $post_comment_model->save(false);
        if (!$re) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 为帖子点赞
     * @return array
     */
    public function actionThumbsForPost()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $author_mobile = RequestHelper::post('author_mobile', '', '');
        if (empty($author_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($author_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $post_id = RequestHelper::post('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }

        $post_rs = Post::find()->select(['id','thumbs'])->where(['id'=>$post_id,'status'=>2,'is_deleted'=>2])->asArray()->one();
        if (empty($post_rs)) {
            $this->returnJsonMsg('707', [], Common::C('code', '707'));
        }
        $post_thumbs = new PostThumbs();
        $post_thumbs_where['mobile']  = $mobile;
        $post_thumbs_where['post_id'] = $post_id;
        $post_thumbs_info = $post_thumbs->getInfo($post_thumbs_where, true, 'id');
        if (!empty($post_thumbs_info)) {
            $this->returnJsonMsg('718', [], Common::C('code', '718'));
        }
        $rs = $this->_setPostNumber($post_id, $post_rs['thumbs']+1, '1');
        $post_thumbs_add_data['mobile']  = $mobile;
        $post_thumbs_add_data['author_mobile']  = $author_mobile;
        $post_thumbs_add_data['post_id'] = $post_id;
        $add_rs = $post_thumbs->insertInfo($post_thumbs_add_data);
        if (!$rs || !$add_rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $rs_data['thumbs'] = Common::formatNumber($post_rs['thumbs']+1);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        if($author_mobile!=$mobile) {
            $userinfo = UserBasicInfo::find()->select(['nickname'])->where(['mobile' => $mobile])->asArray()->one();
//            //获取要推送的channel_id
//            $channel_id = User::find()->select('channel_id')->where(['mobile' => $author_mobile])->scalar();
//            if (!empty($channel_id) && $author_mobile != $mobile) {
//                $channel = explode('-', $channel_id);
//                $data['device_type'] = ArrayHelper::getValue($channel, 0);
//                $data['channel_id'] = ArrayHelper::getValue($channel, 1);
//                $data['type'] = 7;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论
//                $data['title'] = $userinfo['nickname'] . '为您点赞';
//                $data['description'] = $userinfo['nickname'] . '为您点赞';
//                $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
//                $re = CurlHelper::post($channel_url, $data);
//                if ($re['code'] == 200) {
//                    $this->returnJsonMsg('200', $data, Common::C('code', '200', 'data', '[]'));
//                }
//            }

            $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile' => $author_mobile])->scalar();
            if (!empty($channel_id1)) {
                $channel1 = explode('-', $channel_id1);
                $data1['device_type'] = ArrayHelper::getValue($channel1, 0);
                $data1['channel_id'] = ArrayHelper::getValue($channel1, 1);
                $data1['type'] = 7;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论
                $data1['title'] = $userinfo['nickname'] . '为您点赞';
                $data1['description'] = $userinfo['nickname'] . '为您点赞';
                $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                $re = CurlHelper::post($channel_url1, $data1);
            }
        }
        $this->returnJsonMsg('200', $rs_data, Common::C('code', '200'));
    }


    /**
     * 帖子详情评论列表
     * @param string $mobile  手机号
     * @param int    $post_id 帖子ID
     * @param int    $type    类型 1=详情页调用
     * @return array
     */
    public function actionCommentsList()
    {

        $post_id = RequestHelper::get('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $post_comment_where['post_id']    = $post_id;
        $post_comment_where['status']     = '1';
        $post_comment_where['type'] = '1';
        $post_comment_fields = 'id,mobile,post_id,content,thumbs,create_time';
        $post_comment_model = new PostComments();
        //$list = $post_comment_model->getPageList($post_comment_where, $post_comment_fields, 'id asc', $page, $page_size);


        $list = $post_comment_model->find()
                ->select($post_comment_fields)
                ->where($post_comment_where)
                ->orderBy('create_time DESC')
                ->offset(($page-1) * $page_size)
                ->limit($page_size)
                ->asArray()
                ->all();
        
        foreach ($list as $k => $v) {

            $list[$k]['content'] = Common::sens_filter_word($list[$k]['content']);
            if (empty($mobile)) {
                $list[$k]['is_thumbs'] = '0';
            } else {
                $list[$k]['is_thumbs'] = $this->_checkCommentThumbs($mobile, $v['id']);
            }
            if (!empty($v['mobile'])) {
                $user_info = $this->_getUserInfo($v['mobile']);
                $list[$k]['user_nickname'] = $user_info['nickname'];
                $list[$k]['user_avatar']   = $user_info['avatar'];
            }
            $list[$k]['thumbs'] = Common::formatNumber($v['thumbs']);

        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 获取用户信息
     * @param string $mobile 电话
     * @return array
     */
    private function _getUserInfo($mobile = '')
    {
        $user_base_info_model = new UserBasicInfo();
        $user_base_info_where['mobile'] = $mobile;
        $user_base_info_fields = 'nickname,avatar';
        $rs['avatar']   = '';
        $rs['nickname'] = '';
        $rs = $user_base_info_model->getInfo($user_base_info_where, true, $user_base_info_fields);
        if (!empty($rs)) {
            if ($rs['avatar']) {
                if (!strstr($rs['avatar'], 'http')) {
                    $rs['avatar'] = Common::C('imgHost').$rs['avatar'];
                }
            }
        }
        return $rs;
    }

    /**
     * 设置帖子相关数量
     * @param int $post_id 帖子ID
     * @param int $num     相关数量
     * @param int $type    操作类型 1=点赞 2=查看
     * @return bool
     */
    private function _setPostNumber($post_id = 0, $num = 0, $type = 1)
    {
        $post_model = new Post();
        $post_where['id'] = $post_id;
        if ($type == '1') {
            /**点赞**/
            $post_update['thumbs'] = $num;
        } else {
            /**查看**/
            $post_update['views']  = $num;
        }
        $re = false;
        if ($post_where && $post_update) {
            $re = $post_model->updateAll($post_update, $post_where);
        }
        return $re !== false;
    }

    /**
     * 判断当前用户是否对这个评论点赞
     * @param string $mobile     手机号
     * @param int    $comment_id 评论ID
     * @return int
     */
    private function _checkCommentThumbs($mobile = '', $comment_id = 0)
    {
        $post_comment_thumbs_model = new PostCommentsThumbs();
        $post_comment_thumbs_where['mobile']  = $mobile;
        $post_comment_thumbs_where['comment_id'] = $comment_id;
        $post_comment_thumbs_fields = 'id';
        $post_comment_thumbs_info = $post_comment_thumbs_model->getInfo($post_comment_thumbs_where, true, $post_comment_thumbs_fields);
        if (empty($post_comment_thumbs_info)) {
            return '0';
        }
        return '1';
    }
}
