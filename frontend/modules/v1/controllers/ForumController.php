<?php
/**
 * 版块
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Forum
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/11
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\PostForum;
use frontend\models\i500_social\PostForumOther;

/**
 * Forum
 *
 * @category Social
 * @package  Forum
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ForumController extends BaseController
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
     * 获取置顶版块
     * @return array
     */
    public function actionGetTopForum()
    {
        $post_forum_model = new PostForum();
        $post_forum_where['status']     = '2';
        $post_forum_where['is_deleted'] = '2';
        $post_forum_where['pid']        = '0';
        $post_forum_fields = 'id,title';
        $rs = $post_forum_model->getList($post_forum_where, $post_forum_fields, 'sort desc');
        if (empty($rs)) {
            $this->returnJsonMsg('710', [], Common::C('code', '710'));
        }
        $this->returnJsonMsg('200', $rs, Common::C('code', '200'));
    }

    /**
     * 获取其他版块
     * @return array
     */
    public function actionGetOtherForum()
    {
        $pid = RequestHelper::get('pid', '0', 'intval');
        if (empty($pid)) {
            $this->returnJsonMsg('711', [], Common::C('code', '711'));
        }
        $rs['hot']   = $this->_getHotForum($pid);
        $rs['other'] = $this->_getOtherForum($pid);
        $this->returnJsonMsg('200', $rs, Common::C('code', '200'));
    }

    /**
     * 获取热门版块
     * @param int $pid 版块的父类ID
     * @return array
     */
    private function _getHotForum($pid = 0)
    {
        $post_forum_model = new PostForum();
        $post_forum_where['status']     = '2';
        $post_forum_where['is_deleted'] = '2';
        $post_forum_where['pid']        = $pid;
        $post_forum_where['hot']        = '1';
        $post_forum_fields = 'id,title,forum_img';
        $rs = $post_forum_model->getList($post_forum_where, $post_forum_fields, 'sort desc');
        if (!empty($rs)) {
            foreach ($rs as $k => $v) {
                if (!strstr($v['forum_img'], 'http')) {
                    $rs[$k]['forum_img'] = Common::C('imgHost').$v['forum_img'];
                }
            }
        }
        return $rs;
    }

    /**
     * 获取其他版块
     * @param int $pid 版块的父类ID
     * @return array
     */
    private function _getOtherForum($pid = 0)
    {
        $post_forum_model = new PostForum();
        $post_forum_where['status']     = '2';
        $post_forum_where['is_deleted'] = '2';
        $post_forum_where['pid']        = $pid;
        $post_forum_where['hot']        = '2';
        $post_forum_fields = 'id,title,forum_img,describe';
        $rs = $post_forum_model->getList($post_forum_where, $post_forum_fields, 'sort desc');
        if (!empty($rs)) {
            foreach ($rs as $k => $v) {
                $rs[$k]['forum_number'] = $this->_getForumNumber($v['id']);
                $rs[$k]['forum_number'] = Common::formatNumber($rs[$k]['forum_number']);
                if (!strstr($v['forum_img'], 'http')) {
                    $rs[$k]['forum_img'] = Common::C('imgHost').$v['forum_img'];
                }
            }
        }
        return $rs;
    }

    /**
     * 获取版块帖子数量
     * @param int $forum_id 版块ID
     * @return array
     */
    private function _getForumNumber($forum_id = 0)
    {
        $post_forum_other_model = new PostForumOther();
        $post_forum_other_where['forum_id'] = $forum_id;
        $post_forum_other_fields = 'forum_number';
        $rs = $post_forum_other_model->getInfo($post_forum_other_where, true, $post_forum_other_fields);
        return empty($rs['forum_number']) ? '0' : $rs['forum_number'] ;
    }
}
