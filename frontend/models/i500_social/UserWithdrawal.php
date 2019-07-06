<?php

namespace frontend\models\i500_social;

use Yii;

/**
 * This is the model class for table "{{%i500_user_withdrawal}}".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $mobile
 * @property string $real_name
 * @property string $bank_card
 * @property string $money
 * @property integer $status
 * @property string $create_time
 * @property string $expect_arrival_time
 * @property string $arrival_time
 */
class UserWithdrawal extends SocialBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%i500_user_withdrawal}}';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile','bank_card', 'real_name','money'], 'required'],
            [['status'], 'integer'],
            [['money'], 'number'],
            [['money'], 'validateMoney'],
            [['create_time', 'expect_arrival_time', 'arrival_time'], 'safe'],
            [['mobile'], 'string', 'max' => 11],
            [['real_name', 'bank_card'], 'string', 'max' => 25]
        ];
    }
    public function validateMoney($attribute)
    {

        $cat_amount = UserBasicInfo::find()->select('can_amount')->where(['mobile'=>$this->mobile])->scalar();
        if ($cat_amount < $this->money) {
            $this->addError($attribute, '超过最大提现金额.');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mobile' => '手机号',
            'real_name' => '真实姓名',
            'bank_card' => '银行卡号',
            'money' => '提现金额',
            'status' => '提现状态 0 等待审核 1 审核通过 2审核拒绝 3 提现到账',
            'create_time' => '创建时间',
            'expect_arrival_time' => '预计到账时间',
            'arrival_time' => '到账时间',
        ];
    }
}
