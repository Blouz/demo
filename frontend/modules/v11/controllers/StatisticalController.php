<?php
/**
 * 统计
 *
 * PHP Version 5.6
 * @category  Social
 * @package   Login
 * @author    wyy <wyy@i500m.com>
 * @time      2017/06/12
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wyy@i500m.com
 */
namespace frontend\modules\v11\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\Statistical;
use frontend\models\i500m\Community;

class StatisticalController extends BaseController {
    
    /**
     * 小区幸福指数排行
     * @param string $mobile 手机号
     * @author wyy
     * @return json
     */
    public function actionIndex() {
  //       //手机号
  //       $mobile = RequestHelper::post('mobile', '', '');
		// if (empty($mobile)) {
  //           $this->returnJsonMsg('604', [], Common::C('code', '604'));
  //       }
  //       $limit = RequestHelper::post('limit', 10, 'intval');
        
  //       //根据当前手机号获取小区信息
  //       $community = UserBasicInfo::find()->select('last_community_id')
  //       ->with(['community'=>function($query){$query->select(['id','province','city','district'])->where(['status'=>1]);}])
  //       ->where(['mobile'=>$mobile])->asArray()->one();
  //       $city = isset($community['community']['city']) && !empty($community['community']['city']) ? $community['community']['city'] : -1;
  //       $cache_key = "statistical_data_city_{$city}";
  //       //缓存为空
  //       $data = array();//\Yii::$app->cache->get($cache_key);
  //       if (empty($data)) {
  //           //获取上月前十名小区
  //           $field = ['community_id','(SUM(chat_group)+SUM(activity_add)) as huanju','(SUM(post_add)+SUM(post_comment)+SUM(post_thumbs)) as fenxiang','MAX(user_num) as aixin','month_sort'];
  //           $data = Statistical::find()->select($field)
  //                   ->where(['LEFT(tdate,7)'=>date('Y-m', strtotime('-1 month'))])
  //                   ->groupBy('community_id,month_sort')
  //                   ->orderBy('month_sort asc')
  //                   ->limit($limit)->asArray()->all();
  //           foreach ($data as $key=>$val) {
  //               //小区名称
  //               $community_name = Community::find()->select('name')->where(['id'=>$val['community_id']])->scalar();
  //               $val['community_name'] = empty($community_name) ? '' : $community_name;
  //               //大上月浮动
  //               $old_sort = Statistical::find()->select('month_sort')->where(['community_id'=>$val['community_id'],'LEFT(tdate,7)'=>date('Y-m', strtotime('-2 month'))])->scalar();
  //               $val['sort_up'] = empty($old_sort) ? 0 : $old_sort-$val['month_sort'];
                
  //               $val['huanju'] = Common::formatNumber($val['huanju'],1,'万');
  //               $val['fenxiang'] = Common::formatNumber($val['fenxiang'],1,'万');
  //               $val['aixin'] = Common::formatNumber($val['aixin'],1,'万');
  //               $data[$key] = $val;
  //           }
  //           //设置缓存
  //           $time = strtotime(date('Y-m-01', strtotime('+1 month')))-time();
  //           //\Yii::$app->cache->set($cache_key, $data, $time);
  //       }
        $data = array();
        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'130',
            'fenxiang'=>'1969',
            'aixin'=>'40',
            'month_sort'=>'1',
            'community_name'=>'扬子江小区',
            'sort_up'=>6
            );

        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'116',
            'fenxiang'=>'1902',
            'aixin'=>'75',
            'month_sort'=>'2',
            'community_name'=>'小南社区',
            'sort_up'=>-1
            );
        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'103',
            'fenxiang'=>'1810',
            'aixin'=>'58',
            'month_sort'=>'3',
            'community_name'=>'SR国际新城',
            'sort_up'=>-1
            );
        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'98',
            'fenxiang'=>'1715',
            'aixin'=>'49',
            'month_sort'=>'4',
            'community_name'=>'阳光碧水园',
            'sort_up'=>2
            );
        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'102',
            'fenxiang'=>'1852',
            'aixin'=>'22',
            'month_sort'=>'5',
            'community_name'=>'九洲御府',
            'sort_up'=>3
            );
        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'90',
            'fenxiang'=>'1703',
            'aixin'=>'61',
            'month_sort'=>'6',
            'community_name'=>'泛美华庭',
            'sort_up'=>2
            );
        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'88',
            'fenxiang'=>'1700',
            'aixin'=>'47',
            'month_sort'=>'7',
            'community_name'=>'格林生活坊',
            'sort_up'=>-1
            );
        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'83',
            'fenxiang'=>'1698',
            'aixin'=>'32',
            'month_sort'=>'8',
            'community_name'=>'深航翡翠城',
            'sort_up'=>-3
            );
        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'89',
            'fenxiang'=>'1632',
            'aixin'=>'37',
            'month_sort'=>'9',
            'community_name'=>'龙之梦畅园',
            'sort_up'=>4
            );
        $data[]= array(
            'community_id'=>'1',
            'huanju'=>'60',
            'fenxiang'=>'1653',
            'aixin'=>'26',
            'month_sort'=>'10',
            'community_name'=>'绿地摩尔公馆',
            'sort_up'=>-2
            );
        return $this->returnJsonMsg('200', $data, Common::C('code','200'));
    }
}
