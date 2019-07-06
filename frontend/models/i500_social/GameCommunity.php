<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/18
 * Time: 15:00
 */

namespace frontend\models\i500_social;


class GameCommunity extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName(){
        return '{{%i500_game_community}}';
    }
}