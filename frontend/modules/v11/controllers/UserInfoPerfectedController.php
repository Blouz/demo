<?php
/**
 * 用户注册登录完善信息
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Service
 * @author    huangdekui <wangleilei@i500m.com>
 * @time      2017/05/09
 * @copyright 2017 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      huangdekui@i500m.com
 */
namespace frontend\modules\v11\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use common\helpers\TxyunHelper;
use common\vendor\tls_sig\php\sig;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\User;

class UserInfoPerfectedController extends BaseController
{
    /**
     * 与建设者沟通
     * return array()
    **/
    public function actionIndex()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if(empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if(!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        //获取用户所在小区
        $userBasicInfo = new UserBasicInfo();
        $community_id = $userBasicInfo->getInfo(['mobile'=>$this->mobile],true,['last_community_id']);

        $user_basic_id = User::find()->select(['id'])->where(['mobile'=>$this->mobile])->scalar();
        $user_sig = UserBasicInfo::find()->select(['realname','avatar','tx_user_sig'])->where(['mobile'=>$this->mobile])->asArray()->one();
        if(!empty($user_sig['tx_user_sig'])){
            $sig = $user_sig['tx_user_sig'];
        } else {
//            $day = date("Y-m-d H:i:s", strtotime("+179 day"));
            $sdkappid = Common::C('sdkappid');
            $private_key_path = Common::C('private_key_path');
            $generator = Common::C('generator');
//            $current_date>$community['tx_sig_expire'])
            $user_sig_id = sig::signature($user_basic_id,$sdkappid,$private_key_path,$generator);
            if(empty($user_sig_id))
            {
                $this->returnJsonMsg('70011', [], '注册腾讯云用户授权码生成失败');
            }
            $sig = $user_sig_id[0];
        }
        //注册腾讯云
        TxyunHelper::Regsiter($user_basic_id,$user_sig['realname'],$user_sig['avatar']);

        //查询该小区的建设者
        $user_id = UserBasicInfo::find()->select(['mobile','realname','avatar'])
            ->with(['user'=>function($query){
                $query->select(['mobile','id'])->where(['is_deleted'=>2,'status'=>2]);
            }])
            ->where(['last_community_id'=>$community_id,'is_pioneer'=>1])
            ->asArray()
            ->one();
        $array = [];
        $array['id'] = '0';
        if (!empty($user_id['user']['id'])) {
            $array['id'] = $user_id['user']['id'];
        }
        $array['user_id'] = $user_basic_id;
        $array['user_sig'] = $sig;
        return $this->returnJsonMsg('200', $array, 'SUCCESS');
    }
}
?>