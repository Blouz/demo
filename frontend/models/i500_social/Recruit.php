<?php

namespace frontend\models\i500_social;

use frontend\models\i500m\Community;
use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "i500_recruit".
 *
 * @property integer $id
 * @property string $mobile
 * @property string $telphone
 * @property string $true_name
 * @property string $identity_card
 * @property string $identity_image
 * @property string $identity_photo_front
 * @property string $identity_photo_back
 * @property integer $status
 * @property string $reason
 * @property string $create_time
 */
class Recruit extends SocialBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'i500_recruit';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile','telphone','true_name','identity_card','identity_image','identity_photo_front','identity_photo_back','community_id','community_city_id'], 'required'],
            [['status'], 'integer'],
            [['identity_image', 'identity_photo_front', 'identity_photo_back'], 'url'],
            ['status', 'default', 'value'=>0],
            [['create_time'], 'safe'],
            [['mobile', 'telphone'], 'string', 'max' => 11],
            [['true_name'], 'string', 'max' => 120],
            [['identity_card'], 'string', 'max' => 20],
            [['identity_image', 'identity_photo_front', 'identity_photo_back'], 'string', 'max' => 200],
            [['reason'], 'string', 'max' => 255],
            [['mobile'], 'unique', 'message'=>'您已经提交认证'],
//            [['mobile'], 'unique', 'message'=>'请等待审核','when'=>function($model) {
//
//                if ($this->isNewRecord  == false) {
//                    $status = static::find()->select(['status'])->where(['mobile'=>$model->mobile])->scalar();
//                    return in_array($status, [0,1])? true: false;
//                }
//
//                //var_dump($status);exit();
////                return $this->isNewRecord || ($this->_item->name != $this->name);
////                $model->find()->select()->where()
//
//            }],
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
            'telphone' => '申请手机号',
            'true_name' => '真实姓名',
            'identity_card' => '身份证号',
            'identity_image' => '身份证照片',
            'identity_photo_front' => '证件照正面',
            'identity_photo_back' => '证件照反面',
            'status' => '审核状态0 审核中 1 审核通过2 拒绝',
            'reason' => '拒绝理由',
            'community_id' => '小区id',
            'community_city_id' => '城市不能为空',
            'create_time' => '创建时间',
            'be_merchant' => '成为商户 0未申请 1申请中 2申请成功 3 申请被驳回',
        ];
    }
    public function fields()
    {
        $fields = parent::fields();
        $fields['community_name'] = function ($model) {
            return Community::find()->select(['name'])->where(['id'=>$model->community_id])->asArray()->scalar();
        //return $model->mobile . ' ' . $model->true_name;
    };
        // 删除一些包含敏感信息的字段
        unset($fields['mobile']);

        return $fields;
    }
}
