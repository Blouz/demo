<?php
/**
 * WalkActivity.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/7/31 14:31
 */

namespace frontend\models\i500_social;


class WalkActivity extends SocialBase
{
    /**
     * 表连接
     * @return string
     */
    public static  function tableName()
    {
       return "{{%i500_walk_activity}}";
    }
}