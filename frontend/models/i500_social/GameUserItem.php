<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/18
 * Time: 15:50
 */

namespace frontend\models\i500_social;

use frontend\models\i500_social\GameItemDictionary;

class GameUserItem extends SocialBase
{

    /**
     * 设置表名称
     * @return string
     */
    public static function tableName(){
        return '{{%i500_game_user_item}}';
    }

    public function getGameItemDictionary(){
        return $this->hasMany(GameItemDictionary::className(),['id'=>'item_id']);
    }
}