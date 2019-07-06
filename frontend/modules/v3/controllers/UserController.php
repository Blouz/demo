<?php
/**
 * 用户相关
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-9 下午4:26
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v3\controllers;

use frontend\models\i500_social\UserLike;
use frontend\models\i500_social\UserToken;
use Yii;
use common\helpers\Common;
use common\helpers\SsdbHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\ServiceUnit;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\ServiceWeekTime;
use frontend\models\i500_social\UserBasicInfo;
use yii\helpers\ArrayHelper;

class UserController extends BaseController
{
    public $enableCsrfValidation= false;

    /**
     * 获取喜欢分类
     */
    public function actionGetLike()
    {
        $mobile = RequestHelper::get('mobile', 0);
        $category_model = new ServiceCategory();
        $cate_list = $category_model->getList(['status'=>2],['id', 'pid','name','image']);

        $model = new UserLike();
        $info = $model->getInfo(['mobile'=>$mobile]);
        //$category_item = [];
        //var_dump($info);exit();
        if (!empty($info) && !empty($info['category'])) {
            $category_item = json_decode($info['category'], true);
            //var_dump($category_item);exit();

            if (!empty($category_item) && is_array($category_item)) {
                $category_data = ArrayHelper::index($category_item, 'id');
            }

            //$category_data
        }
        foreach ($cate_list as $k => $v) {
            $cate_list[$k]['is_checked'] = 0;
            $cate_list[$k]['image'] = Common::formatImg($v['image']);
        }
        if (!empty($category_data)) {
            foreach ($cate_list as $k => $v) {
                if (isset($category_data[$v['id']])) {
                    $cate_list[$k]['is_checked'] = 1;
                } else {
                    $cate_list[$k]['is_checked'] = 0;
                }
                //$id = ArrayHelper::getValue($category_data, $v['id'].'.id', 0);
            }
        }
        $category_tree = $category_model->getChildList($cate_list);
        foreach ($category_tree as $k => $v) {
            if (count($v['child']) == 0) {
                unset($category_tree[$k]);
            }
        }
        $category_tree = array_values($category_tree);
        $this->returnJsonMsg(200, $category_tree, 'OK');
    }

    /**
     * 设置喜欢分类
     * @return array
     */
    public function actionSetLike()
    {
        $mobile = RequestHelper::post('mobile', 0);
        $category = RequestHelper::post('json_str', '');
        //$json = htmlspecialchars_decode($category);
        $json = json_decode(htmlspecialchars_decode($category), true);
        //var_dump($json);exit();
        $model = new UserLike();
        if (!empty($json)) {
            $map = ['mobile'=>$mobile];
            $info = $model->getInfo($map);
            if (!empty($info)) {
                $re = $model->updateInfo(['category'=>json_encode($json)], $map);
            } else {
                $data = [
                    'mobile'=>$mobile,
                    'category'=>json_encode($json),
                ];
               // var_dump($data);//exit();
                $re = $model->insertInfo($data);
            }
            if ($re == false) {
                $this->returnJsonMsg(400, [], Common::C('code', '400'));
            } else {
                $this->returnJsonMsg(200, [], 'OK');
            }


        } else {
            $this->returnJsonMsg(654, [], Common::C('code', '654'));
        }

    }

    /**
     * 验证token
     * 当用户每次打开应用的时候 如果登陆 则重置token
     */
    public function actionResetToken()
    {
        $mobile = RequestHelper::get('mobile', 0);
        /**更新token**/
        if (empty($mobile)) {
            $this->returnJsonMsg(100, [], '手机号必须!');
        }
        $user_token_model = new UserToken();
        $user_token_data['token']       = md5($mobile.time());
        $user_token_data['create_time'] = date('Y-m-d H:i:s', time());
        $re = $user_token_model->updateInfo($user_token_data, ['mobile'=>$mobile]);
        if ($re) {
            $this->returnJsonMsg(200, [], 'token更新成功!');
        } else {
            $this->returnJsonMsg(101, [], 'token更新失败!');
        }

    }
}