<?php
/**
 * App版本控制
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Version
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/9/11
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v11\controllers;

use frontend\models\i500m\AppLog;
use common\helpers\RequestHelper;

/**
 * App版本控制
 *
 * @category Social
 * @package  Version
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class VersionController extends BaseController
{
    /**
     * 版本更新
     * @return array
     */
    public function actionIndex()
    {
        $dev = RequestHelper::get('dev', '', '');
        $typeArr = array('1'=>'1','2'=>'0');
        if(!isset($typeArr[$dev])){
            $this->returnJsonMsg(100, [], 'SUCCESS');
        }
        $where['type'] = $typeArr[$dev];
        $where['status'] = 1;
        $model = new AppLog();
        $fields = 'name,major,explain,url,is_forced_to_update';
        $info = $model->getInfo($where, true, $fields, ['<=','update_prompt',date('Y-m-d H:i:s')], 'create_time desc');
        if (!empty($info)) {
            $info['version'] = $info['major'];
            unset($info['major']);
            $info['upgrade'] = $info['is_forced_to_update'];
            $array[] = $info;
            $this->returnJsonMsg(200, $array, '有新版本了');
        } else {
            $this->returnJsonMsg(100, [], 'SUCCESS');
        }
    }
}
