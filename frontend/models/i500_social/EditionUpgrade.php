<?php
namespace frontend\models\i500_social;

use common\helpers\Common;

class EditionUpgrade extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_edition_upgrade}}';
    }

}