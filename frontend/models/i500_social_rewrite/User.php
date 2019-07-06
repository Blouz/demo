<?php
/**
 * 用户账号信息model
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2017-04-13
 * @copyright 辽宁i500科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\models\i500_social_rewrite;

use Yii;

class User extends SocialRewriteBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'i500_user';
    }


    /**
     * 根据手机号获取用户信息
     * @author liuyanwei <liuyanwei@i500m.com>
     * @param string $mobile   手机号
     * @return array
     */
    public function getUserStatus($mobile)
    {
        return $this->find()->select(['mobile','status'])->with(['userCommunity'=>function($query) {
            $query->select(['mobile','community_id','lng','lat','is_pioneer','join_in']);
        }])->where(['mobile'=>$mobile])->asArray()->one();
    }


    /**
     * 根据手机号获取用户id
     * @author duzongyan <duzongyan@i500m.com>
     * @param string $mobile   手机号
     * @return array
     */
    public function getOneColumn($arr_where, $str_field = '*')
    {
        return $this->find()->select($str_field)->where($arr_where)->column();
    }
    /**
     * 关联用户所在小区权限
     */
    public function getUserCommunity()
    {
        return $this->hasOne(UserCommunity::className(), ['mobile' => 'mobile']);
    }
}
