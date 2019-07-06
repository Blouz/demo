<?php
/**
 * 一行的文件介绍
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      16/8/9
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v6\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\ServiceCategory;

class ServicecategoryController extends BaseController
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
     * 获取邻居说分类
     * @return array
     */
    public function actionForumCategory()
    {
        $categoryList = ServiceCategory::find()->select(['id','name'])->where(['type'=>2,'is_deleted'=>2,'status'=>2])->asArray()->all();
        $this->returnJsonMsg(200, $categoryList, 'SUCCESS');
    }
}
