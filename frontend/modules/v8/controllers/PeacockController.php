<?php
/**
 * 一行的文件介绍
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    duzongyan <duzongyan@i500m.com>
 * @time      17/01/10
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      duzongyan@i500m.com
 */
namespace frontend\modules\v8\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\Peacock;
use yii\db\Query;

class PeacockController extends BaseController
{
    /**
     * 获取开屏页信息
     * @return array
     */
    public function actionIndex()
    {   
        $peacock = Peacock::find()->select(['images','image_time'])->where(['status'=>2])->orderBy('sort ASC')->asArray()->all(); 
        foreach ($peacock as $k => $v) {
            $peacock[$k]['images'] = Common::C('imgHost').$v['images'];    
        }  
        if (empty($peacock)) {
            $this->returnJsonMsg('625', [], Common::C('code', '625'));
        }
        $this->returnJsonMsg('200', $peacock, Common::C('code', '200'));
    }
}
