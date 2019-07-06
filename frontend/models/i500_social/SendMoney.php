<?php
/**
 * 红包
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    huangdekui <huangdekui@i500m.com>
 * @time      2016-11-01
 * @copyright 2016 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      huangdekui@i500m.com
 */

namespace frontend\models\i500_social;

/**
 * 红包表
 *
 * @category MODEL
 * @package  Social
 * @author   huangdekui <huangdekui@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     huangdekui@i500m.com
 */
class SendMoney extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_send_money}}';
    }
}
