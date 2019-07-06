<?php
/**
 * 邻居说评论
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   ActivityComments
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @time      2017/2/27
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@i500m.com
 */
namespace frontend\modules\v9\controllers;

use Yii;
use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\ActivityComment;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ActivityCommentsPhoto;
use frontend\models\i500_social\Activity;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\PostPhoto;
use common\helpers\CurlHelper;
use common\helpers\FastDFSHelper;
use yii\helpers\ArrayHelper;
/**
 * Post
 *
 * @category Social
 * @package  ActivityComments
 * @author   xuxiaoyu <xuxiaoyu@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     xuxiaoyu@i500m.com
 */
class ActivityCommentsController extends BaseController
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
     * 新增活动详情评论
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
        $step = User::find()->select(['step'])->where(['mobile'=>$mobile])->scalar();
        if ((int)$step != 8) {
            return $this->returnJsonMsg('6001',[],'没有权限');
        }
        $activity_id = RequestHelper::post('activity_id', '0', 'intval');
        if (empty($activity_id)) {
            $this->returnJsonMsg('2000', [], '活动id不能为空');
        }
        $content = RequestHelper::post('content', '', ''); 
        if (empty($content)) {
            $this->returnJsonMsg('2001', [], '活动评论内容不能为空');
        }
        $synchro = RequestHelper::post('synchro', '', '');//$synchro=1;同步到邻居说帖子表;
        $activity_comment_model = new ActivityComment();
        $activity_comment_model -> mobile = $mobile;
        $activity_comment_model -> activity_id = $activity_id;
        $activity_comment_model -> content = $content;
        $activity_comment_model -> synchro = $synchro;

        $re = $activity_comment_model->save(false);
        //上传图片
        $user_info_model = new UserBasicInfo();
        $list = $user_info_model->find()
                ->select(['last_community_id'])
                ->where(['mobile'=>$mobile])
                ->asArray()
                ->one();            
        if($synchro == 1){
            $post_comment_model = new Post();
            $post_comment_model -> mobile = $mobile;
            $post_comment_model -> content = $content;
            $post_comment_model -> community_id = $list['last_community_id'];
            $res = $post_comment_model->save(false);
        }
        if (!empty($_FILES)) {
            $fastDfs = new FastDFSHelper();
            $keys = [
                'activity_comments_id',
                'mobile',
                'photo',
                'width',
                'height',
            ];
            $key = [
                'post_id',
                'mobile',
                'photo',
                'width',
                'height',
            ];
            file_put_contents('/tmp/uploader.log', "执行时间：" . date('Y-m-d H:i:s') . " 数据：" . var_export($_FILES, true) . "\n", FILE_APPEND);
            
            $commentsdata_img = $postdata_img = [];
            foreach ($_FILES as $k => $v) {
                $rs_data = $fastDfs->fdfs_upload($k);
                $size = [];
                if (!empty($v['name'])) {
                    $tmp = explode('.' ,$v['name']);
                    if (count($tmp) == 2) {
                        $size = explode('_' ,$tmp[0]);
                    }
                }
                $width = ArrayHelper::getValue($size, 0, 0);
                $height = ArrayHelper::getValue($size, 1, 0);
                if ($rs_data) {
                    $commentsdata_img[] = [
                        $activity_comment_model->id,
                        $mobile,
                        Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'],
                        $width,
                        $height
                    ];

                    if($synchro == 1){
                        if($res){
                            $aid = \Yii::$app->db_social->getLastInsertId();
                        }
                        $post_comment_model = new Post();
                        $postdata_img[] = [
                        $aid,
                        $mobile,
                        Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'],
                        $width,
                        $height
                    ];
                    }
                }
            }
            if (!empty($commentsdata_img)) {
                Yii::$app->db_social->createCommand()->batchInsert(ActivityCommentsPhoto::tableName(), $keys, $commentsdata_img)->execute();
            }
            if (!empty($postdata_img)) {
                Yii::$app->db_social->createCommand()->batchInsert(PostPhoto::tableName(), $key, $postdata_img)->execute();
            }
        }
        
        if (!$re) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }else{
            $user_mobile = Activity::find()->select(['mobile'])->where(['id'=>$activity_id])->scalar();
            $channel_id = User::find()->select('xg_channel_id')->where(['mobile'=>$user_mobile])->scalar();
            if(!empty($channel_id))
            {
                //活动评论id + 200
                $type_id = intval($activity_id) + 200;
                $channel = explode('-', $channel_id);
                $data['device_type'] = ArrayHelper::getValue($channel, 0);
                $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                $data['type'] = $type_id;//添加好友标识   3服务单 4需求单 5访客 6添加好友 7点赞互动 8评论 9加入社区 大于200 活动评论推送
                $data['title'] = '活动评论';
                $data['description'] = $content;
                $channel_url = \Yii::$app->params['channelHost'] . 'v1/xg-push/index';
                $re = CurlHelper::post($channel_url, $data);
            }

            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }
       
    }
    /**
     * 活动详情评论列表
     * @param int    $post_id 帖子ID
     * @param int    $type    类型 1=详情页调用
     * @return array
     */
    public function actionCommentsList()
    {

        $activity_id = RequestHelper::post('activity_id', '', '');
        if (empty($activity_id)) {
            $this->returnJsonMsg('2000', [], '活动id不能为空');
        }
        $page      = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $activity_comment_where['activity_id']    = $activity_id;
        $activity_comment_where['status']     = '2';
        $activity_comment_where['is_deleted']     = '2';
        $activity_comment_fields = 'id,mobile,activity_id,content,create_time';
        $activity_comment_model = new ActivityComment();

        $list = $activity_comment_model->find()
                                ->select($activity_comment_fields)
                                ->where($activity_comment_where)
                                ->with(['photo'=>function($query) {
                                      $query->select(['photo','activity_comments_id','width','height']);
                                      }])
                                ->orderBy('create_time DESC')
                                ->offset(($page-1) * $page_size)
                                ->limit($page_size)
                                ->asArray() 
                                ->all();
        foreach ($list as $k => $v) {
            $list[$k]['content'] = Common::sens_filter_word($list[$k]['content']);
            if (!empty($v['mobile'])) {
                $user_info = $this->_getUserInfo($v['mobile']);//评论人信息
                $list[$k]['user_nickname'] = $user_info['nickname'];
                $list[$k]['user_avatar']   = $user_info['avatar'];
            }
        }
        $number = count($list);
        $result['comments_list'] = $list;
        $result['comments_number'] = (string)$number;
        $this->returnJsonMsg('200', $result, Common::C('code', '200'));
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

}
?>