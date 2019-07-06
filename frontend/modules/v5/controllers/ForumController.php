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
namespace frontend\modules\v5\controllers;

use frontend\controllers\RestController;
use frontend\models\i500_social\ServiceCategory;
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
class ForumController extends RestController
{

    public $modelClass = 'frontend\models\i500_social\ServiceCategory';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['index'], $actions['update']);
        return $actions;
    }

    /**
     * 获取版块
     * @return array
     */
    public function actionIndex()
    {
        $map = [
          'type'=>['0','2'],
        ];
        $list = $this->findAll($map);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['image'] = Yii::$app->params['imgUrl'].$v['image'];
            }
        }
        $this->result['data'] = $list;
        return $this->result;
    }
}
