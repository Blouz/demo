<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/16
 * Time: 13:37
 */

namespace frontend\models\i500_social;


class WalkZrankPersion extends SocialBase
{
    /**
     * 表连接
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_walk_zrank_persion}}';
    }
}