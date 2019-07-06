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
namespace frontend\modules\v8\controllers;

use frontend\models\i500m\Sample;
use frontend\models\i500m\SampleLog;
use frontend\models\i500m\SampleImage;
// use frontend\models\i500m\SampleLog;
use frontend\models\i500_social\User;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use yii\helpers\ArrayHelper;

/**
 * ChatSample
 *
 * @category Social
 * @package  Sample
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class ChatSampleController extends BaseController
{
    /**
     * 地铁聊天室领取样品
     * @return array
     */
    public function actionReceive()
    {
        $openid = RequestHelper::post('openid', '', '');
        if (empty($openid)) {
            $this->returnJsonMsg('604', [], '错误参数');
        }
        $data['sample_id'] = RequestHelper::post('sample_id', '0', 'intval');
        if (empty($data['sample_id'])) {
            $this->returnJsonMsg('1203', [], Common::C('code', '1203'));
        }

        //通过微信openid获取用户信息
        $user_model = new User();
        $userinfo = $user_model->getInfo(['openid'=>$openid], false, 'mobile');
        if(empty($userinfo)){
        	$this->returnJsonMsg('604', [], '无效用户');
        }

        $data['mobile'] = $userinfo['mobile'];

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
     * 样品列表
     * @return array
     */
    public function actionList()
    {

        $page = RequestHelper::get('page', '1', 'intval');
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
        //获取样品ID
        $sample = new Sample();
        $samples_where['status'] = 2;

        $supplier_sample_fields = 'id,name,shop_id';
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

            $list[$l_key]['image'] = $this->_formatImg(ArrayHelper::getValue($image_list, $l_val['id'].'.image', ''));
            $list[$l_key]['price'] = '0';
            $list[$l_key]['receive_type'] = '到店';
        }
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
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
}
