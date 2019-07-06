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
use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\User;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\PostCommentsThumbs;
use frontend\models\i500_social\PostCommentsPhoto;
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
     * 邻居说用户帖子列表
     * @return array
     */
    public function actionUserPostlist()
    {
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        if (empty($user_mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($user_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

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

        $page      = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '10', 'intval');
        $fields = ['id','mobile','forum_id','content','views','create_time','thumbs','share_num'];
        $query = Post::find()->select($fields)->where(['mobile'=>$user_mobile,'status'=>2,'is_deleted'=>2])->with(['user'=>function($query) {
                                    $query->select(['nickname','avatar','mobile','sex']);
                                }])->with(['photo'=>function($query) {
                                    $query->select(['photo','post_id','width','height']);
                                }])->with(['serviceCategory'=>function($query) {
                                    $query->select(['id','name']);
                                }])->with(['postComments'=>function($query) {
                                    $query->select("post_id, count('post_id') as num")->where(['status' => '1']);
                                }]);
        $count = $query->count();
        $post_list = $query->orderBy('create_time DESC')->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
        $data =  array();

        $pages=new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);
        $data['item'] = $post_list;

        if (!empty($data['item'])) {
            foreach ($data['item'] as $k => $v) {
                $data['item'][$k]['content'] = Common::userTextDecode(Common::sens_filter_word($data['item'][$k]['content'])) ;
                //$comments = PostComments::find()->select(['*'])->where(['post_id'=>$v['id'],'status'=>1])->count();
                //$data['item'][$k]['comments'] = $comments;
                //if (empty($mobile)) {
                //    $data['item'][$k]['is_thumbs'] = '0';
                //} else {
                $data['item'][$k]['is_thumbs'] = $this->_checkPostThumbs($mobile, $v['id']);
                //}
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
     * 邻居说话题
     * @return array
     */
    public function actionTopic()
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
        $topicList = ServiceCategory::find()->select(['id','name','description','image'])->where(['is_deleted'=>2,'type'=>2,'status'=>2,'is_topic'=>1])->asArray()->all();
        foreach ($topicList as $k => $v) {
            $img_path = \Yii::$app->params['imgUrl'];
            $topicList[$k]['image'] = $img_path.$v['image'];
            $topicList[$k]['discussion_nm'] = "0";
            $discussion_nm = Post::find()->select(['*'])->where(['forum_id'=>$v['id'],'status'=>2,'is_deleted'=>2,'community_id'=>$community_id])->count();
            if(!empty($discussion_nm)){
                $topicList[$k]['discussion_nm'] = $discussion_nm;
            }
        }
        $this->returnJsonMsg('200', $topicList, Common::C('code', '200'));
    }


     /**
     * 帖子评论列表
     * @param int    $post_id 帖子ID
     * @param int    $type    类型 1=详情页调用
     * @return array
     */
    public function actionCommentsList()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $post_id = RequestHelper::post('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }

        $page      = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '3', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        Post::updateAllCounters(['views'=>1], ['id'=>$post_id]);
        
        $post_comment_where['post_id']    = $post_id;
        $post_comment_where['status']     = '1';
        $post_comment_fields = 'id,mobile,post_id,author_mobile,type,content,thumbs,create_time';
        $post_comment_model = new PostComments();

        $list = $post_comment_model->find()
                ->select($post_comment_fields)
                ->with(['img'=>function($query) {
                    $query->select(['photo','comment_id']);
                }])
                ->where($post_comment_where)
                ->orderBy('id DESC')
                ->offset(($page-1) * $page_size)
                ->limit($page_size)
                ->asArray()
                ->all();

        foreach ($list as $k => $v) {
            $list[$k]['content'] = htmlspecialchars_decode(Common::sens_filter_word($list[$k]['content']));
            if (!empty($v['mobile'])) {
                $user_info = $this->_getUserInfo($v['mobile']);//评论人信息
                $list[$k]['user_nickname'] = $user_info['nickname'];
                $list[$k]['user_avatar']   = $user_info['avatar'];
                $user_info1 = $this->_getUserInfo($v['author_mobile']);//被评论人信息
                $list[$k]['user_comment_nickname'] = $user_info1['nickname'];
                $list[$k]['is_thumbs'] = $this->_checkCommentThumbs($mobile, $v['id']);//判断是否点赞1已点赞0未点赞
            }
            $list[$k]['thumbs'] = Common::formatNumber($v['thumbs']);
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

     /**
     * 为评论点赞
     * @return array
     */
    public function actionThumbsForComments()
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

        $comment_id = RequestHelper::post('comment_id', '0', 'intval');
        if (empty($comment_id)) {
            $this->returnJsonMsg('713', [], Common::C('code', '713'));
        }

        $comment_rs = PostComments::find()->select(['id','thumbs'])->where(['id'=>$comment_id,'status'=>1])->asArray()->one();
        if (empty($comment_rs)) {
            $this->returnJsonMsg('714', [], Common::C('code', '707'));
        }
        $comment_thumbs = new PostCommentsThumbs();
        $comment_thumbs_where['mobile']  = $mobile;
        $comment_thumbs_where['comment_id'] = $comment_id;
        $comment_thumbs_info = $comment_thumbs->getInfo($comment_thumbs_where, true, 'id');
        if (!empty($comment_thumbs_info)) {
            $this->returnJsonMsg('716', [], Common::C('code', '716'));
        }
        $rs = $this->_setCommentNumber($comment_id, $comment_rs['thumbs']+1);
        $comment_thumbs_add_data['mobile']  = $mobile;
        $comment_thumbs_add_data['author_mobile']  = $author_mobile;
        $comment_thumbs_add_data['comment_id'] = $comment_id;
        $add_rs = $comment_thumbs->insertInfo($comment_thumbs_add_data);
        if (!$rs || !$add_rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $rs_data['thumbs'] = Common::formatNumber($comment_rs['thumbs']+1);
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
     * 取消评论点赞
     * @return array
     */
    public function actionCancelThumbsForComments()
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

        $comment_id = RequestHelper::post('comment_id', '0', 'intval');
        if (empty($comment_id)) {
            $this->returnJsonMsg('713', [], Common::C('code', '713'));
        }
        $comment_model = new PostComments();
        $comment_where['id']         = $comment_id;
        $comment_where['status']     = '1';
        $comment_fields = 'id,thumbs';
        $comment_rs = $comment_model->getInfo($comment_where, true, $comment_fields);
        if (empty($comment_rs)) {
            $this->returnJsonMsg('714', [], Common::C('code', '714'));
        }
        $comment_thumbs = new PostCommentsThumbs();
        $comment_thumbs_where['mobile']  = $mobile;
        $comment_thumbs_where['comment_id'] = $comment_id;
        $comment_thumbs_info = $comment_thumbs->getInfo($comment_thumbs_where, true, 'id');
        if (empty($comment_thumbs_info)) {
            $this->returnJsonMsg('717', [], Common::C('code', '717'));
        }
        $rs = $this->_setCommentNumber($comment_id, $comment_rs['thumbs']-1);
        $comment_thumbs_where['mobile']  = $mobile;
        $comment_thumbs_where['comment_id'] = $comment_id;
        $del_rs = $comment_thumbs->delOneRecord($comment_thumbs_where);
        if ($del_rs['result'] != '1' || empty($rs)) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $rs_data['thumbs'] = Common::formatNumber($comment_rs['thumbs']-1);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', $rs_data, Common::C('code', '200'));
    }
    
    /**
     * 设置评论相关数量
     * @param int $comment_id 评论ID
     * @param int $num     相关数量
     * @return bool
     */
    private function _setCommentNumber($comment_id = 0, $num = 0)
    {
        $comment_model = new PostComments();
        $comment_where['id'] = $comment_id;
        /**点赞**/
        $comment_update['thumbs'] = $num;
        return $comment_model->updateInfo($comment_update, $comment_where);
    }

     /**
     * 判断当前用户评论是否点赞
     * @param string $mobile  手机号
     * @param int    $comment_id 评论ID
     * @return int
     */
    private function _checkCommentThumbs($mobile = '', $comment_id = 0)
    {
        $comment_thumbs_model = new PostCommentsThumbs();
        $comment_thumbs_where['mobile']  = $mobile;
        $comment_thumbs_where['comment_id'] = $comment_id;
        $comment_thumbs_fields = 'id';
        $comment_thumbs_info = $comment_thumbs_model->getInfo($comment_thumbs_where, true, $comment_thumbs_fields);
        if (empty($comment_thumbs_info)) {
            return '0';
        }
        return '1';
    }

     /**
     * 新增评论
     * @return array
     */
    public function actionComment()
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
        $content = RequestHelper::post('content', '', '');
        if (empty($content)) {
            $this->returnJsonMsg('715', [], Common::C('code', '715'));
        }
        $type = RequestHelper::post('type', '', '');
        if (empty($type)){
            $this->returnJsonMsg('720', [], Common::C('code', '720'));
        }
        $image = $_POST['image'];
        $img = json_decode($image, true);

        $post_comment_model = new PostComments();
        $post_comment_model -> mobile = $mobile;
        $post_comment_model -> author_mobile = $author_mobile;
        $post_comment_model -> post_id = $post_id;
        $post_comment_model -> content = $content;
        $post_comment_model -> type =$type;

        $re = $post_comment_model->save(false);
        if (!$re) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //推送回复消息给作者
        if($re)
        {   
            $cid = \Yii::$app->db_social->getLastInsertId();
            foreach ($img as $k => $v) {
                $photo_model = new PostCommentsPhoto();
                $photo_model->mobile = $mobile;
                $photo_model->comment_id = $cid;
                $photo_model->photo = $v;
                $res = $photo_model->save(false);
                if (!$re) {
                    $this->returnJsonMsg('403', [], Common::C('code', '403'));
                }
            }
            if($author_mobile!=$mobile)
            {
            try {
                    //获取要推送的channel_id
                    $channel_id = User::find()->select('channel_id')->where(['mobile'=>$author_mobile])->scalar();
                    if (!empty($channel_id))
                    {
                        $channel = explode('-', $channel_id);
                        $data['device_type'] = ArrayHelper::getValue($channel, 0);
                        $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                        $data['type'] = 8;//新增评论标识  2服务单 4需求单 5访客 6添加好友 7点赞 8评论
                        $data['title'] = '您有一个新的回复';
                        $data['description'] = '您有一个新的回复';
                        $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                        $re = CurlHelper::post($channel_url, $data);
                        if ($re['code'] == 200)
                        {
        //                                               file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 推送成功 订单数据：" . $data['order_sn'] . "\n", FILE_APPEND);
                            $this->returnJsonMsg('200', [], Common::C('code', '200'));
                        }
                    }

                    $channel_id1 = User::find()->select('xg_channel_id')->where(['mobile'=>$author_mobile])->scalar();
                    if(!empty($channel_id1))
                    {
                        $channel1 = explode('-', $channel_id1);
                        $data1['device_type'] = ArrayHelper::getValue($channel1, 0);
                        $data1['channel_id'] = ArrayHelper::getValue($channel1, 1);
                        $data1['type'] = 8;//新增评论标识  2服务单 4需求单 5访客 6添加好友 7点赞 8评论
                        $data1['title'] = '您有一个新的回复';
                        $data1['description'] = '您有一个新的回复';
                        $channel_url1 = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                        $re = CurlHelper::post($channel_url1, $data1);
                        if ($re['code'] == 200)
                        {
                            //                                               file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 推送成功 订单数据：" . $data['order_sn'] . "\n", FILE_APPEND);
                            $this->returnJsonMsg('200', [], Common::C('code', '200'));
                        }
                    }
                } catch( \Exception $e) {}
            }
         }
        
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    
}
