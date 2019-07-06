<?php
/**
 * 定位
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Location
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      2015/9/28
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v1\controllers;

use common\helpers\CurlHelper;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;

/**
 * 定位
 *
 * @category Social
 * @package  Location
 * @author   renyineng <renyineng@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     renyineng@iyangpin.com
 */
class LocationController extends BaseController
{
    public function actionNearCommunity()
    {
        $lng = RequestHelper::get('lng', 0);
        $lat = RequestHelper::get('lat', 0);
        $dis = RequestHelper::get('dis', 3);
        $url = Common::C('channelHost').'lbs/near-community?lng='.$lng.'&lat='.$lat.'&dis='.$dis;
        $res = CurlHelper::get($url);
        if ($res['code'] == 200) {
            $this->returnJsonMsg($res['code'], $res['data'], $res['message']);
        } else {
            $this->returnJsonMsg(101, [], '获取失败');
        }
    }
    public function actionSearchCommunity()
    {
        $keywords = RequestHelper::get('keywords', '');
        $city = RequestHelper::get('city', '');
        $limit = RequestHelper::get('limit', 10, 'intval');

        $url = Common::C('channelHost').'lbs/search-community?keywords='.$keywords.'&city='.$city.'&limit='.$limit;

        $res = CurlHelper::get($url);
        if ($res['code'] == 200) {
            $this->returnJsonMsg($res['code'], $res['data'], $res['message']);
        } else {
            $this->returnJsonMsg(101, [], '获取失败');
        }

//        $table_name = Common::getCommunityTable(1);
//        $model = new CommunityMongo($table_name);
//        $list = $model->search($keywords, $city, $limit);
//        //var_dump($list);exit();
//        if (!empty($list)) {
//            return $this->returnJsonMsg(200, $list, '获取成功');
//        } else {
//            return $this->returnJsonMsg(404, [], '暂无数据');
//        }
    }
}
