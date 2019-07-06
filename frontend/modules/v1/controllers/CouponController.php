<?php
/**
 * 优惠劵
 *
 * PHP Version 5
 *
 * @category  I500M
 * @package   Member
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-08-29
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use frontend\models\i500_social\UserCoupons;

/**
 * Order
 *
 * @category Social
 * @package  Order
 * @author   renyineng <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class CouponController extends BaseController
{

    /**
     * 方法描述
     * @return array
     */
    public function actionIndex()
    {
        $model = new UserCoupons();
        $list = $model->getMaxCoupon('18600618179', 210);
        var_dump($list);
    }
}
