<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/14
 * Time: 10:43
 */

namespace frontend\models\i500_social;


class WalkTeam extends SocialBase
{
    /**
     * 表连接
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_walk_team}}";
    }
}