<?php

namespace frontend\models\i500_social;

use Yii;
use yii\behaviors\TimestampBehavior;


class Comment extends SocialBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'i500_comment';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile','content'], 'required'],

            [['mobile', 'contract_mobile'], 'string', 'max' => 11],
            [['content','dev_name'], 'string', 'max' => 255]
        ];
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => false,
                'value' => function() { return date('Y-m-d H:i:s');}
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键ID',
            'mobile' => '手机号',
            'contract_mobile' => '联系人手机号',
            'content' => '请输入评论内容',
            'create_time' => '创建时间',
        ];
    }
}
