<?php
/**
 * 个人信息
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Profile
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/06
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v4\controllers;


use frontend\models\i500_social\User;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use common\helpers\SsdbHelper;
use common\helpers\HuanXinHelper;
use common\helpers\LoulianHelper;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\UserToken;
use frontend\models\i500_social\UserCoupons;
use frontend\models\i500_social\UserCommunity;
use frontend\models\i500_social\UserPushId;
use frontend\models\i500_social\UserFocusServiceCategory;
use yii\helpers\ArrayHelper;

/**
 * Profile
 *
 * @category Social
 * @package  Profile
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ProfileController extends BaseController
{
    public function afterAction($action,$result)
    {
        $this->response();
    }
    /**
     * 个人信息
     * @return array
     */
    public function actionIndex()
    {
        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->result['code'] = 604;
            $this->result['message'] = Common::C('code', '604');
            return;
        }
        if (!Common::validateMobile($mobile)) {
            $this->result['code'] = 605;
            $this->result['message'] = Common::C('code', '605');
            return;
        }


        $user_base_model = new UserBasicInfo();
        $user_base_where['mobile'] = $mobile;
        $user_base_fields = 'id,mobile,nickname,avatar,personal_sign,realname,sex,birthday,age,constellation,push_status';
        $user_base_info = $user_base_model->getInfo($user_base_where, true, $user_base_fields);
        if (empty($user_base_info)) {
            $this->result['code'] = 404;
            $this->result['message'] = '用户数据不合法';
        }


        if (!empty($user_base_info)) {
            if ($user_base_info['avatar']) {
                if (!strstr($user_base_info['avatar'], 'http')) {
                    $user_base_info['avatar'] = Common::C('imgHost').$user_base_info['avatar'];
                }
            } else {
                $user_base_info['avatar'] = Common::C('defaultAvatar');
            }
            //@todo 返回的身份证号码进行加*
            if (!empty($user_base_info['user_card'])) {
                $user_base_info['user_card'] = Common::hiddenUserCard($user_base_info['user_card']);
            }
        }
        $this->result['data'] = $user_base_info;
//        $this->returnJsonMsg('200', $user_base_info, Common::C('code', '200'));

    }

    /**
     * 修改用户信息
     */
    public function actionEdit()
    {
        $mobile = RequestHelper::post('mobile', 0);
        $model = UserBasicInfo::findOne(['mobile'=>$mobile]);
        if (!empty($model)) {
            $model->attributes = Yii::$app->request->post();

            if (!$model->save()) {
                $errors = $model->getFirstErrors();
                $error = array_values($errors);
                $this->result['code'] = 511;
                $this->result['message'] = ArrayHelper::getValue($error, 0, 'Error');
            }
        } else {
            $this->result['code'] = 404;
            $this->result['message'] = '用户不存在';
        }



    }
    /**
     * 设置小区
     * @return array
     */
    public function actionSetCommunity()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('422', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('422', [], Common::C('code', '605'));
        }
        $community_id = RequestHelper::post('community_id', '0', 'intval');
        if (empty($community_id)) {
            $this->returnJsonMsg('642', [], Common::C('code', '642'));
        }
        $community_name = RequestHelper::post('community_name', '');
        if (empty($community_name)) {
            $this->returnJsonMsg('422', [], '无效的小区名');
        }
        $community_city_id = RequestHelper::post('community_city_id', '0', 'intval');
        if (empty($community_city_id)) {
            $this->returnJsonMsg('645', [], Common::C('code', '645'));
        }
        $user_base_model = new UserBasicInfo();
        $user_base_where['mobile'] = $mobile;
        $user_base_fields = 'id,mobile';
        $user_base_info = $user_base_model->getInfo($user_base_where, true, $user_base_fields);

        /**编辑**/
        $user_base_update['last_community_city_id'] = $community_city_id;
        $user_base_update['last_community_id']      = $community_id;
        $user_base_update['community_name']      = $community_name;
        $update_rs = $user_base_model->updateInfo($user_base_update, $user_base_where);
        if (!$update_rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->_checkUserCommunity($mobile, $community_city_id, $community_id, $community_name);
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 设置推送ID
     * @return array
     */
    public function actionSetPushId()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        $push_id = RequestHelper::post('push_id', '', '');
        $dev = RequestHelper::post('dev', '0', 'intval');
        if (empty($mobile) || !Common::validateMobile($mobile) || empty($push_id) || !in_array($dev, [1, 2])) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的参数';
        }
        $model = User::findOne(['mobile'=>$mobile]);
        $user_base_update['last_push_id'] = $push_id;
        $model->channel_id = $dev.'-'.$push_id;
        $update_rs = $model->save();
        if (!$update_rs) {
            $this->returnJsonMsg('500', [], '服务器繁忙');
        }
    }

    /**
     * 获取小区
     * @return array
     */
    public function actionGetCommunity()
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
        $rs['last_community_id'] = '0';
        $user_base_model = new UserBasicInfo();
        $user_base_where['mobile'] = $mobile;
        $user_base_fields = 'last_community_id,last_community_city_id';
        $user_base_info = $user_base_model->getInfo($user_base_where, true, $user_base_fields);
        if (empty($user_base_info)) {
            $this->returnJsonMsg('200', $rs, Common::C('code', '200'));
        }
        $this->returnJsonMsg('200', $user_base_info, Common::C('code', '200'));
    }

    /**
     * 退出登陆
     * @return array
     */
    public function actionLogOut()
    {

        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile) || !Common::validateMobile($mobile)) {
            $this->returnJsonMsg('422', [], Common::C('code', '604'));
        }

        $user_model = new User();
        /**更新token**/
        $user_token_data['token']       = md5($mobile.time());
        $user_token_data['expired_in'] = time()-1;
        $user_token_data['xg_channel_id'] = 0;
        $rs = $user_model->updateInfo($user_token_data, ['mobile'=>$mobile]);

        if (empty($rs)) {
            $this->returnJsonMsg('500', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 优惠券
     * @return array
     */
    public function actionCoupons()
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
        $user_coupons_model = new UserCoupons();
        $user_coupons_where['mobile'] = $mobile;
        $user_coupons_fields = '
        id,
        type_name as name,
        par_value as amount,
        get_time as start_time,
        expired_time as end_time,
        status,remark';
        $info = $user_coupons_model->getList($user_coupons_where, $user_coupons_fields, 'id desc');
        foreach ($info as $k => $v) {
            if (strtotime($v['end_time']) < time()) {
                $info[$k]['status'] = '2';
            }
        }
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

    /**
     * 验证token是否过期
     * @return array
     */
    public function actionCheckToken()
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
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 关注服务分类
     * @return array
     */
    public function actionFocusServiceCategory()
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
        $categories = RequestHelper::post('categories', '', '');
        if (empty($categories)) {
            $this->returnJsonMsg('652', [], Common::C('code', '652'));
        }
        $user_focus_model = new UserFocusServiceCategory();
        $user_focus_where['uid']        = $uid;
        $user_focus_where['mobile']     = $mobile;
        $info = $user_focus_model->getInfo($user_focus_where, true, 'id');
        if (empty($info)) {
            /**新增**/
            $user_focus_where['categories'] = $categories;
            $rs = $user_focus_model->insertInfo($user_focus_where);
        } else {
            /**编辑**/
            $update_data['categories'] = $categories;
            $rs = $user_focus_model->updateInfo($update_data, $user_focus_where);
        }
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }

    /**
     * 关注服务分类列表
     * @return array
     */
    public function actionFocusServiceCategoryList()
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
        $user_focus_model = new UserFocusServiceCategory();
        $user_focus_where['uid']        = $uid;
        $user_focus_where['mobile']     = $mobile;
        $info = $user_focus_model->getInfo($user_focus_where, true, 'categories');
        if (empty($info)) {
            $this->returnJsonMsg('653', [], Common::C('code', '653'));
        }
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

    /**
     * 验证并更新用户小区
     * @param int    $uid               用户ID
     * @param string $mobile            手机号
     * @param int    $community_city_id 小区城市ID
     * @param int    $community_id      小区ID
     * @return bool
     */
    private function _checkUserCommunity($mobile = '', $community_city_id = 0, $community_id = 0, $community_name = '')
    {
        if (!empty($community_city_id) && !empty($community_id) && !empty($mobile)) {
            $user_community_model = new UserCommunity();
            $user_community_fields = 'id';
            $user_community_where['mobile']            = $mobile;
            $user_community_where['community_id']      = $community_id;
            $user_community_where['community_city_id'] = $community_city_id;
            $info = $user_community_model->getInfo($user_community_where, true, $user_community_fields);
            if (empty($info)) {
                /**执行添加**/
                $user_community_where['community_name'] = $community_name;
                $add_rs = $user_community_model->insertInfo($user_community_where);
                if (!$add_rs) {
                    return false;
                }
            }
        }
        return false;
    }
}
