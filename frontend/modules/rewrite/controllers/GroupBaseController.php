<?php
/**
 * 用户基类
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   BASE
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2017/4/13
 * @copyright 辽宁i500科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */

namespace frontend\modules\rewrite\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use common\helpers\CurlHelper;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

/**
 * BASE
 *
 * @category Social
 * @package  BASE
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class GroupBaseController extends BaseController
{

    //登陆用户信息
    public $user_info = null;
    public $users = '';
    public $group_id = '';
    public $group_name = '';
    public $group_type_id = '';
    public $image = '';

    /**
     * 初始化
     * @return array
     */
    public function init()
    {
        parent::init();
        $this->users = isset($_POST['user_mobile']) ? json_decode($_POST['user_mobile'],true) : '' ;
        $this->group_name = isset($_POST['name']) ? Common::userTextEncode($_POST['name']) : '' ;
        $this->group_id = RequestHelper::post('group_id','','');
        $this->group_type_id = RequestHelper::post('group_type_id','','');
        $this->image = RequestHelper::post('image','','');
    }
}
