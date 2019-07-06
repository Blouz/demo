<?php
/**
 * 评论表
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-26 下午3:02
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v5\controllers;

use frontend\controllers\RestController;

class PostCommentController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\PostComments';
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['view'],$actions['index']);
        return $actions;
    }
}