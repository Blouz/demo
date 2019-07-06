<?php
/**
 * 商家
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Shop
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/24
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v1\controllers;


use frontend\models\i500m\Shop;
use Yii;
use common\helpers\Common;
use common\helpers\CurlHelper;
use common\helpers\SsdbHelper;
use common\helpers\RequestHelper;

/**
 * Shop
 *
 * @category Social
 * @package  Shop
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class ShopController extends BaseController
{
    /**
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * 根据坐标获取附近的店铺
     * @return array
     */
    public function actionList()
    {
        $lng = RequestHelper::get('lng', '', '');
        if (empty($lng)) {
            $this->returnJsonMsg('801', [], Common::C('code', '801'));
        }
        $lat = RequestHelper::get('lat', '', '');
        if (empty($lat)) {
            $this->returnJsonMsg('802', [], Common::C('code', '802'));
        }
        $dis = Common::C('shopScope');
        $url = Common::C('channelHost').'lbs/near-shop?lng='.$lng.'&lat='.$lat.'&dis='.$dis;
        $rs = CurlHelper::get($url, true);
        if (empty($rs['code'])) {
            $this->returnJsonMsg('401', [], Common::C('code', '401'));
        }
        if ($rs['code'] == '404' || empty($rs['data'])) {
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }
        $info = [];
        foreach ($rs['data'] as $k => $v) {
            $shopInfo = $this->_getShopInfo($v['shop_id']);
            $info[$k]['shop_id']    = $v['shop_id'];
            $info[$k]['shop_img']   = $shopInfo['logo'];
            $info[$k]['shop_name']  = $v['shop_name'];
            $info[$k]['star']       = '5';
            $info[$k]['address']    = $shopInfo['address'];
            $info[$k]['distance']   = $v['dis'];
            $info[$k]['sent_fee']   = number_format($shopInfo['sent_fee']);
            $info[$k]['free_money'] = number_format($shopInfo['free_money']);
            $info[$k]['freight']    = number_format($shopInfo['freight']);
        }
        $this->returnJsonMsg('200', $info, Common::C('code', '200'));
    }

    /**
     * 获取商家信息
     * @param int $shop_id 商家ID
     * @return array
     */
    private function _getShopInfo($shop_id = 0)
    {
        if (empty($shop_id)) {
            $this->returnJsonMsg('803', [], Common::C('code', '803'));
        }
        $shop_model  = new Shop();
        $shop_fields = 'logo,address,sent_fee,free_money,freight';
        $shop_where['id'] = $shop_id;
        $info = $shop_model->getInfo($shop_where, true, $shop_fields);
        if (!empty($info)) {
            if (!empty($info['logo'])) {
                $info['logo'] = Common::C('imgHost').$info['logo'];
            }
        }
        return $info;
    }

    /**
     * 初始化商家信息
     * @return json
     */
    public function actionIndex()
    {
        $this->shop_id = RequestHelper::get('shop_id', 0, 'intval');
        if (empty($this->shop_id)) {
            $this->returnJsonMsg('803', [], Common::C('code', '803'));
        }
        $shop_model  = new Shop();
        $shop_fields = 'sent_fee,free_money,freight,address';
        $info = $shop_model->getInfo(['id'=>$this->shop_id], true, $shop_fields);
        if (!empty($info)) {
            $this->returnJsonMsg('200', $info, Common::C('code', '200'));
        } else {
            $this->returnJsonMsg('101', [], '此商家不存在');
        }
    }
}
