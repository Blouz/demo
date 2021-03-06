<?php
/**
 * 用户钱包表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2016-08-13
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */

namespace frontend\models\i500_social;

/**
 * 用户钱包表
 *
 * @category MODEL
 * @package  Social
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class UserBankCard extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {

        return '{{i500_user_bank_card}}';
    }
}
