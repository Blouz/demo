<?php
/**
 * 邀请码表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    duzongyan <duzongyan@i500.com>
 * @time      2017-02-04
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      duzongyan@i500.com
 */

namespace frontend\models\i500_social;

/**
 * 邀请码表
 *
 * @category MODEL
 * @package  Social
 * @author   duzongyan <duzongyan@i500.com>
 * @license  http://www.i500m.com/ license
 * @link     duzongyan@i500.com
 */
class InviteCode extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_invite_code}}';
    }
    
}
