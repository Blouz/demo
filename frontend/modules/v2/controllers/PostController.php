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
namespace frontend\modules\v2\controllers;

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

/**
 * Post
 *
 * @category Social
 * @package  Post
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
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
     * 发布帖子
     * @return array
     */
    public function actionAdd()
    {
        $uid = RequestHelper::post('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $forum_id = RequestHelper::post('forum_id', '0', 'intval');
        if (empty($forum_id)) {
            $this->returnJsonMsg('701', [], Common::C('code', '701'));
        }
        $title = RequestHelper::post('title', '', '');
        if (empty($title)) {
            $this->returnJsonMsg('702', [], Common::C('code', '702'));
        }
        $post_img = RequestHelper::post('post_img', '', '');
        $content = RequestHelper::post('content', '', '');
        if (empty($content)) {
            $this->returnJsonMsg('703', [], Common::C('code', '703'));
        }
        $community_id = RequestHelper::post('community_id', '0', 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $community_city_id = RequestHelper::post('community_city_id', '0', 'intval');
        if (empty($community_city_id)) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        $post_model = new Post();
        $post_where['title'] = $title;
        $post_fields = 'id';
        $post_info = $post_model->getInfo($post_where, true, $post_fields);
        if (!empty($post_info)) {
            $this->returnJsonMsg('704', [], Common::C('code', '704'));
        }
        $post_add_data['uid']               = $uid;
        $post_add_data['mobile']            = $mobile;
        $post_add_data['forum_id']          = $forum_id;
        $post_add_data['title']             = $title;
        $post_add_data['post_img']          = $post_img;
        $post_add_data['community_id']      = $community_id;
        $post_add_data['community_city_id'] = $community_city_id;
        $rs = $post_model->insertOneRecord($post_add_data);
        if (!$rs || empty($rs['data']['new_id'])) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $post_content = new PostContent();
        $post_content_add_data['post_id'] = $rs['data']['new_id'];
        $post_content_add_data['content'] = $content;
        $rs = $post_content->insertInfo($post_content_add_data);
        /**当前版块帖子数+1**/
        $this->_setForumNumber($forum_id);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 别人动态-帖子列表
     * @return array
     */
    public function actionDynamic()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        $user_mobile = RequestHelper::get('user_mobile', '', '');
        if (!empty($user_mobile)) {
            if (!Common::validateMobile($user_mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
            $post_where['mobile'] = $user_mobile;
        }
        $community_id = RequestHelper::get('community_id', '0', 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $community_city_id = RequestHelper::get('community_city_id', '0', 'intval');
        if (empty($community_city_id)) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $post_where['community_id']      = $community_id;
        $post_where['community_city_id'] = $community_city_id;
        $post_where['status']            = '2';
        $post_where['is_deleted']        = '2';
        $post_fields = 'id,mobile,forum_id,title,post_img,thumbs,views,create_time';
        $post_model = new Post();
        $list = $post_model->getPageList($post_where, $post_fields, 'id desc', $page, $page_size);
        if (empty($list)) {
            $this->returnJsonMsg('708', [], Common::C('code', '708'));
        }
        foreach ($list as $k => $v) {
            $list[$k]['post_img'] = $this->_formatImg($v['post_img']);
            if (empty($mobile)) {
                $list[$k]['is_thumbs'] = '0';
            } else {
                $list[$k]['is_thumbs'] = $this->_checkPostThumbs($mobile, $v['id']);
            }
            if (!empty($v['mobile'])) {
                $user_info = $this->_getUserInfo($v['mobile']);
                $list[$k]['user_nickname'] = $user_info['nickname'];
                $list[$k]['user_avatar'] = $user_info['avatar'];
            }
            $list[$k]['thumbs']   = Common::formatNumber($v['thumbs']);
            $list[$k]['views']    = Common::formatNumber($v['views']);
            $list[$k]['time_ago'] = Common::timeAgo($v['create_time']);
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 帖子列表
     * @return array
     */
    public function actionList()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        $user_mobile = RequestHelper::get('user_mobile', '', '');
        if (!empty($user_mobile)) {
            if (!Common::validateMobile($user_mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
            $post_where['mobile'] = $mobile;
        }
        $forum_id = RequestHelper::get('forum_id', '0', 'intval');
        if (empty($forum_id)) {
            $this->returnJsonMsg('701', [], Common::C('code', '701'));
        }
        $community_id = RequestHelper::get('community_id', '0', 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $community_city_id = RequestHelper::get('community_city_id', '0', 'intval');
        if (empty($community_city_id)) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $post_where['forum_id']          = $forum_id;
        $post_where['community_id']      = $community_id;
        $post_where['community_city_id'] = $community_city_id;
        $post_where['status']            = '2';
        $post_where['is_deleted']        = '2';
        $post_fields = 'id,mobile,forum_id,title,post_img,thumbs,views,create_time';
        $post_model = new Post();
        $list = $post_model->getPageList($post_where, $post_fields, 'id desc', $page, $page_size);
        if (empty($list)) {
            $this->returnJsonMsg('708', [], Common::C('code', '708'));
        }
        foreach ($list as $k => $v) {
            $list[$k]['post_img'] = $this->_formatImg($v['post_img']);
            if (empty($mobile)) {
                $list[$k]['is_thumbs'] = '0';
            } else {
                $list[$k]['is_thumbs'] = $this->_checkPostThumbs($mobile, $v['id']);
            }
            if (!empty($v['mobile'])) {
                $user_info = $this->_getUserInfo($v['mobile']);
                $list[$k]['user_nickname'] = $user_info['nickname'];
                $list[$k]['user_avatar'] = $user_info['avatar'];
            }
            $list[$k]['thumbs'] = Common::formatNumber($v['thumbs']);
            $list[$k]['views']  = Common::formatNumber($v['views']);
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 帖子详情
     * @return array
     */
    public function actionDetails()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        $post_id = RequestHelper::get('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }
        $community_id = RequestHelper::get('community_id', '0', 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $community_city_id = RequestHelper::get('community_city_id', '0', 'intval');
        if (empty($community_city_id)) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        $post_fields = 'id,mobile,forum_id,title,post_img,thumbs,views,create_time';
        $post_where['id']                = $post_id;
        $post_where['community_id']      = $community_id;
        $post_where['community_city_id'] = $community_city_id;
        $post_where['status']            = '2';
        $post_where['is_deleted']        = '2';
        $post_model = new Post();
        $rs = $post_model->getInfo($post_where, true, $post_fields);
        if (empty($rs)) {
            $this->returnJsonMsg('707', [], Common::C('code', '707'));
        }
        $this->_setPostNumber($post_id, $rs['views']+1, '2');
        $rs['user_nickname'] = '';
        $rs['user_avatar']   = '';
        $rs['content']       = '';
        $rs['post_img']      = $this->_formatImg($rs['post_img']);
        if (empty($mobile)) {
            $rs['is_thumbs']     = '0';
        } else {
            $rs['is_thumbs']     = $this->_checkPostThumbs($mobile, $rs['id']);
        }
        if (!empty($rs['mobile'])) {
            $user_info = $this->_getUserInfo($rs['mobile']);
            $rs['user_nickname'] = $user_info['nickname'];
            $rs['user_avatar']   = $user_info['avatar'];
        }
        $rs['thumbs'] = Common::formatNumber($rs['thumbs']);
        $rs['views']  = Common::formatNumber($rs['views']);
        $post_info = $this->_getPostContent($post_id);
        $rs['content'] = $post_info['content'];
        $rs_arr['post_info'] = $rs;  //帖子信息
        $rs_comments_info = $this->actionCommentsList($mobile, $post_id, '1');
        $rs_arr['comments_info'] = $rs_comments_info;
        $this->returnJsonMsg('200', $rs_arr, Common::C('code', '200'));
    }

    /**
     * 新增评论
     * @return array
     */
    public function actionComment()
    {
        $uid = RequestHelper::post('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
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
        $content = RequestHelper::post('content', '', '');
        if (empty($content)) {
            $this->returnJsonMsg('715', [], Common::C('code', '715'));
        }
        $post_comment_model = new PostComments();
        $post_comment_add_data['uid']     = $uid;
        $post_comment_add_data['mobile']  = $mobile;
        $post_comment_add_data['post_id'] = $post_id;
        $post_comment_add_data['content'] = $content;
        $rs = $post_comment_model->insertInfo($post_comment_add_data);
        if (empty($rs)) {
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
        $uid = RequestHelper::get('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $post_id = RequestHelper::get('post_id', '0', 'intval');
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
        if (!empty($post_thumbs_info)) {
            $this->returnJsonMsg('718', [], Common::C('code', '718'));
        }
        $rs = $this->_setPostNumber($post_id, $post_rs['thumbs']+1, '1');
        $post_thumbs_add_data['uid']     = $uid;
        $post_thumbs_add_data['mobile']  = $mobile;
        $post_thumbs_add_data['post_id'] = $post_id;
        $add_rs = $post_thumbs->insertInfo($post_thumbs_add_data);
        if (!$rs || !$add_rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $rs_data['thumbs'] = Common::formatNumber($post_rs['thumbs']+1);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', $rs_data, Common::C('code', '200'));
    }

    /**
     * 取消帖子点赞
     * @return array
     */
    public function actionCancelThumbsForPost()
    {
        $uid = RequestHelper::get('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $post_id = RequestHelper::get('post_id', '0', 'intval');
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
     * 为评论点赞
     * @return array
     */
    public function actionThumbsForComments()
    {
        $uid = RequestHelper::get('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $comment_id = RequestHelper::get('comment_id', '0', 'intval');
        if (empty($comment_id)) {
            $this->returnJsonMsg('713', [], Common::C('code', '713'));
        }
        $post_comments_model = new PostComments();
        $post_comments_where['id']         = $comment_id;
        $post_comments_where['status']     = '2';
        $post_comments_where['is_deleted'] = '2';
        $post_comments_info = $post_comments_model->getInfo($post_comments_where, true, 'id,thumbs');
        if (empty($post_comments_info)) {
            $this->returnJsonMsg('714', [], Common::C('code', '714'));
        }
        $post_comments_thumbs_model = new PostCommentsThumbs();
        $post_comments_thumbs_where['mobile']     = $mobile;
        $post_comments_thumbs_where['comment_id'] = $comment_id;
        $post_comments_thumbs_info = $post_comments_thumbs_model->getInfo($post_comments_thumbs_where, true, 'id');
        if (!empty($post_comments_thumbs_info)) {
            $this->returnJsonMsg('716', [], Common::C('code', '716'));
        }
        $post_comments_update['thumbs'] = $post_comments_info['thumbs'] + 1;
        $rs = $post_comments_model->updateInfo($post_comments_update, $post_comments_where);
        $post_comments_thumbs_add_data['uid']        = $uid;
        $post_comments_thumbs_add_data['mobile']     = $mobile;
        $post_comments_thumbs_add_data['comment_id'] = $comment_id;
        $add_rs = $post_comments_thumbs_model->insertInfo($post_comments_thumbs_add_data);
        if (!$rs || !$add_rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $rs_data['thumbs'] = Common::formatNumber($post_comments_update['thumbs']);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', $rs_data, Common::C('code', '200'));
    }

    /**
     * 取消评论点赞
     * @return array
     */
    public function actionCancelThumbsForComments()
    {
        $uid = RequestHelper::get('uid', '', '');
        if (empty($uid)) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $comment_id = RequestHelper::get('comment_id', '0', 'intval');
        if (empty($comment_id)) {
            $this->returnJsonMsg('713', [], Common::C('code', '713'));
        }
        $post_comments_model = new PostComments();
        $post_comments_where['id']         = $comment_id;
        $post_comments_where['status']     = '2';
        $post_comments_where['is_deleted'] = '2';
        $post_comments_info = $post_comments_model->getInfo($post_comments_where, true, 'id,thumbs');
        if (empty($post_comments_info)) {
            $this->returnJsonMsg('714', [], Common::C('code', '714'));
        }
        $post_comments_thumbs_model = new PostCommentsThumbs();
        $post_comments_thumbs_where['mobile']     = $mobile;
        $post_comments_thumbs_where['comment_id'] = $comment_id;
        $post_comments_thumbs_info = $post_comments_thumbs_model->getInfo($post_comments_thumbs_where, true, 'id');
        if (empty($post_comments_thumbs_info)) {
            $this->returnJsonMsg('717', [], Common::C('code', '717'));
        }
        $post_comments_update['thumbs'] = $post_comments_info['thumbs'] - 1;
        $rs = $post_comments_model->updateInfo($post_comments_update, $post_comments_where);
        $post_comments_thumbs_where['mobile']     = $mobile;
        $post_comments_thumbs_where['comment_id'] = $comment_id;
        $del_rs = $post_comments_thumbs_model->delOneRecord($post_comments_thumbs_where);
        if (empty($rs) || $del_rs['result']!='1') {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $rs_data['thumbs'] = Common::formatNumber($post_comments_update['thumbs']);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$mobile]);
        $this->returnJsonMsg('200', $rs_data, Common::C('code', '200'));
    }

    /**
     * 评论列表
     * @param string $mobile  手机号
     * @param int    $post_id 帖子ID
     * @param int    $type    类型 1=详情页调用
     * @return array
     */
    public function actionCommentsList($mobile = '', $post_id = 0, $type = 0)
    {
        if (empty($post_id)) {
            $post_id = RequestHelper::get('post_id', '0', 'intval');
        }
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }
        if (empty($mobile)) {
            $mobile = RequestHelper::get('mobile', '0', 'intval');
        }
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $post_comment_where['post_id']    = $post_id;
        $post_comment_where['status']     = '2';
        $post_comment_where['is_deleted'] = '2';
        $post_comment_fields = 'id,mobile,post_id,content,thumbs,create_time';
        $post_comment_model = new PostComments();
        $list = $post_comment_model->getPageList($post_comment_where, $post_comment_fields, 'id asc', $page, $page_size);
        if (empty($list)) {
            if ($type == '1') {
                return [];
            } else {
                $this->returnJsonMsg('709', [], Common::C('code', '709'));
            }
        }
        foreach ($list as $k => $v) {
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
        if ($type == '1') {
            return $list;
        } else {
            $this->returnJsonMsg('200', $list, Common::C('code', '200'));
        }
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
     * 获取帖子详情
     * @param int $post_id 帖子ID
     * @return array
     */
    private function _getPostContent($post_id = 0)
    {
        $post_content_model = new PostContent();
        $post_content_where['post_id'] = $post_id;
        $post_content_fields = 'content';
        $rs = $post_content_model->getInfo($post_content_where, true, $post_content_fields);
        return $rs;
    }

    /**
     * 设置版块帖子数
     * @param int $forum_id 版块ID
     * @return bool
     */
    private function _setForumNumber($forum_id = 0)
    {
        $post_forum_other_model = new PostForumOther();
        $post_forum_other_where['forum_id'] = $forum_id;
        $post_forum_other_info = $post_forum_other_model->getInfo($post_forum_other_where, true, 'id,forum_number');
        if (!empty($post_forum_other_info)) {
            /**存在**/
            $post_forum_other_update['forum_number'] = $post_forum_other_info['forum_number'] +1;
            $rs = $post_forum_other_model->updateInfo($post_forum_other_update, $post_forum_other_where);
        } else {
            /**不存在**/
            $post_forum_other_add_data['forum_id']     = $forum_id;
            $post_forum_other_add_data['forum_number'] = '1';
            $rs = $post_forum_other_model->insertInfo($post_forum_other_add_data);
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
        return $post_model->updateInfo($post_update, $post_where);
    }

    /**
     * 格式化图片
     * @param string $img 图片
     * @return array
     */
    private function _formatImg($img = '')
    {
        $img_data = [];
        if (!empty($img)) {
            $img_arr = @explode(",", $img);
            foreach ($img_arr as $key => $value) {
                if (!empty($value)) {
                    if (!strstr($value, 'http')) {
                        $img_data[]= Common::C('imgHost').$value;
                    }
                }
            }
        }
        return $img_data;
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
