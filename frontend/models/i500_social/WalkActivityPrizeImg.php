<?php
/**
 * WalkActivityPrizeImg.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/7/31 15:12
 */

namespace frontend\models\i500_social;


class WalkActivityPrizeImg extends SocialBase
{
    /**
     * 表连接
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_walk_activity_prize_img}}";
    }
}