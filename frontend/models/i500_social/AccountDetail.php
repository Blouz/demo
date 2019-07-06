<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-25 上午9:49
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\i500_social;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * 用户银行卡表
 *
 * @category MODEL
 * @package  Social
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class AccountDetail extends SocialBase
{
    public static $water_type = [
        0=>'未知',
        1=>'预约服务',
        2=>'需求担保',
        3=>'退款',
        4=>'生活缴费',
        5=>'充值',
        6=>'提现',
        7=>'系统奖励',
        8=>'便利店',
    ];
//类型1 服务 2 需求3 预约消费 4 充值5提现6 注册送7邀请送
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return 'i500_account_detail';
    }

    public function rules()
    {
        return [
            ['mobile','match','pattern'=>'/^1[34587][0-9]{9}$/','message'=>'请输入正确的手机号'],
            ['type','in','range'=>[1,2,3,4,5,6]],

            [['extra_info'], 'safe'],
            [['price'], 'number','message'=>'金额必须是为数字'],
//            ['bank_card','getBankInfo'],
//            ['bank_card','unique','message'=>'此银行卡已被绑定'],
//            [['mobile','real_name', 'bank_card','bank_name', 'bank'],'required'],
            [['remark','order_sn'],'string', 'max'=>255],
            [['status','pay_method'],'default', 'value'=>0],
//            ['bank_card','string', 'max'=>19],
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
            'id' => 'ID',
            'mobile' => '手机号',
            'type' => '类型',
            'price' => '操作金额',
            'amount' => '剩余金额',
            'remark' => '备注',
            'create_time' => '创建时间'
        ];
    }
    public function getBankInfo($attribute)
    {
        $info = \common\libs\BankCard::info($this->bank_card);
        //var_dump($info);
        if ($info['validated'] == true) {
            //卡正确
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
        $fields['water_type'] = function ($model) {
            return ArrayHelper::getValue(self::$water_type, $this->type);
            //return self::$water_type[$this->type];
        };
        // 删除一些包含敏感信息的字段
        unset($fields['mobile']);

        return $fields;
    }

}
