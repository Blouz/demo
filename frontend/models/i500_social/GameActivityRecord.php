<?php
/**
 * 中奖记录表
 * User: Administrator
 * Date: 2016/10/18
 * Time: 11:04
 */

namespace frontend\models\i500_social;

use frontend\models\i500_social\ActivityPrice;
use frontend\models\i500_social\GameUserDetail;
class GameActivityRecord extends SocialBase
{
        /**
         * 设置表名称
         * @return string
         */
        public static function tableName()
        {
            return '{{%i500_game_activity_record}}';
        }

        public function getGift(){
            return $this->hasOne(GameGift::className(),['id'=>'price_id']);
        }

        public function getGameUserDetail(){
            return $this->hasOne(GameUserDetail::className(),['record_id'=>'id']);
        }
}