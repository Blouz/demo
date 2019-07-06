<?php
/**
 * 首页
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Index
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/31
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\UserBasicInfo;

/**
 * Index
 *
 * @category Social
 * @package  Index
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class IndexController extends BaseController
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
     * 获取最新的5条帖子
     * @return array
     */
    public function actionIndex()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        $post_where['status']     = '2';
        $post_where['is_deleted'] = '2';
        $post_fields = 'id,mobile,forum_id,title,post_img,thumbs,views,create_time';
        $post_model = new Post();
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '5', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
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
}
