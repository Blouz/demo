<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-18 上午11:41
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\controllers;

use frontend\models\i500_social\User;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use Yii;
use common\helpers\Common;
use yii\web\Response;

class AppController extends ActiveController
{   
    protected $appee = "";
    protected $params = '';
    public $mobile = '';
    public $channel_id = '';
    public $result = ['code'=>200, 'data'=>[], 'message'=>'OK'];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    public function init()
    {

      
        parent::init();
        //获取请求类型
        $method = Yii::$app->request->getMethod();
        //var_dump(Yii::$app->request->getBodyParams());exit();
        switch ($method) {
            case 'POST':
                $this->params = Yii::$app->request->getBodyParams();
                break;
            case 'PUT' :
                $this->params = Yii::$app->request->getBodyParams();
                break;
            default :
                $this->params = Yii::$app->request->getQueryParams();
                break;
        }
        $this->saveLog(Common::C('returnLogFile'), var_export($this->params, true));
        
    }
    /**
     * 返回JSON格式的数据
     * @param string $code    错误代码
     * @param array  $data    数据
     * @param string $message 错误说明
     * @return array
     */
    protected function returnJsonMsg($code='', $data=[], $message='')
    {
        $arr = [
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ];
        $ret_str = json_encode($arr);
        die($ret_str);
    }
    protected function response()
    {
        return $this->result;
    }
    public function findModel($map)
    {
        $modelClass = $this->modelClass;
        $model = $modelClass::findOne($map);
        if (isset($model)) {
            return $model;
        } else {
            return null;
        }
    }
    public function findAll($map)
    {
        $modelClass = $this->modelClass;
        return $modelClass::findAll($map);

    }
    public function deleteAll($map)
    {
        if (!empty($map) && is_array([$map])) {
            $modelClass = $this->modelClass;
            return $modelClass::deleteAll($map);
        } else {
            return 0;
        }

    }
    public function updateModel($map)
    {
        $model = $this->findModel($map);
        if (!empty($model)) {
            //$model
        }
    }
    /**
     * 分页说明
     *
     * @param array  $map      条件
     * @param array  $andMap   条件
     * @param string  $fields   字段
     * @param bool  $is_array   是否返回数组
     * @param int    $pageSize 条件
     * @param string $order    条件
     * @param int    $sort     条件
     *
     * @return array
     */
    public function getPagedRows($query, $config=[])
    {
        $countQuery = clone $query;
        $count = $countQuery->count();
        $pages=new Pagination(['totalCount' => $count]);
        if(isset($config['pageSize'])) {
            $pages->setPageSize($config['pageSize'], true);
        }

        $rows = $query->offset($pages->offset)->limit($pages->limit);
        if(isset($config['order'])) {
            $rows = $rows->orderBy($config['order']);
        }
        $rows = $rows->asArray()->all();


        return ['item'=>$rows,'count'=>$count,'pageCount'=>$pages->pageCount];

    }
    /**
     * 开启日志
     * @param string $path 路径
     * @param string $data 数据
     * @return bool
     */
    public function saveLog($path = '', $data = '')
    {

        file_put_contents($path, "执行时间：" . date('Y-m-d H:i:s') . " 数据：" . var_export($data, true) . "\n", FILE_APPEND);

    }
    public function checkLogin($mobile, $token)
    {
        $user_model = new User();
        $user_where['mobile']     = $mobile;
        $user_fields = 'id,mobile,status,token,expired_in,channel_id';
        $user_info = $user_model->getInfo($user_where, true, $user_fields);
        if (empty($user_info) || $user_info['status'] == 1) {
            return false;
        } else if ($token != $user_info['token'] || time() > $user_info['expired_in']) {
            return false;
        } else {
            $this->channel_id = ArrayHelper::getValue($user_info, 'channel_id', 0);
            $this->mobile = ArrayHelper::getValue($user_info, 'mobile', 0);
            return true;
        }
//        var_dump($user_info);exit();

    }
}