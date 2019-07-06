<?php
/**
 * 邻居说评论
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Post
 * @author    huangdekui <huangdekui@i500m.com>
 * @time      2016/8/9
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      huangdekui@i500m.com
 */
namespace frontend\modules\v11\controllers;

use Yii;
use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\PostCommentsPhoto;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Post;
use common\helpers\CurlHelper;
use yii\helpers\ArrayHelper;
use common\helpers\FastDFSHelper;

class PostcommentsController extends BaseController
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
    /* 
    * 新增评论
    * @author    wangleilei <wangleilei@i500m.com>
    * @time      2017
    * @copyright 2017 辽宁爱伍佰科技发展有限公司
    * @license   http://www.i500m.com license
    * @link      wangleilei@i500m.com
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
        $post_comment_model = new PostComments();
        $post_comment_model -> mobile = $mobile;
        $post_comment_model -> author_mobile = $author_mobile;
        $post_comment_model -> post_id = $post_id;
        $post_comment_model -> content = $content;
        $post_comment_model -> type = $type;

        $re = $post_comment_model->save(false);
        $comments_id = $post_comment_model->primaryKey;
        if (!empty($_FILES)) {
                $fastDfs = new FastDFSHelper();
                $keys = [
                    'comment_id',
                    'mobile',
                    'photo',
                    'width',
                    'height',
                ];

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
                            $comments_id,
                            $mobile,
                            Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'],
                            $width,
                            $height
                        ];
//                        $url[] = Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'];
                    }
                }
                if (!empty($data_img)) {
                   $res =  Yii::$app->db_social->createCommand()->batchInsert(PostCommentsPhoto::tableName(), $keys, $data_img)->execute();
                }
            }

          

        if (!$re) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //推送回复消息给作者
        if($re)
        {
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
    /**
     * 帖子详情评论列表
     * @param int    $post_id 帖子ID
     * @param int    $type    类型 1=详情页调用
     * @return array
     */
    public function actionCommentsList()
    {

        $post_id = RequestHelper::post('post_id', '0', 'intval');
        if (empty($post_id)) {
            $this->returnJsonMsg('706', [], Common::C('code', '706'));
        }
        $page      = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
		
	$mobile = Post::find()->select('mobile')->where(['id'=>$post_id,'status'=>2,'is_deleted'=>2])->scalar();

		
        $post_comment_where['post_id']    = $post_id;
        $post_comment_where['status']     = '1';
        $post_comment_fields = 'id,mobile,post_id,author_mobile,type,content,thumbs,create_time';
        $post_comment_model = new PostComments();

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
            if (!empty($v['mobile'])) {
                $user_info = $this->_getUserInfo($v['mobile']);//评论人信息
                $list[$k]['user_nickname'] = $user_info['nickname'];
                $list[$k]['user_avatar']   = $user_info['avatar'];
                $user_info1 = $this->_getUserInfo($v['author_mobile']);//被评论人信息
                $list[$k]['user_comment_nickname'] = $user_info1['nickname'];
            }
            $list[$k]['thumbs'] = Common::formatNumber($v['thumbs']);
        }
        //$this->result['code'] = '200';
        //$this->result['data'] = $list;
        //$this->result['message'] = Common::C('code', '200');
        //return $this->response();
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
}
?>