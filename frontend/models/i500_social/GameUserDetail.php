<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/18
 * Time: 13:34
 */

namespace frontend\models\i500_social;


class GameUserDetail extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_game_user_detail}}';
    }
}