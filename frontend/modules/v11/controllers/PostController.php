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
namespace frontend\modules\v11\controllers;

use Yii;
use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\User;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ServiceCategory;
use yii\db\Query;
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
     * 邻居说首页帖子列表
     * @return array
     */
    public function actionPostList()
    {   
        $mobile = RequestHelper::post('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }

        $post_id = RequestHelper::post('post_id', '', '');
        $community_id = RequestHelper::post('community_id', 0, 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        //$community_city_id = RequestHelper::post('community_city_id', 0, 'intval');
        //if (empty($community_city_id)) {
        //    $this->returnJsonMsg('645', [], Common::C('code', '645'));
        //}

        $page = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '10', 'intval');
        $fields = ['id','mobile','forum_id','content','views','community_id','create_time','thumbs','share_num','video_url','video_time','video_views'];

        $orWhere = array();
        if(empty($post_id))
        {
            $wheres = ['community_id'=>$community_id,'status'=>2,'is_deleted'=>2,'is_public'=>0];
            $orWhere = ['status'=>2,'is_deleted'=>2,'is_public'=>1];
        }
        else
        {
            $wheres = ['id'=>$post_id];
        }
        $forum_id = RequestHelper::post('forum_id', 0, 'intval');
        if (!empty($forum_id)) {
            $wheres["forum_id"] = $forum_id;
            $orWhere["forum_id"] = $forum_id;
        }

        $query = Post::find()->select($fields)
                             ->where($wheres)
                             ->orWhere($orWhere)//可用，未删除，全部可见
                             ->with(['user'=>function($query) {
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
                    $field = array();
                    $field[] = 'i500_service_category.id';
                    $field[] = 'i500_service_category.name';
                    $field[] = 'i500_service_category.image';
                    $field[] = 'i500_service_category.description';
                    $field['post_num'] = (new Query())->select('count(id)')->from('i500_post')
                                        ->where(['forum_id'=>$v['forum_id'],'community_id'=>$community_id,'status'=>2,'is_deleted'=>2,'is_public'=>0])
                                        ->orWhere(['forum_id'=>$v['forum_id'],'status'=>2,'is_deleted'=>2,'is_public'=>1]);
                    $field['post_views'] = (new Query())->select('SUM(views)')->from('i500_post')
                                        ->where(['forum_id'=>$v['forum_id'],'community_id'=>$community_id,'status'=>2,'is_deleted'=>2,'is_public'=>0])
                                        ->orWhere(['forum_id'=>$v['forum_id'],'status'=>2,'is_deleted'=>2,'is_public'=>1]);
                    $category = ServiceCategory::find()->select($field)->where(['id'=>$v['forum_id']])->asArray()->one();
                    $data['item'][$k]['category'] = array();
                    if (!empty($category)) {
                        $data['item'][$k]['category']['name'] = $category['name'];
                        $data['item'][$k]['category']['id'] = $category['id'];
                        $data['item'][$k]['category']['image'] = $category['image'];
                        $data['item'][$k]['category']['description'] = $category['description'];
                        $data['item'][$k]['category']['post_num'] = $category['post_num'];
                        $data['item'][$k]['category']['post_views'] = $category['post_views'];
                    }else {
                        $data['item'][$k]['category']['name'] = "";
                        $data['item'][$k]['category']['id'] = "";
                        $data['item'][$k]['category']['image'] = "";
                        $data['item'][$k]['category']['description'] = '';
                        $data['item'][$k]['category']['post_num'] = "";
                        $data['item'][$k]['category']['post_views'] = "";
                    }
                }else {
                    $data['item'][$k]['category'] = array();
                    $data['item'][$k]['category']['name'] = "";
                    $data['item'][$k]['category']['id'] = "";
                    $data['item'][$k]['category']['image'] = "";
                    $data['item'][$k]['category']['description'] = '';
                    $data['item'][$k]['category']['post_num'] = "";
                    $data['item'][$k]['category']['post_views'] = "";
                }
                $data['item'][$k]['content'] = Common::userTextDecode(Common::sens_filter_word($data['item'][$k]['content'])) ;
                $comments = PostComments::find()->select(['*'])->where(['post_id'=>$v['id'],'status'=>1])->count();
                $data['item'][$k]['comments'] = $comments;
                if (empty($mobile)) {
                    $data['item'][$k]['is_thumbs'] = '0';
                } else {
                    $data['item'][$k]['is_thumbs'] = $this->_checkPostThumbs($mobile, $v['id']);
                }
                $list = PostComments::find()->select(['id','mobile','post_id','author_mobile','type','content','thumbs','create_time'])
                                            ->where(['post_id'=>$v['id'],'status'=>1])
                                            ->orderBy('create_time DESC')
                                            ->limit(3)
                                            ->asArray()
                                            ->all();
                foreach ($list as $kk => $vv) {
                    $list[$kk]['content'] = Common::sens_filter_word($list[$kk]['content']);
                    if (!empty($vv['mobile'])) {
                        $user_info = $this->_getUserInfo($vv['mobile']);//评论人信息
                        $list[$kk]['user_nickname'] = $user_info['nickname'];
                        $list[$kk]['user_avatar']   = $user_info['avatar'];
                        $author_user_info = $this->_getUserInfo($vv['author_mobile']);//被评论人信息
                        $list[$kk]['user_comment_nickname'] = $author_user_info['nickname'];
                    }
                    $list[$kk]['thumbs'] = Common::formatNumber($vv['thumbs']);
                }
                $data['item'][$k]['comments_list'] = $list;
            }
            $data['item'] = array_values($data['item']);
        }
        $data['count'] = $count;
        if(!empty($forum_id)){
            $field[] = 'i500_service_category.id';
            $field[] = 'i500_service_category.name';
            $field[] = 'i500_service_category.image';
            $field[] = 'i500_service_category.description';
            $field['post_num'] = (new Query())->select('count(id)')->from('i500_post')
                                ->where(['forum_id'=>$forum_id,'community_id'=>$community_id,'status'=>2,'is_deleted'=>2,'is_public'=>0])
                                ->orWhere(['forum_id'=>$forum_id,'status'=>2,'is_deleted'=>2,'is_public'=>1]);
            $field['post_views'] = (new Query())->select('SUM(views)')->from('i500_post')
                                ->where(['forum_id'=>$forum_id,'community_id'=>$community_id,'status'=>2,'is_deleted'=>2,'is_public'=>0])
                                ->orWhere(['forum_id'=>$forum_id,'status'=>2,'is_deleted'=>2,'is_public'=>1]);
            $category_info = ServiceCategory::find()->select($field)->where(['id'=>$forum_id])->asArray()->one();
            if(!empty($category_info)) {
                $category_info['post_views'] = empty($category_info['post_views']) ? 0 : $category_info['post_views'];
                $data['category'] =  $category_info;
            }else{
                $data['category'] = array('name'=>'','id'=>'','image'=>'','description'=>'','post_num'=>'','post_views'=>'');
            }
        }
        else
        {
            $data['category'] = array('name'=>'','id'=>'','image'=>'','description'=>'','post_num'=>'','post_views'=>'');
        }
        $data['pageCount']=$pages->pageCount;
        $this->result['code'] = '200';
        $this->result['data'] = $data;
        $this->result['message'] = Common::C('code', '200');
        return $this->response();
        //$this->returnJsonMsg('200', $data, Common::C('code', '200'));
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
     * 帖子评论列表
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
        $post_comment_where['post_id']    = $post_id;
        $post_comment_where['status']     = '1';
        $post_comment_fields = 'id,mobile,post_id,author_mobile,type,content,thumbs,create_time';
        $post_comment_model = new PostComments();

        $list = $post_comment_model->find()
                ->select($post_comment_fields)
                ->where($post_comment_where)
                ->orderBy('create_time DESC')
                ->limit(3)
                ->asArray()
                ->all();

        foreach ($list as $k => $v) {
            $list[$k]['content'] = Common::sens_filter_word($list[$k]['content']);
            if (!empty($v['mobile'])) {
                $user_info = $this->_getUserInfo($v['mobile']);//评论人信息
                $list[$k]['user_nickname'] = $user_info['nickname'];
                $list[$k]['user_avatar']   = $user_info['avatar'];
                $user_info1 = $this->_getUserInfo($v['author_mobile']);//被评论人信息
                $list[$k]['user_comment_nickname'] = $user_info1['nickname'];
            }
            $list[$k]['thumbs'] = Common::formatNumber($v['thumbs']);
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }
    /**
     * 邻居说用户帖子列表
     * @return array
     */
    public function actionUserPostlist()
    {
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        if (!empty($user_mobile)) {
            if (!Common::validateMobile($user_mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        $mobile = RequestHelper::post('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }

        $uid = RequestHelper::post('uid', '', '');
        if(!empty($uid)) {
            $user_mobile = User::find()->select(['mobile'])->where(['id'=>$uid])->scalar();
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile,'step'=>8])->asArray()->one();
        if (!$step) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $page      = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '10', 'intval');
        $fields = ['id','mobile','forum_id','content','views','community_id','create_time','thumbs','share_num','video_url','video_time','video_views'];
        $query = Post::find()->select($fields)->where(['mobile'=>$user_mobile,'status'=>2,'is_deleted'=>2])->with(['user'=>function($query) {
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
                    $field = array();
                    $field[] = 'i500_service_category.id';
                    $field[] = 'i500_service_category.name';
                    $field[] = 'i500_service_category.image';
                    $field['post_num'] = (new Query())->select('count(id)')->from('i500_post')
                                                      ->where('forum_id=i500_service_category.id')
                                                      ->andWhere(['status'=>2,'is_deleted'=>2,'community_id'=>$v['community_id'],'is_public'=>0])
                                                      ->orWhere(['status'=>2,'is_deleted'=>2,'is_public'=>1]);
                    $field['post_views'] = (new Query())->select('SUM(views)')->from('i500_post')
                                                        ->where('forum_id=i500_service_category.id')
                                                        ->andWhere(['status'=>2,'is_deleted'=>2,'community_id'=>$v['community_id'],'is_public'=>0])
                                                        ->orWhere(['status'=>2,'is_deleted'=>2,'is_public'=>1]);
                    $category = ServiceCategory::find()->select($field)->where(['id'=>$v['forum_id']])->asArray()->one();
        
                    $data['item'][$k]['category'] = array();
                    if (!empty($category)) {
                        $data['item'][$k]['category']['name'] = $category['name'];
                        $data['item'][$k]['category']['id'] = $category['id'];
                        $data['item'][$k]['category']['image'] = $category['image'];
                        $data['item'][$k]['category']['post_num'] = $category['post_num'];
                        $data['item'][$k]['category']['post_views'] = $category['post_views'];
                    }else {
                        $data['item'][$k]['category']['name'] = "";
                        $data['item'][$k]['category']['id'] = "";
                        $data['item'][$k]['category']['image'] = "";
                        $data['item'][$k]['category']['post_num'] = "";
                        $data['item'][$k]['category']['post_views'] = "";
                    }
                }else {
                    $data['item'][$k]['category'] = array();
                    $data['item'][$k]['category']['name'] = "";
                    $data['item'][$k]['category']['id'] = "";
                    $data['item'][$k]['category']['image'] = "";
                    $data['item'][$k]['category']['post_num'] = "";
                    $data['item'][$k]['category']['post_views'] = "";
                }
                $data['item'][$k]['content'] = Common::userTextDecode(Common::sens_filter_word($data['item'][$k]['content'])) ;
                $comments = PostComments::find()->select(['*'])->where(['post_id'=>$v['id'],'status'=>1])->count();
                $data['item'][$k]['comments'] = $comments;
                if (empty($mobile)) {
                    $data['item'][$k]['is_thumbs'] = '0';
                } else {
                    $data['item'][$k]['is_thumbs'] = $this->_checkPostThumbs($mobile, $v['id']);
                }
                $list = PostComments::find()->select(['id','mobile','post_id','author_mobile','type','content','thumbs','create_time'])
                                            ->where(['post_id'=>$v['id'],'status'=>1])
                                            ->orderBy('create_time DESC')
                                            ->limit(3)
                                            ->asArray()
                                            ->all();
                foreach ($list as $kk => $vv) {
                    $list[$kk]['content'] = Common::sens_filter_word($list[$kk]['content']);
                    if (!empty($vv['mobile'])) {
                        $user_info = $this->_getUserInfo($vv['mobile']);//评论人信息
                        $list[$kk]['user_nickname'] = $user_info['nickname'];
                        $list[$kk]['user_avatar']   = $user_info['avatar'];
                        $author_user_info = $this->_getUserInfo($vv['author_mobile']);//被评论人信息
                        $list[$kk]['user_comment_nickname'] = $author_user_info['nickname'];
                    }
                    $list[$kk]['thumbs'] = Common::formatNumber($vv['thumbs']);
                }
                $data['item'][$k]['comments_list'] = $list;
            }
            $data['item'] = array_values($data['item']);
        }
        $data['count'] = $count;
        $data['pageCount']=$pages->pageCount;
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
    }
    /**
     * 邻居说话题对应帖子
     * @return array
     */
    public function actionTopicPostlist()
    {   
        $mobile = RequestHelper::post('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile,'step'=>8])->asArray()->one();
        if (!$step) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }
		
		$community_id = UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$mobile])->scalar();

        $category_id = RequestHelper::post('category_id', '0', 'intval');
        if (empty($category_id)) {
            $this->returnJsonMsg('701', [], Common::C('code', '701'));
        }
        $page      = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '10', 'intval');
        $fields = ['id','mobile','forum_id','content','views','community_city_id','community_id','create_time','thumbs','share_num'];
        $query = Post::find()->select($fields)->where(['forum_id'=>$category_id,'status'=>2,'is_deleted'=>2,'community_id'=>$community_id])->with(['user'=>function($query) {
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
                $data['item'][$k]['content'] = Common::userTextDecode(Common::sens_filter_word($data['item'][$k]['content'])) ;
                $comments = PostComments::find()->select(['*'])->where(['post_id'=>$v['id']])->count();
                $data['item'][$k]['comments'] = $comments;
                if (empty($mobile)) {
                    $data['item'][$k]['is_thumbs'] = '0';
                } else {
                    $data['item'][$k]['is_thumbs'] = $this->_checkPostThumbs($mobile, $v['id']);
                }
            }
            $data['item'] = array_values($data['item']);
        }
        $data['count'] = $count;
        $data['pageCount']=$pages->pageCount;
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
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

        $step = User::find()->select(['step'])->where(['mobile'=>$mobile,'step'=>8])->asArray()->one();
        if (!$step) {
            return $this->returnJsonMsg('6001',[],'没有权限');
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

        try {
                $username = UserBasicInfo::find()->select(['nickname'])->where(['mobile'=>$mobile])->scalar();
                //获取要推送的channel_id
                $channel_id = User::find()->select('channel_id')->where(['mobile'=>$author_mobile])->scalar();
                if (!empty($channel_id))
                {
                    $channel = explode('-', $channel_id);
                    $data['device_type'] = ArrayHelper::getValue($channel, 0);
                    $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                    $data['type'] = 7;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论
                    $data['title'] = $username.'为您点赞';
                    $data['description'] = $username.'为您点赞';
                    $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                    $re = CurlHelper::post($channel_url, $data);
                    if ($re['code'] == 200) 
                    {
//                        $this->returnJsonMsg('200', [], Common::C('code', '200'));
                        $this->returnJsonMsg('200', $data, Common::C('code','200','data','[]'));	
                    } 
                }

                $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile'=>$author_mobile])->scalar();
                if(!empty($channel_id1))
                {
                    $channel1 = explode('-', $channel_id1);
                    $data1['device_type'] = ArrayHelper::getValue($channel1, 0);
                    $data1['channel_id'] = ArrayHelper::getValue($channel1, 1);
                    $data1['type'] = 7;//点赞  3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论
                    $data1['title'] = $username.'为您点赞';
                    $data1['description'] = $username.'为您点赞';
                    $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                    $re = CurlHelper::post($channel_url1, $data1);
                }
            } catch( \Exception $e) {}
        $this->returnJsonMsg('200', $rs_data, Common::C('code', '200'));
    }

        /**
     * 取消帖子点赞
     * @return array
     */
    public function actionCancelThumbsForPost()
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
        $post_model = new Post();
        $post_where['id']         = $post_id;
        $post_where['status']     = '2';
        $post_where['is_deleted'] = '2';
        $post_fields = 'id,thumbs';
        $post_rs = $post_model->getInfo($post_where, true, $post_fields);
        if (empty($post_rs)) {
            $this->returnJsonMsg('707', [], Common::C('code', '707'));
        }
        $post_thumbs = new PostThumbs();
        $post_thumbs_where['mobile']  = $mobile;
        $post_thumbs_where['post_id'] = $post_id;
        $post_thumbs_info = $post_thumbs->getInfo($post_thumbs_where, true, 'id');
        if (empty($post_thumbs_info)) {
            $this->returnJsonMsg('719', [], Common::C('code', '719'));
        }
        $rs = $this->_setPostNumber($post_id, $post_rs['thumbs']-1, '1');
        $post_thumbs_where['mobile']  = $mobile;
        $post_thumbs_where['post_id'] = $post_id;
        $del_rs = $post_thumbs->delOneRecord($post_thumbs_where);
        if ($del_rs['result'] != '1' || empty($rs)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $rs_data['thumbs'] = Common::formatNumber($post_rs['thumbs']-1);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', $rs_data, Common::C('code', '200'));
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
        return $post_model->updateInfo($post_update, $post_where);
    }


    /**
     * 判断当前用户帖子是否点赞
     * @param string $mobile  手机号
     * @param int    $post_id 帖子ID
     * @return int
     */
    private function _checkPostThumbs($mobile = '', $post_id = 0)
    {
        $post_thumbs_model = new PostThumbs();
        $post_thumbs_where['mobile']  = $mobile;
        $post_thumbs_where['post_id'] = $post_id;
        $post_thumbs_fields = 'id';
        $post_thumbs_info = $post_thumbs_model->getInfo($post_thumbs_where, true, $post_thumbs_fields);
        if (empty($post_thumbs_info)) {
            return '0';
        }
        return '1';
    }
    
  //热门邻居说
    public function actionTopicinnerList()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }

        $city_id = RequestHelper::post('community_city_id', '', '');
        $comm_id = RequestHelper::post('community_id', '', '');
        if (empty($comm_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $category_id = RequestHelper::post('category_id', '', '');
        if(empty($category_id))
        {
            $this->returnJsonMsg('5000',[], '话题id不能为空');    
        }   
        $post = new Post();
        $field=array();
        $field[]='i500_post.id';
        $field[]='i500_post.mobile';
        $field[]='i500_post.title';
        $field[]='i500_post.content';
        $field[]='i500_post.post_img';
        $field[]='i500_post.thumbs';     
        $field[]='i500_post.views';
        $field[]='i500_post.create_time';
        $field[]='i500_post.share_num';
        $field[]='i500_user_basic_info.nickname as nickname';
        $field[]='i500_user_basic_info.realname as realname';
        $field[]='i500_user_basic_info.avatar as avatar';
        $field[]='i500_user_basic_info.personal_sign as sign';
        //帖子评论数量，包括评论的回复
        $comments = (new Query())->select('count(id)')->from('i500_post_comments'); 
        $field['comments'] = $comments->where('post_id=i500_post.id AND status=1 ');
        
        $field['post_num'] = (new Query())->select('count(id)')->from('i500_post')->where('forum_id=i500_post.forum_id');
        $field['post_views'] = (new Query())->select('SUM(views)')->from('i500_post')->where('forum_id=i500_post.forum_id');
        $field['cate_name'] = (new Query())->select('name')->from('i500_service_category')->where('id=i500_post.forum_id')->offset(0)->limit(1);
        $field['image'] = (new Query())->select('image')->from('i500_service_category')->where('id=i500_post.forum_id')->offset(0)->limit(1);
        //邻居说点赞数量
//        $thumbs = (new Query())->select('count(id)')->from('i500_post_thumbs'); 
//        $field['total_thumbs'] = $thumbs->where('post_id=i500_post.id');
//        
        //当前用户是否已为该帖点赞
        $isthumb = (new Query())->select('count(id)')->from('i500_post_thumbs'); 
        $field['is_thumbs'] = $isthumb->where('post_id=i500_post.id')->andWhere(['mobile'=>$mobile]);
         
        if(!empty($city_id))
        {
            $condition[Post::tableName().'.community_city_id'] = $city_id;
        }
        if(!empty($comm_id))
        {
            $condition[Post::tableName().'.community_id'] = $comm_id;
        }
        $condition[Post::tableName().'.status'] = '2';
        $condition[Post::tableName().'.is_deleted'] = '2';
        if(!empty($category_id))
        {
            $condition[Post::tableName().'.forum_id'] = $category_id;
        }
        
        $hotlist = $post->find()->select($field)
                                ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_post.mobile')
                                ->andwhere($condition)
                                ->andWhere(['>','i500_post.thumbs','9'])
                                ->with(['photo'=>function ($query){$query->select(['id as pid','post_id','photo','width','height']);}])
                                ->orderBy('thumbs DESC')
                                ->offset(0)
                                ->limit(6)
                                ->asArray()
                                ->all();
                                
        //表情解码
        foreach($hotlist as $key=>$value)
        {
            $newcontent = Common::userTextDecode($value['content']);
            $hotlist[$key]['content']=str_replace($hotlist[$key]['content'], $newcontent,$value['content']);                              
        }
                                
          ////排除热度小于10的邻居说                      
          //        foreach($hotlist as $key=>$value)
//        {
//            if($value['total_thumbs']<10)
//            {
//                 unset($hotlist[$key]);
//            }
//        }
//      
        $josn_data = array();
        if(count($hotlist)==1)
        {
            $josn_data[] = $hotlist;
        }
        else
        {
            $josn_data = $hotlist;
        }
        
        $this->returnJsonMsg('200',$josn_data, Common::C('code','200','data','[]'));
    }

     /**
     * 我的邻居说数量
     * @return array
     * @author    duzongyan <duzongyan@i500m.com>
     * @link      duzongyan@i500m.com
     */
    public function actionUserPostnumber()
    {
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($user_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $step = User::find()->select(['step'])->where(['mobile'=>$user_mobile,'step'=>8])->asArray()->one();
        if (!$step) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }

        $fields = ['id'];
        $count = Post::find()->select($fields)->where(['mobile'=>$user_mobile,'status'=>2,'is_deleted'=>2])->count();
        $query['count'] = $count;
        $this->returnJsonMsg('200', $query, Common::C('code', '200'));
    }
    
    /**
     * 邻居说视频播放次数+1
     */
    public function actionVideoViewAdd() {
        $post_id = RequestHelper::post('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }
        
        $post_rs = Post::findOne(['id'=>$post_id]);
        if (empty($post_rs)) {
            $this->returnJsonMsg('707', [], Common::C('code', '707'));
        }
        $post_rs->video_views += 1;
        $post_rs->save();
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
}
