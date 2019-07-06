<?php
/**
 * 活动基类
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   BASE
 * @author    huangdekui <huangdekui@i500m.com>
 * @time      2017/4/18
 * @copyright 辽宁i500科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      huangdekui@i500m.com
 */
namespace frontend\modules\rewrite\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;

/**
 * BASE
 *
 * @category Social
 * @package  BASE
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class ActivityBaseController extends BaseController
{

    public $type = null;
    public $mobile = null;
    public $activity_id = null;
    public $page = null;
    public $page_size = null;

    /**
     * 初始化
     * @return array
     */
    public function init()
    {
    	$this->type        = RequestHelper::post('type', '', '');//0所有活动 1自己已参加的活动
        $this->mobile      = RequestHelper::post('usermobile', '', '');//手机号
        $this->activity_id = RequestHelper::post('activity_id', '', '');//活动ID
        $this->page        = RequestHelper::post('page', '1', 'intval');//列表起始位置
		$this->page_size   = RequestHelper::post('page_size','10','intval');//每页多少个        
        parent::init();
    }
}