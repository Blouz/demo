<?php

namespace frontend\models\i500_social;

use Yii;

/**
 * This is the model class for table "{{%i500_adv}}".
 *
 * @property integer $id
 * @property string $title
 * @property string $image
 * @property string $type
 * @property integer $position
 * @property string $url
 * @property integer $status
 * @property integer $listorder
 */
class Adv extends SocialBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%i500_adv}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_social');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['position', 'url'], 'required'],
            [['position', 'status', 'listorder'], 'integer'],
            [['title'], 'string', 'max' => 100],
            [['image', 'url'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '标题',
            'image' => '描述',
            'type' => '跳转地址',
            'position' => '1首页上部一 2 首页上部二  3首页上班三 4 首页顶部四 。。',
            'url' => 'Url',
            'status' => '1 显示2 禁用',
            'listorder' => '排序',
        ];
    }
}
