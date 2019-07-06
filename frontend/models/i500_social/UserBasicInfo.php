<?php
/**
 * 用户基本信息表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015-08-05
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\models\i500_social;
use common\helpers\HuanXinHelper;
use yii\behaviors\TimestampBehavior;
use frontend\models\i500m\Community;
/**
 * 用户基本信息表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class UserBasicInfo extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_user_basic_info}}';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile','nickname','avatar','personal_sign'], 'safe'],
            ['nickname', 'editMob'],
            [['no_amount','can_amount'], 'number'],
            ['avatar','url','message'=>'图像不合法']

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
            'id' => '主键ID',
            'mobile' => '手机号',
            'nickname' => '昵称',
            'avatar' => '头像',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * 修改环信昵称
     */
    public function editMob()
    {
        HuanXinHelper::hxModifyNickName($this->mobile, $this->nickname);
    }

    public function getService()
    {
        return $this->hasMany(Service::className(), ['mobile' => 'mobile']);
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['mobile' => 'mobile']);
    }

    public function getFriend()
    {
        return $this->hasOne(UserFriends::className(), ['uid' => 'mobile']);
    }

    public function getRecruit(){
        return $this->hasOne(Recruit::className(),['mobile'=>'mobile']);
    }

    public function getUsercommunity(){
        return $this->hasOne(UserCommunity::className(),['mobile'=>'mobile']);
    }
     public function getUsercommlist(){
        return $this->hasMany(Logincommunity::className(),['mobile'=>'mobile']);
    }
	
	 public function getLabel(){
        return $this->hasMany(UserLabel::className(),['mobile'=>'mobile']);
    }

    public function getCommunity(){
        return $this->hasOne(Community::className(),['id'=>'last_community_id']);
    }

    public function getMember(){
        return $this->hasOne(GroupMember::className(),['mobile'=>'mobile']);
    }
    public function getGroup(){
        return $this->hasMany(GroupMember::className(),['mobile'=>'mobile']);
    }
}
