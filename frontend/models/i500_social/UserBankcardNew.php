<?php
/**
 * 用户绑定银行卡表
 * PHP Version 5
 * @category  MODEL
 * @package   Social
 * @author    wyy <wyy@iyangpin.com>
 * @time      2017-06-21
 * @copyright 爱伍佰
 */

namespace frontend\models\i500_social;

class UserBankcardNew extends SocialBase {
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName() {
        return '{{%i500_user_bankcard_new}}';
    }
}
