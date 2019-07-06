<?php

namespace frontend\models\i500_social;

use Yii;

/**
 * This is the model class for table "i500_user".
 *
 * @property integer $id
 * @property string $username
 * @property string $mobile
 * @property string $email
 * @property string $password
 * @property string $salt
 * @property string $last_login_time
 * @property string $last_login_ip
 * @property integer $last_login_channel
 * @property integer $last_login_source
 * @property integer $login_count
 * @property integer $status
 * @property integer $is_deleted
 * @property string $create_time
 * @property string $token
 * @property integer $expired_in
 */
class User extends SocialBase
{
    public $code;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'i500_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile','password'], 'trim'],
            ['mobile','match','pattern'=>'/^1[34587][0-9]{9}$/','message'=>'请输入正确的手机号'],
            [['mobile','password'], 'required'],
            ['password', 'createPassword','on'=>['register']],
            [['last_login_time', 'create_time','salt'], 'safe'],
            [['last_login_channel', 'last_login_source', 'login_count', 'status', 'is_deleted', 'expired_in'], 'integer'],
            [['expired_in'], 'required'],
            [['username'], 'string', 'max' => 25],
            [['mobile'], 'string', 'max' => 11],
            [['email'], 'string', 'max' => 120],
            [['last_login_ip'], 'string', 'max' => 20],
            [['token'], 'string', 'max' => 45],
            [['token'], 'default', 'value' => md5($this->mobile.time())],

            [['mobile'], 'unique','message'=>'此用户已经存在'],
            [['code','password'], 'required','message'=>'验证码必须','on'=>['register']],
            //['code','checkCode','params'=>['type'=>1]],
            ['code', 'validateCode','params'=>['type'=>3], 'skipOnEmpty'=>false, 'on'=>['register']],
        ];
    }
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['login'] = ['mobile', 'password','type','code'];//登陆场景
        $scenarios['register'] = ['mobile', 'password','code','token'];//注册场景
        $scenarios['binds'] = ['mobile', 'code','nickname','avatar'];//绑定第三方场景
        $scenarios['forget'] = ['mobile', 'password','code'];//忘记密码 修改密码
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键ID',
            'username' => '登录名',
            'mobile' => '手机号',
            'email' => '邮箱',
            'password' => '密码',
            'salt' => '随机数(用于密码加密)',
            'last_login_time' => '最后登陆时间',
            'last_login_ip' => '最后登陆IP',
            'last_login_channel' => '最后登陆渠道 1=账号2=qq3=weixin4=sina5=微信服务号',
            'last_login_source' => '最后登陆来源 1=wap2=android3=ios',
            'login_count' => '登陆次数',
            'status' => '是否禁用 1=禁用2=可用',
            'is_deleted' => '是否删除 1=已删除2=未删除',
            'create_time' => '创建时间',
            'token' => '用户token',
            'expired_in' => '过期时间',
        ];
    }
    public function validateCode($attribute, $params)
    {
        $map = ['mobile'=>$this->mobile, 'type'=>$params['type']];
       // $info = UserVerifyCode::findOne($map);
        $info = UserVerifyCode::find()->where($map)->orderBy('id desc')->asArray()->one();
        file_put_contents('/tmp/login1.txt', "执行时间：" . date('Y-m-d H:i:s') . " 数据11：" . var_export($info, true) . "\n", FILE_APPEND);
        file_put_contents('/tmp/login1.txt', "执行时间：" . date('Y-m-d H:i:s') . " 数据22：" . $this->code . "\n", FILE_APPEND);
        if (empty($info)) {
            $this->addError($attribute, '请发送验证码!');
        } else if ($info['code'] != $this->code) {
            $this->addError($attribute, '验证码错误!');}
        else if (strtotime($info['expires_in']) < time()) {
            $this->addError($attribute, '验证码已经过期,请重新发送!');

        }
    }
    public function createPassword()
    {
        file_put_contents('/tmp/login1.txt', "执行时间：" . date('Y-m-d H:i:s') . " 数据：" . var_export($this->salt, true) . "\n", FILE_APPEND);

        $this->salt = mt_rand(100000, 999999);
        $this->password = md5($this->salt.$this->password);
    }
}
