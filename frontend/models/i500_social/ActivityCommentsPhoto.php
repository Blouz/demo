<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\models\i500_social;

use common\helpers\Common;

class ActivityCommentsPhoto extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_activity_comments_photo}}';
    }
}