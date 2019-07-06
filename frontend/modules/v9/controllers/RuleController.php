<?php
/**
 *
 *
 * PHP Version 5
 *
 * @category  PHP
 * @filename  RuleController.php
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @copyright 2015 www.i500m.com
 * @license   http://www.i500m.com/ i500m license
 * @datetime  17/3/6
 * @version   SVN: 1.0
 * @link      http://www.i500m.com/
 */

namespace frontend\modules\v9\controllers;

use frontend\models\i500_social\Rule;
use common\helpers\RequestHelper;
use common\helpers\Common;
use yii;

/**
 * Class RuleController
 * @category  PHP
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @license   http://www.i500m.com/ i500m license
 * @link      http://www.i500m.com/
 */
class RuleController extends BaseController
{
    public function actionIndex()
    {   
        $data = Rule::find()->select(['pic_one','pic_two'])->asArray()->one();
        if (!empty($data)) {
        	 $this->returnJsonMsg('200', $data, Common::C('code','200','data','[]'));
        }else{
        	 $this->returnJsonMsg('2000', [], 'error');
        }
    }
}
