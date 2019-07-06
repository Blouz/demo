<?php

/* 
 * 
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */

namespace frontend\modules\v11\controllers;


use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500m\Community;
use frontend\models\i500m\City;
use frontend\models\i500m\CommunityLightup;
use yii\db\Query;
class CommController extends BaseController
{
    public function actionIndex()
    {
        $field[] = 'id';
        $comm = Community::find()->select($field)->where(['city'=>37])
                                 ->asArray()
                                 ->all();
        
        $total = array();
        foreach($comm as $c)
        {    
            $res = array();
            $id = $c['id'];
            $amount = UserBasicInfo::find()->select('i500_user.id')
                    ->join('LEFT JOIN','i500_user','i500_user_basic_info.mobile=i500_user.mobile')
                    ->where(['i500_user_basic_info.last_community_id'=>$id])
                    ->count();
            
            $uamount = UserBasicInfo::find()->select('i500_user.id')
                    ->join('LEFT JOIN','i500_user','i500_user_basic_info.mobile=i500_user.mobile')
                    ->where(['i500_user_basic_info.last_community_id'=>$id])
                    ->andWhere(['i500_user.step'=>8])
                    ->count();
            $res['comm_id'] = $id;
            if($uamount>9)
            {
                $res['up'] = 1;
            }
            else
            {
                $res['up'] = 0;
            }
            $res['all_users'] = $amount;
            $res['auth_users'] = $uamount;
            $total[] = $res;
        }
        $keys = [
                    'comm_id',
                    'up',
                    'all_users',
                    'auth_users',
                ];
        $num = \Yii::$app->db_500m->createCommand()->batchInsert(CommunityLightup::tableName(),$keys,$total)->execute();
        var_dump($num);
    }
}