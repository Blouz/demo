<?php
/**
 * 样品
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Sample
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/10/23
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v2\controllers;

use frontend\models\i500m\Sample;
use frontend\models\i500m\SampleImage;
use frontend\models\i500m\SampleLog;
use frontend\models\i500m\SupplierLocation;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500m\ShopCommunity;
use frontend\models\i500m\SupplierSampleGive;
use frontend\models\i500m\Shop;
use frontend\models\shop\ShopSamples;
use frontend\models\i500_social\SampleOrder;
use frontend\models\i500_social\Order;
use yii\helpers\ArrayHelper;

/**
 * Sample
 *
 * @category Social
 * @package  Sample
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class SampleController extends BaseController
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
     * 样品列表
     * @return array
     */
    public function actionList()
    {

        $page      = RequestHelper::get('page', '1', 'intval');
        $lng = RequestHelper::get('lng', 0, 'floatval');
        $lat = RequestHelper::get('lat', 0, 'floatval');
        $dis = RequestHelper::get('dis', 0, 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $model = new Sample();
        $shop_list = $model->getNearSampleShop($lng, $lat, $dis);
        $shop_ids_array = [];
        if (!empty($shop_list)) {
            foreach ($shop_list as $k => $v) {
                $shop_ids_array[] = $v['supplier_id'];
            }
        }
        //var_dump($shop_ids_array);exit();

        //获取样品ID
        $sample = new Sample();
        $samples_where['status'] = 2;
        $samples_where['shop_id'] = $shop_ids_array;
        //$shop_samples_and_where = "shop_id in ({$shop_ids})";

        $supplier_sample_fields = 'id,name,description';
        //$shop_samples_and_where = "id in ({$samples_ids})";
        $list = $sample->getPageList($samples_where, $supplier_sample_fields, 'id desc', $page, $page_size);
        if (empty($list)) {
            $this->returnJsonMsg('1202', [], Common::C('code', '1202'));
        }
        $sample_ids = [];
        foreach ($list as $v) {
            $sample_ids[] = $v['id'];
        }
        $sample_image_model = new SampleImage();
        $sample_image_list = $sample_image_model->getList(['sample_id'=>$sample_ids, 'type'=>1], 'image,sample_id');
        $image_list = ArrayHelper::index($sample_image_list, 'sample_id');

        foreach ($list as $l_key => $l_val) {

            $list[$l_key]['sample_description'] = $this->_formatHtml($l_val['description']);
            $list[$l_key]['image'] = $this->_formatImg(ArrayHelper::getValue($image_list, $l_val['id'].'.image', ''));
            $list[$l_key]['price'] = '0';
            $list[$l_key]['receive_type'] = '到店';
            unset($list[$l_key]['description']);
        }
        //var_dump($image_list);
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 样品详情
     * @return array
     */
    public function actionDetail()
    {
        $sample_id = RequestHelper::get('sample_id', '0', 'intval');
        if (empty($sample_id)) {
            $this->returnJsonMsg('1203', [], Common::C('code', '1203'));
        }
        $uid = RequestHelper::get('uid', '', '');
        $mobile = RequestHelper::get('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
        //通过样品查询商家ID
        $sample_model = new Sample();
        $sample_where['id'] = $sample_id;
        //$shop_sample_where['status']    = '2';
        $sample_info = $sample_model->getInfo($sample_where, true, ['shop_id','day_count','status','used'=>'have_receive_count']);
        if (empty($shop_sample_info) || $sample_info['status'] !=2) {
            $this->returnJsonMsg('1204', [], Common::C('code', '1204'));
        }
        //通过商家ID查询商家信息
        $shop_model = new SupplierLocation();
        $shop_where['supplier_id'] = $sample_info['shop_id'];
        //$shop_fields = 'id,name,address,tel';
        $shop_fields = ['id','name'=>'shop_name','address','tel'=>'mobile'];
        $shop_info = $shop_model->getInfo($shop_where, true, $shop_fields);
        if (empty($shop_info)) {
            $this->returnJsonMsg('1205', [], Common::C('code', '1205'));
        }



        $sample_image_model = new SampleImage();
        $sample_image = $sample_image_model->getField(['sample_id'=>$sample_id, 'type'=>1], 'image');
        //$image_list = ArrayHelper::index($sample_image_list, 'sample_id');

        $supplier_sample_info['sample_description'] = $this->_formatHtml($sample_info['description']);
        unset($supplier_sample_info['description']);
        $supplier_sample_info['image'] = $this->_formatImg($sample_image);
        $rs_arr['have_receive'] = '0';
        //样品已领取数
        $sample_order_model = new SampleLog();
        $sample_receive_where['sample_id'] = $sample_id;
        $sample_receive_where['user_id'] = $sample_id;
        $sample_receive_count = $sample_order_model->getCount($sample_receive_where);
        $supplier_sample_info['have_receive_count'] = $sample_receive_count;
        //样品可领取数量
        $supplier_sample_info['can_receive_count'] = $shop_sample_info['can_receive_count'];
        //通过用户手机号和用户ID 查询是否领取过
        if (!empty($uid) && !empty($mobile)) {
            $sample_order_where['uid']       = $uid;
            $sample_order_where['mobile']    = $mobile;
            $sample_order_where['sample_id'] = $sample_id;
            $sample_order_info = $sample_order_model->getInfo($sample_order_where, true, 'id');
            if (!empty($sample_order_info)) {
                $rs_arr['have_receive'] = '1';
            }
        }
        unset($shop_info['can_receive_count']);
        $rs_arr['shop_info']   = $shop_info;
        $rs_arr['sample_info'] = $supplier_sample_info;
        $this->returnJsonMsg('200', $rs_arr, Common::C('code', '200'));
    }

    /**
     * 领取
     * @return array
     */
    public function actionReceive()
    {
        $data['uid'] = RequestHelper::post('uid', '0', 'intval');
        if (empty($data['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $data['mobile'] = RequestHelper::post('mobile', '', '');
        if (empty($data['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($data['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $data['sample_id'] = RequestHelper::post('sample_id', '0', 'intval');
        if (empty($data['sample_id'])) {
            $this->returnJsonMsg('1203', [], Common::C('code', '1203'));
        }
        $data['source_type'] = RequestHelper::post('source_type', '0', 'intval');
        if (empty($data['source_type'])) {
            $this->returnJsonMsg('1033', [], Common::C('code', '1033'));
        }
        if (!in_array($data['source_type'], ['1','2','3','4'])) {
            $this->returnJsonMsg('902', [], Common::C('code', '902'));
        }
        //@todo 领取规则，一天只能领取一件样品
        $sample_order_model = new SampleOrder();
        $sample_order_where['uid']    = $data['uid'];
        $sample_order_where['mobile'] = $data['mobile'];
        $now_day = date('Y-m-d', time());
        $next_day = date("Y-m-d", strtotime("+1 day"));
        $sample_order_and_where = ['and', ['>', 'create_time', $now_day ], ['<', 'create_time', $next_day]];
        $sample_order_rs = $sample_order_model->getInfo($sample_order_where, true, 'id', $sample_order_and_where);
        if (!empty($sample_order_rs)) {
            $this->returnJsonMsg('1207', [], Common::C('code', '1207'));
        }

        //通过样品查询商家ID
        $shop_sample_model = new ShopSamples();
        $shop_sample_where['sample_id'] = $data['sample_id'];
        $shop_sample_where['status']    = '2';
        $shop_sample_info = $shop_sample_model->getInfo($shop_sample_where, true, 'shop_id,can_receive_count');
        if (empty($shop_sample_info)) {
            $this->returnJsonMsg('1204', [], Common::C('code', '1204'));
        }

        //获取该样品已经领取数量
        $sample_receive_where['sample_id'] = $data['sample_id'];
        $sample_receive_count = $sample_order_model->getCount($sample_receive_where);
        if ($sample_receive_count >= $shop_sample_info['can_receive_count']) {
            $this->returnJsonMsg('1209', [], Common::C('code', '1209'));
        }
        //通过商家ID查询商家信息
        $shop_model = new Shop();
        $shop_where['id'] = $shop_sample_info['shop_id'];
        $shop_fields = 'id,logo,shop_name,address,mobile';
        $shop_info = $shop_model->getInfo($shop_where, true, $shop_fields);
        if (empty($shop_info)) {
            $this->returnJsonMsg('1205', [], Common::C('code', '1205'));
        }
        $data['shop_id']      = $shop_info['id'];
        $data['shop_img']     = $shop_info['logo'];
        $data['shop_name']    = $shop_info['shop_name'];
        $data['shop_address'] = $shop_info['address'];
        $data['shop_mobile']  = $shop_info['mobile'];

        //获取样品信息
        $supplier_sample_model = new SupplierSampleGive();
        $supplier_sample_fields = 'id,title,image,description';
        $supplier_sample_where['id'] = $data['sample_id'];
        $supplier_sample_info = $supplier_sample_model->getInfo($supplier_sample_where, true, $supplier_sample_fields);
        if (empty($supplier_sample_info)) {
            $this->returnJsonMsg('1206', [], Common::C('code', '1206'));
        }
        $data['sample_title']       = $supplier_sample_info['title'];
        $data['sample_image']       = $supplier_sample_info['image'];
        $data['sample_description'] = $supplier_sample_info['description'];
        $data['sample_use_type']    = '到店';
        $data['sample_price']       = '0.00';
        $data['sample_number']      = '1';

        //创建订单号
        $order_model = new Order();
        //@todo 确定创建订单号为什么用省份？35=全国
        $data['order_sn']     = $order_model->createSn('35', $data['mobile']);
        if (empty($data['order_sn'])) {
            $this->returnJsonMsg('1053', [], Common::C('code', '1053'));
        }
        //创建兑换码 8 为随机码
        $data['exchange_code'] = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        $rs = $sample_order_model->insertInfo($data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $rs_arr['order_sn'] = $data['order_sn'];
        $this->returnJsonMsg('200', $rs_arr, Common::C('code', '200'));
    }

    /**
     * 样品订单列表
     * @return array
     */
    public function actionOrderList()
    {
        $where['uid'] = RequestHelper::get('uid', '0', 'intval');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $order_status = RequestHelper::get('order_status', '0', 'intval');
        if (empty($order_status)) {
            $this->returnJsonMsg('1046', [], Common::C('code', '1046'));
        }
        if ($order_status == '1') {
            //未领取
            $where['order_status'] = '0';
        }
        if ($order_status == '2') {
            //已领取
            $where['order_status'] = '1';
        }
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $sample_order_model = new SampleOrder();
        $sample_order_fields = 'order_status,order_sn,create_time,sample_image,sample_title,sample_price,sample_number,exchange_code';
        $list = $sample_order_model->getPageList($where, $sample_order_fields, 'id desc', $page, $page_size);
        if (empty($list)) {
            $this->returnJsonMsg('1210', [], Common::C('code', '1210'));
        }
        foreach ($list as $k => $v) {
            $list[$k]['sample_image'] = $this->_formatImg($v['sample_image']);
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 样品订单详情
     * @return array
     */
    public function actionOrderDetail()
    {
        $where['uid'] = RequestHelper::get('uid', '0', 'intval');
        if (empty($where['uid'])) {
            $this->returnJsonMsg('621', [], Common::C('code', '621'));
        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $where['order_sn'] =  RequestHelper::get('order_sn', '0', '');
        if (empty($where['order_sn'])) {
            $this->returnJsonMsg('1042', [], Common::C('code', '1042'));
        }
        $sample_order_model = new SampleOrder();
        $sample_order_fields = 'order_status,order_sn,create_time,sample_image,sample_title,sample_price,sample_number,exchange_code,shop_id,shop_img,shop_name,shop_address,shop_mobile';
        $sample_order_info = $sample_order_model->getInfo($where, true, $sample_order_fields);
        if (empty($sample_order_info)) {
            $this->returnJsonMsg('1208', [], Common::C('code', '1208'));
        }
        $sample_order_info['sample_image'] = $this->_formatImg($sample_order_info['sample_image']);
        $sample_order_info['shop_img'] = $this->_formatImg($sample_order_info['shop_img']);
        $this->returnJsonMsg('200', $sample_order_info, Common::C('code', '200'));
    }

    /**
     * 格式化图片
     * @param string $image 图片地址
     * @return string
     */
    private function _formatImg($image = '')
    {
        if (!empty($image)) {
            if (!strstr($image, 'http')) {
                return Common::C('imgHost').$image;
            }
        }
        return '';
    }

    /**
     * 给客户端返回html
     * @param string $html Html
     * @return string
     */
    private function _formatHtml($html = '')
    {
        $f_html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html><head><meta http-equiv="content-type" content="text/html; charset=UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <style type="text/css">
            img{max-width: 100%;}
</style>
            </head>
            <body>';
        $f_html .= $html;
        $f_html .= '</body></html>';
        $f_html = urlencode(str_replace('\"', '', htmlspecialchars_decode($f_html)));
        return $f_html;
    }
}
