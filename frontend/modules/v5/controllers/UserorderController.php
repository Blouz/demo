<?php
/**
 * 服务订单
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Service
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/9/20
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace frontend\modules\v5\controllers;

use common\helpers\CurlHelper;
use frontend\controllers\RestController;
use frontend\models\i500_social\Seek;
use frontend\models\i500_social\ServiceOrderDetail;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserOrder;
use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Service;
use frontend\models\i500_social\ServiceWeekTime;
use frontend\models\i500_social\ServiceOrder;
use frontend\models\i500_social\Order;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\ServiceUnit;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\ServiceOrderEvaluation;
use yii\helpers\ArrayHelper;

/**
 * Service order
 *
 * @category Social
 * @package  Serviceorder
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class UserorderController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\UserOrder';
    /**
     * 预约服务 支持打包
     */
    public function actionAdd()
    {
        $model = new UserOrder();
        $data = Yii::$app->request->post();
       // $service_data = json_decode($data['json_str'], true);
        if (empty($data['service_ids'])) {
            $this->result['code'] = 601;
            $this->result['message'] = '无效的服务id';
        } else {
            //todo  预约的时候 如果服务下架
            $service_data = explode(',', $data['service_ids']);
            $server_list = Service::find()
                ->select(['id','mobile','category_id', 'son_category_id', 'content'=>'description','price', 'unit', 'image','status','community_city_id','community_id'])
                ->where(['id'=>$service_data])->asArray()->all();

            $data['total'] = 0;
            if (empty($server_list)) {
                $this->result['code'] = 601;
                $this->result['message'] = '服务不存在';
            } else {
                if ($data['shop_mobile'] != $server_list[0]['mobile']) {
                    $this->result['code'] = 422;
                    $this->result['message'] = '无效的数据';
                    return $this->response();
                }
                foreach ($server_list as $k => $v) {

                    if($v['mobile'] == $data['mobile']) {
                        $this->result['code'] = 422;
                        $this->result['message'] = '不能预约自己';
                        return $this->response();
                    }
                    if ($v['status'] == 2) {
                        $this->result['code'] = 601;
                        $this->result['message'] = $v['content'].'已经下架';
                        return $this->response();
                    }
                    unset($server_list[$k]['status']);
                    $data['total'] +=$v['price'];
                }

                $data['order_info'] = json_encode($server_list);
                $data['order_type'] = 1;

                $data['order_sn'] = Common::createSn('35', $data['mobile']);
                if (isset($data['order_sn'])) {
                        $model->load($data, '');
                        //var_dump($model->attributes);exit();
                        if (!$model->save()) {
                            if ($model->hasErrors()) {
                                $errors = $model->getFirstErrors();
                                // var_dump($model->errors);
                                $error = array_values($errors);
                                $this->result['code'] = 511;
                                $this->result['message'] = ArrayHelper::getValue($error, 0, 'Error');
                            } else {
                                $this->result['code'] = 500;
                                $this->result['message'] = '系统繁忙!请稍后再试';
                            }
                        } else {
                            $this->result['data'] = [
                                'order_sn'=>$data['order_sn'],
                                'total'=>number_format($data['total'], 2, '.', ''),
                            ];
                        }

                } else {
                    $this->result['code'] = 500;
                    $this->result['message'] = 'channel系统繁忙!请稍后再试';
                }
            }
        }

        return $this->response();
    }

    /**
     * 服务方抢单
     */
    public function actionGetOrder()
    {
        $model = new UserOrder();
        $data = Yii::$app->request->post();
		
	    $qty = 1;
        // $service_data = json_decode($data['json_str'], true);
        if (empty($data['need_id'])) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的需求id';
        } else {

            $need_info = Seek::find()
                ->select(['id','title','mobile','category_id','son_category_id','price','content'=>'description', 'is_receive', 'unit', 'image','status'])
                ->where(['id'=>$data['need_id']])->asArray()->one();
            if($need_info['mobile'] == $data['mobile']) {
                $this->result['code'] = 422;
                $this->result['message'] = '不能预约自己';
                return $this->response();
            }
            //判断是否申请招募
            $user = UserBasicInfo::findOne(['mobile'=>$data['mobile']]);
            if ($user->is_recruit == 0) {
                $this->result['code'] = 405;
                $this->result['message'] = '您还未申请招募，请先申请';
                return $this->response();
            }
            if ($need_info['is_receive'] == 1) {
                $this->result['code'] = 422;
                $this->result['message'] = '已经被抢';
                return $this->response();
            }
            $data['total'] = $need_info['price'];
            if (empty($need_info)) {
                $this->result['code'] = 404;
                $this->result['message'] = '需求不存在';
            } else {
                    if ($need_info['status'] == 2) {
                        $this->result['code'] = 422;
                        $this->result['message'] = $need_info['content'].'已经下架';
                        return $this->response();
                    }
                    unset($need_info['status']);
				$need_info['qty'] = $qty;  //数量
                $need_list[] = $need_info;
                $data['order_info'] = json_encode($need_list);
                $data['order_type'] = 2;//需求订单
                $data['status'] = 1;//被抢单


                $data['order_sn'] = Common::createSn('35', $data['mobile']);
                $data['shop_mobile'] = $data['mobile'];//自己手机号
                $data['mobile'] = $need_info['mobile'];//对方手机号
                if (isset($data['order_sn'])) {
                    $model->load($data, '');
                    //var_dump($model->attributes);exit();
                    if (!$model->save()) {
                        if ($model->hasErrors()) {
                            $errors = $model->getFirstErrors();
                            // var_dump($model->errors);
                            $error = array_values($errors);
                            $this->result['code'] = 422;
                            $this->result['message'] = ArrayHelper::getValue($error, 0, 'Error');
                        } else {
                            $this->result['code'] = 500;
                            $this->result['message'] = '系统繁忙!请稍后再试';
                        }
                    } else {
                        //设置订单已经被抢
                        $re = Seek::updateAll(['is_receive'=>1], ['id'=>$need_info['id']]);
                        if ($re) {
                            try {
                                //获取要推送的channel_id
                                $channel_id = User::find()->select('channel_id')->where(['mobile'=>$need_info['mobile']])->scalar();
                                if (!empty($channel_id)) {
                                    $channel = explode('-', $channel_id);
                                    $data['device_type'] = ArrayHelper::getValue($channel, 0);
                                    $data['channel_id'] = ArrayHelper::getValue($channel, 1);
                                    $data['type'] = 4;
                                    $data['title'] = '恭喜您 您的需求被抢单了，请支付';
                                    $data['description'] = '您的需求被抢单了，请支付';
                                    //$data['title'] = '您有一个新订单';
                                    $channel_url = \Yii::$app->params['channelHost'] . 'v1/push';
                                    $re = CurlHelper::post($channel_url, $data);
                                    if ($re['code'] == 200) {
                                    	file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 推送成功 订单数据：" . $data['order_sn'] . "\n", FILE_APPEND);

                                    } else {
                                    	file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 推送失败 订单数据：" . $data['order_sn'] . "\n", FILE_APPEND);

                                    }
                                }
                            } catch( Exception $e) {
                            	file_put_contents('/tmp/push.log', "执行时间：" . date('Y-m-d H:i:s') . " 推送失败 订单数据：" . var_export($data['order_sn'], true) . "\n", FILE_APPEND);
                            }
                            $this->result['data'] = [
                                'order_sn'=>$data['order_sn'],
                                'total'=>$data['total'],
                            ];
                        } else {
                            $this->result['code'] = 500;
                            $this->result['message'] = '系统繁忙!请稍后再试';
                        }

                    }

                } else {
                    $this->result['code'] = 500;
                    $this->result['message'] = 'channel系统繁忙!请稍后再试';
                }
            }
        }
        return $this->response();

    }
}
