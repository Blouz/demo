<?php

namespace frontend\models\i500_social;

use Yii;

/**
 * This is the model class for table "{{%i500_post_photo}}".
 *
 * @property integer $id
 * @property string $mobile
 * @property integer $post_id
 * @property string $photo
 */
class PostPhoto extends \yii\db\ActiveRecord
{
    public static function getDB()
    {
        return \Yii::$app->db_social;
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%i500_post_photo}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['post_id'], 'integer'],
            [['mobile'], 'string', 'max' => 11],
            [['photo'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键ID',
            'mobile' => '用户ID(用户唯一标示)',
            'post_id' => '帖子ID',
            'photo' => '图片地址',
        ];
    }
}
