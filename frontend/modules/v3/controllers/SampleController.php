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
namespace frontend\modules\v3\controllers;

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
        $dis = RequestHelper::get('dis', 3, 'intval');

        $page_size = RequestHelper::get('page_size', '6', 'intval');
        $dis = empty($dis)? 3 :$dis;
        if (empty($lng) || empty($lat)) {
            $this->returnJsonMsg('600', [], Common::C('code', '600'));
        }
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $model = new Sample();
        $shop_list = $model->getNearSampleShop($lng, $lat, $dis);
        if ($shop_list == 100) {
            $this->returnJsonMsg('401', [], Common::C('code', '401'));
        }
        //var_dump($shop_list);
        $new_shop = [];
        $shop_ids_array = [];
        if (!empty($shop_list)) {
            foreach ($shop_list as $k => $v) {
                $shop_ids_array[] = $v['supplier_id'];
                $new_shop[$v['supplier_id']] = ['dis'=>$v['dis'], 'shop_name'=>$v['name']];
            }
        }
        //var_dump($shop_ids_array);exit();

        //获取样品ID
        $sample = new Sample();
        $samples_where['status'] = 2;
        $samples_where['shop_id'] = $shop_ids_array;
        //$shop_samples_and_where = "shop_id in ({$shop_ids})";

        $supplier_sample_fields = 'id,name,shop_id';
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

            //$list[$l_key]['sample_description'] = $this->_formatHtml($l_val['description']);
            $list[$l_key]['image'] = $this->_formatImg(ArrayHelper::getValue($image_list, $l_val['id'].'.image', ''));
            $list[$l_key]['price'] = '0';
            $list[$l_key]['receive_type'] = '到店';
            $list[$l_key]['dis'] = ArrayHelper::getValue($new_shop, $v['shop_id'].'.dis', '');
            //unset($list[$l_key]['description']);
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
        $lng = RequestHelper::get('lng', 0, 'floatval');
        $lat = RequestHelper::get('lat', 0, 'floatval');
        if (empty($sample_id)) {
            $this->returnJsonMsg('1203', [], Common::C('code', '1203'));
        }
        //$uid = RequestHelper::get('uid', '', '');
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
        $sample_info = $sample_model->getInfo($sample_where, true, ['name', 'shop_id','description', 'day_count','status','have_receive_count'=>'used']);
        //var_dump($sample_info);
        if (empty($sample_info) || $sample_info['status'] !=2) {
            $this->returnJsonMsg('1204', [], Common::C('code', '1204'));
        }
        //通过商家ID查询商家信息
        $shop_model = new SupplierLocation();
        $shop_where['supplier_id'] = $sample_info['shop_id'];
        //$shop_fields = 'id,name,address,tel';
        $shop_fields = ['id','shop_name'=>'name','address','mobile'=>'tel','lng'=>'xpoint', 'lat'=>'ypoint'];
        //var_dump($shop_where);exit();
        $shop_info = $shop_model->getInfo($shop_where, true, $shop_fields);
        if (empty($shop_info)) {
            $this->returnJsonMsg('1205', [], Common::C('code', '1205'));
        }



        $sample_image_model = new SampleImage();
        $sample_image = $sample_image_model->getField('image', ['sample_id'=>$sample_id, 'type'=>1]);
        //$image_list = ArrayHelper::index($sample_image_list, 'sample_id');

        $sample_info['sample_description'] = $this->_formatHtml($sample_info['description']);
        unset($sample_info['description']);
        $sample_info['image'] = $this->_formatImg($sample_image);
        $rs_arr['have_receive'] = '0';
        //样品已领取数
        $sample_order_model = new SampleLog();
//        $sample_receive_where['sample_id'] = $sample_id;
//        $sample_receive_where['mobile'] = $mobile;
//        $sample_receive_count = $sample_order_model->getCount($sample_receive_where);
       // $sample_info['have_receive_count'] = $sample_receive_count;
        //样品可领取数量
        //$sample_info['can_receive_count'] = $sample_info['day_count'];
        //通过用户手机号和用户ID 查询是否领取过
        if (!empty($mobile)) {

            $sample_order_where['mobile']    = $mobile;
            $sample_order_where['sample_id'] = $sample_id;
            $sample_order_info = $sample_order_model->getInfo($sample_order_where, true, 'id');
            if (!empty($sample_order_info)) {
                $rs_arr['have_receive'] = '1';
            }
        }
        //今日剩余的计算
        $today = strtotime(date("Y-m-d"));
        $tomorry = strtotime("+1 day", $today);
        //$today = strtotime(date("Y-m-d") + 1);
        //今日已经领取数量
        $have_receive = $sample_order_model->getCount(['sample_id'=>$sample_id],['between', 'create_time', $today, $tomorry]);

        $sample_info['have_receive'] = $have_receive;
        //今日剩余可以领取数量
        $sample_info['can_receive_count'] = $sample_info['day_count'] - $have_receive;
        //var_dump($shop_info);exit();
        if (!empty($lng) && !empty($lat)) {
            $shop_info['dis'] = Common::getDistance($lat,$lng, $shop_info['lat'], $shop_info['lng']);
        } else {
            $shop_info['dis'] = '2';
        }

        unset($shop_info['can_receive_count']);
        $rs_arr['shop_info']   = $shop_info;
        $rs_arr['sample_info'] = $sample_info;
        $this->returnJsonMsg('200', $rs_arr, Common::C('code', '200'));
    }

    /**
     * 领取
     * @return array
     */
    public function actionReceive()
    {
        //$data['uid'] = RequestHelper::post('uid', '0', 'intval');
//        if (empty($data['uid'])) {
//            $this->returnJsonMsg('621', [], Common::C('code', '621'));
//        }
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
        $data['source'] = RequestHelper::post('source_type', '0', 'intval');
        if (empty($data['source'])) {
            $this->returnJsonMsg('1033', [], Common::C('code', '1033'));
        }
        if (!in_array($data['source'], ['1','2','3','4'])) {
            $this->returnJsonMsg('902', [], Common::C('code', '902'));
        }
        //获取样品信息
        $sample_model = new Sample();
        $sample_info = $sample_model->getInfo(['id'=>$data['sample_id']], false, 'id,name,status,shop_id,day_count,sms_content,stock,download,sms_valid_time');
        if (empty($sample_info) || $sample_info['status'] !=2) {
            $this->returnJsonMsg('1204', [], Common::C('code', '1204'));
        }
        if ($sample_info['stock'] <= 0) {
            $this->returnJsonMsg('1204', [], '样品已经被领取完毕!');
        }
        //@todo 领取规则，一天只能领取一件样品

        $sample_order_model = new SampleLog();
        $sample_order_where['sample_id'] = $data['sample_id'];
        $sample_order_where['mobile'] = $data['mobile'];

        //今日剩余的计算
        $today = strtotime(date("Y-m-d"));
        $tomorrow = strtotime("+1 day", $today);
        //$today = strtotime(date("Y-m-d") + 1);
        //今日已经领取数量
        $have_receive = $sample_order_model->getCount(['sample_id'=>$data['sample_id']],['between', 'create_time', $today, $tomorrow]);
        //判断今日发放数是否已经领取完了。
        //$sample_info['have_receive'] = $have_receive;
        //超过发放数量
        if ($sample_info['day_count'] <= $have_receive) {
            $this->returnJsonMsg('1212', [], Common::C('code', '1212'));
        }

        //今日剩余可以领取数量
        //$receive_left = $sample_info['day_count'] - $have_receive;
//        if ($receive_left <=0) {
//            $this->returnJsonMsg('1207', [], );
//        }

//        $now_day = date('Y-m-d', time());
//        $next_day = date("Y-m-d", strtotime("+1 day"));
//        $sample_order_and_where = ['and', ['>', 'create_time', $now_day ], ['<', 'create_time', $next_day]];
        $sample_order_rs = $sample_order_model->getInfo($sample_order_where, true, 'id');
        if (!empty($sample_order_rs)) {
            $this->returnJsonMsg('1207', [], Common::C('code', '1207'));
        }
        //判断 同一个商家一天只能领取一个样品
        $today_list = $sample_order_model->find()
            ->select('id,sample_id,shop_id')
            ->where(['mobile'=>$data['mobile']])
            ->andWhere(['between', 'create_time', $today, $tomorrow])
            ->asArray()->all();
        //var_dump($today_list);
        if (!empty($today_list)) {
            //如果今天已经有领取 判断今天领取的这个样品是否在这些商家内
            foreach ($today_list as $k => $v) {
                if ($sample_info['shop_id'] == $v['shop_id']) {
                    $this->returnJsonMsg('1213', [], Common::C('code', '1213'));
                }
            }
        }
        //创建兑换码 8 为随机码
        $sms_code = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $data['sms_code']      = $sms_code;
        //$data['mobile']      = $sms_code;
        $data['shop_id']      = $sample_info['shop_id'];
        $data['status']     = 0;
        $data['create_time']    = time();
        $data['verify_time'] = 0;
        //$data['expired_time'] = time() + 3600 * 24 * $detail['sms_valid_time'];
        $data['expired_time'] = $today + 3600 * 24 * $sample_info['sms_valid_time'];
        //$data['expired_time'] = strtotime('+15 day', $today);
        $data['verify_user_id'] = 0;

        $rs = $sample_order_model->insertInfo($data);
        if (!$rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        //此样品今日可领取数减去相应的值  总库存减去相应的值
        $sample_info->stock = $sample_info->stock - 1;
        $sample_info->download = $sample_info->download + 1;
        if ($sample_info->stock <= 0) {
            $sample_info->stock = 0;
            $sample_info->status = 1;
        }
        $re = $sample_info->save();
       // var_dump($sample_model);
       // var_dump($re);exit();
        //$sample_info['stock'] - 1;

        /**保存短信数据**/
        $user_sms_data['mobile']  = $data['mobile'];
        $sms_content = $sample_info['sms_content'] .'样品验证码：'.$sms_code;
        //$sms_content = "恭喜您成功申请样品:".$sample_info['name']." 领取码为:".$sms_code."请及时到店领取 如非本人操作请忽略本条信息";
        $user_sms_data['content'] = $sms_content;
//        if (!$this->saveUserSms($user_sms_data)) {
//            $this->returnJsonMsg('611', [], Common::C('code', '611'));
//        }
        /**发送短信通道**/
        $this->sendSmsChannel($data['mobile'], $sms_content);

        $this->returnJsonMsg('200', ['sample_id'=>$data['sample_id']], Common::C('code', '200'));
    }

    /**
     * 样品订单列表
     * @return array
     */
    public function actionOrderList()
    {
//        $where['uid'] = RequestHelper::get('uid', '0', 'intval');
//        if (empty($where['uid'])) {
//            $this->returnJsonMsg('621', [], Common::C('code', '621'));
//        }
        $where['mobile'] = RequestHelper::get('mobile', '', '');
        if (empty($where['mobile'])) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($where['mobile'])) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $order_status = RequestHelper::get('order_status', '0', 'intval');

        if (!in_array($order_status, [0, 2])) {
            $this->returnJsonMsg('403', [], Common::C('code', '403'));
        }
        $where['status'] = $order_status;
        $page      = RequestHelper::get('page', '1', 'intval');
        $page_size = RequestHelper::get('page_size', '6', 'intval');
        if ($page_size > Common::C('maxPageSize')) {
            $this->returnJsonMsg('705', [], Common::C('code', '705'));
        }
        $sample_order_model = new SampleLog();
        $sample_order_fields = 'sms_code,sample_id,mobile,create_time,verify_time,expired_time';
        $list = $sample_order_model->getPageList($where, $sample_order_fields, 'id desc', $page, $page_size);
        if (empty($list)) {
            $this->returnJsonMsg('1210', [], Common::C('code', '1210'));
        }
        $sample_model = new Sample();

        foreach ($list as $k => $v) {

            $sample_info = $sample_model->getSampleInfo($v['sample_id']);
            $list[$k]['image'] = !empty($sample_info['image'])?Common::formatImg($sample_info['image']):'';
            $list[$k]['sample_title'] = $sample_info['name'];
            $list[$k]['sample_number'] = 1;
            $list[$k]['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
            $list[$k]['verify_time'] = date("Y-m-d", $v['expired_time']);
//            if (!empty($sample_image)) {
//                $list[$k]['image'] = $this->_formatImg($sample_image);

//            $list[$k]['sample_image'] = $this->_formatImg($v['sample_image']);
        }

        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 样品订单详情
     * @return array
     */
    public function actionOrderDetail()
    {

        $mobile = RequestHelper::get('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $sample_id =  RequestHelper::get('sample_id', '0', '');
        if (empty($sample_id)) {
            $this->returnJsonMsg('1042', [], Common::C('code', '1042'));
        }

        $sample_order_model = new SampleLog();
        $order_info = $sample_order_model->getInfo(['mobile'=>$mobile, 'sample_id'=>$sample_id]);
        if (!empty($order_info)) {
            $sample_model = new Sample();
            $sample_info = $sample_model->getSampleInfo($sample_id);
            $sample_data = [
                'sample_id'=>$sample_id,
                'sample_title'=>$sample_info['name'],
                //'sample_description'=>$this->_formatHtml($sample_info['description']),
                'sample_image'=>Common::formatImg($sample_info['image']),
                'create_time'=>date("Y-m-d H:i:s", $order_info['create_time']),
                'verify_time'=>date("Y-m-d", $order_info['expired_time']),
                'sample_number'=>1,
                'exchange_code'=>$order_info['sms_code'],

                //'sample_title'=>$sample_info['name'],
            ];
            if (empty($sample_info)) {
                $this->returnJsonMsg('1208', [], Common::C('code', '1208'));
            } else {
                //通过商家ID查询商家信息
            $shop_model = new SupplierLocation();
            $shop_where['supplier_id'] = $sample_info['shop_id'];
            //$shop_fields = 'id,name,address,tel';
            $shop_fields = ['id','image','shop_name'=>'name','address','mobile'=>'tel','lng'=>'xpoint', 'lat'=>'ypoint'];
            //var_dump($shop_where);exit();
            $shop_info = $shop_model->getInfo($shop_where, true, $shop_fields);
            $sample_data['shop_id'] = $shop_info['id'];
            $sample_data['shop_name'] = $shop_info['shop_name'];
            $sample_data['shop_address'] = $shop_info['address'];
            $sample_data['shop_mobile'] = $shop_info['mobile'];
            $sample_data['shop_image'] = Common::formatImg($shop_info['image']);
                //var_dump($shop_info);
            if (empty($shop_info)) {
                $this->returnJsonMsg('1205', [], Common::C('code', '1205'));
            }
                $this->returnJsonMsg('200', $sample_data, 'OK');
            }
        } else {
            $this->returnJsonMsg('1210', [], Common::C('code', '1210'));
        }
//        $now_day = date('Y-m-d', time());
//        $next_day = date("Y-m-d", strtotime("+1 day"));
//        $sample_order_and_where = ['and', ['>', 'create_time', $now_day ], ['<', 'create_time', $next_day]];



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
