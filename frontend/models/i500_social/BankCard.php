<?php
/**
 * 用户银行卡表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015-11-27
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */

namespace frontend\models\i500_social;
use yii\behaviors\TimestampBehavior;

/**
 * 用户银行卡表
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class BankCard extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_user_bank_card}}';
    }

    public function rules()
    {
        return [
            ['mobile','match','pattern'=>'/^1[34587][0-9]{9}$/','message'=>'请输入正确的手机号'],
            ['bank_card','getBankInfo'],
            ['bank_card','unique','message'=>'此银行卡已被绑定'],
            [['mobile','real_name', 'bank_card','bank_name', 'bank', 'bank_type'],'required'],
            [['mobile','bank','bank_type'],'string', 'max'=>11],
            ['status','default', 'value'=>1],
            ['bank_card','string', 'max'=>19],
            //['bank_card','string', 'max'=>19],

        ];
    }

    public function behaviors()
    {
//        return [
//            TimestampBehavior::className(),
//        ];
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => 'update_time',
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
            'id' => 'ID',
            'mobile' => '手机号',
            'real_name' => '开户名',
            'bank_card' => '银行卡号',
            'bank_name' => '银行名',
            'bank' => '银行简称',
            'bank_type' => '银行卡类型',
            'status' => '绑定装填 1=已绑定2=已解绑',

            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }
    public function getBankInfo($attribute)
    {
            $info = \common\libs\BankCard::info($this->bank_card);
        //var_dump($info);
            if ($info['validated'] == true) {
                //卡正确
                if ($info['cardType'] == 'CC') {
                    $this->addError($attribute, '暂不支持绑定信用卡');
                }
                $this->bank_name = $info['bankName'];
                $this->bank = $info['bank'];
                $this->bank_type = $info['cardType'];

              //  var_dump($this);
            } else {
                $this->addError($attribute, '无效的银行卡号');
            }
    }
    public function fields()
    {
        $fields = parent::fields();
        $fields['image'] = function ($model) {
            return \common\libs\BankCard::getBankImg($this->bank);
        };
       // $fields['bank_type'] = $this->bank_type == 'CC' ?'信用卡':'储蓄卡';
        // 删除一些包含敏感信息的字段
        unset($fields['mobile']);

        return $fields;
    }

}
