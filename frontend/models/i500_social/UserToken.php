<?php
/**
 * 用户Token表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015-08-05
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\models\i500_social;

/**
 * 用户Token表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class UserToken extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_user_token}}';
    }
}
