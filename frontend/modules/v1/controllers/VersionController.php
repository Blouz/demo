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
namespace frontend\modules\v1\controllers;

use frontend\models\i500m\AppLog;

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
        $model = new AppLog();
        $where['type'] = '0';
        $fields = 'name,major,explain,url,upgrade';
        $info = $model->getInfo($where, true, $fields, [], 'create_time desc');
        $info['version'] = $info['major'];
        unset($info['major']);
        if (!empty($info)) {
            $this->returnJsonMsg(200, $info, '有新版本了');
        } else {
            $this->returnJsonMsg(100, [], 'SUCCESS');
        }
    }
}
