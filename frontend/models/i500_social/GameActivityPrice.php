<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace frontend\models\i500_social;

use frontend\models\i500_social\ActivityPrice;
use frontend\models\i500_social\GameUserDetail;
class GameActivityPrice extends SocialBase
{
        /**
         * 设置表名称
         * @return string
         */
        public static function tableName()
        {
            return '{{%i500_game_activity_price}}';
        }

        

        
}