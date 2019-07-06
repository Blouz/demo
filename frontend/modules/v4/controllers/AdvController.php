<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-20 下午4:21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\modules\v4\controllers;

use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\controllers\RestController;
use Yii;
use yii\data\ActiveDataProvider;

class AdvController extends RestController
{
    public $modelClass = 'frontend\models\i500_social\Adv';
    public function actions(){
        $actions = parent::actions();
        unset($actions['view']);
        // 使用"prepareDataProvider()"方法自定义数据provider
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }
    /**
     * 根据广告位置获取广告位
     * @return array|ActiveDataProvider
     */
    public function prepareDataProvider()
    {
        //$position_id = RequestHelper::get('id', 0);
        $modelClass = $this->modelClass;
       // $map['position'] = $position_id;
        $map['status'] = 1;
        return new ActiveDataProvider([
            'query' => $modelClass::find()->where($map),
        ]);
    }

    /**
     * 根据广告位置获取广告位
     * @return array|ActiveDataProvider
     */
    public function actionView()
    {
        $position_id = RequestHelper::get('id', 0);
        $modelClass = $this->modelClass;
        if (empty($position_id)) {
            // $this->code = 201;
            $this->result['code'] = 512;
            $this->result['message'] = '广告位不合法';
            return $this->response();
        }
        $map['position'] = $position_id;
        $map['status'] = 1;
       // var_dump($modelClass::find()->where($map)->all());exit();
        $info = $modelClass::find()->where($map)->one();
        if (empty($info)) {
            $this->result['code'] = 404;
            $this->result['message'] = '数据不存在';
            return $this->response();
        } else {
            return $info;
        }

    }

    /**
     * 根据广告位置获取广告位
     * @return array|ActiveDataProvider
     */
    public function actionList()
    {

        $mobile = RequestHelper::get('mobile', 0);
        $modelClass = $this->modelClass;
        if (empty($mobile)) {
            // $this->code = 201;
            $this->result['code'] = 512;
            $this->result['message'] = Common::C('code', '512');
            return $this->response();
        }
        $map['mobile'] = $mobile;
        // var_dump($map);exit();
        return new ActiveDataProvider([
            'query' => $modelClass::find()->where($map),
        ]);
    }
}