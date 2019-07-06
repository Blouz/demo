<?php
namespace frontend\modules\v5\controllers;

use frontend\controllers\AppController;
use frontend\models\i500_social\UserBasicInfo;
use Yii;
use yii\helpers\ArrayHelper;


class FriendController extends AppController
{
    public $modelClass = 'frontend\models\i500_social\UserFriends';
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['delete'],$actions['create'],$actions['index']);
        return $actions;
    }
    /**
     * 关注某人
     */
    public function actionCreate()
    {
        $model = new $this->modelClass;
        $data = Yii::$app->request->post();
        $data['uid'] = $this->mobile;
//        var_dump($data);exit();
        $model->load($data, '');
        $re = $model->validate();

        if (!$model->save()) {
            if ($model->hasErrors()) {
                return $model;
            } else {
                $this->result['code'] = 500;
                $this->result['message'] = '网络繁忙';
            }
        } else {


        }
        return $this->result;
    }

    /**
     * 我的好友
     * @return array
     */
    public function actionIndex()
    {
        if (empty($this->mobile)) {
            $this->result['code'] = 422;
            $this->result['message'] = '手机号不能为空';
            return $this->result;
        }
        $map = ['uid'=>$this->mobile, 'status'=>1];
        $list = $this->findAll($map);
        $data = [];
        if(!empty($list)) {
            $user_ids = [$this->mobile];
            foreach ($list as $k => $v) {
                $user_ids[] = $v['fid'];
            }
//            var_dump($user_ids);
            $user_list = UserBasicInfo::find()
                ->select(['mobile','nickname','last_community_id'])
                ->where(['mobile'=>$user_ids])
                ->asArray()->all();
            $user_list = ArrayHelper::index($user_list, 'mobile');
//            var_dump($user_list);
            foreach ($list as $k => $v) {
                $status = (ArrayHelper::getValue($user_list, $v['fid'].'.last_community_id', -1) == $user_list[$this->mobile]['last_community_id'])? 1 : 0;
                $data[] = [
                    'mobile'=>$v['fid'],
                    'nickname'=>ArrayHelper::getValue($user_list, $v['fid'].'.nickname', $v['fid']),
                    'status'=>$status,
                ];
            }
        }
        $this->result['data'] = $data;
        return $this->result;
    }

    /**
     * 取消关注某人
     */
    public function actionDelete($id)
    {
        $modelClass = $this->modelClass;
        $data['uid'] = $this->uid;
        $model = $modelClass::findOne(['uid'=>$this->uid, 'fid'=>$id]);
        if (empty($model)) {
            $this->result['code'] = 422;
            $this->result['message'] = '您还未关注此人';
            return $this->result;
        }
        $re = $model->delete();
        if ($re) {

        } else {
            $this->result['code'] = 500;
            $this->result['message'] = '网络繁忙';
        }
        //UserCount::deleteAll(['']);
        return $this->result;
    }

    /**
     * 附近的人
     */
    public function actionNear(){
        $mobile = Yii::$app->request->get('mobile', '');
        $token = Yii::$app->request->get('token', '');
//        $re = false;
        if (!empty($mobile) && !empty($token)) {
            $this->checkLogin($mobile, $token);
        }
//        var_dump($this->mobile);
//var_dump($re);exit();
        $community_id = Yii::$app->request->get('community_id', 0);
        if (empty($community_id)) {
            $this->result['code'] = 422;
            $this->result['message'] = '无效的小区id';
            return $this->result;
        }
        $user = UserBasicInfo::find()->select(['mobile', 'last_community_id', 'nickname', 'avatar', 'personal_sign'])
            ->where(['last_community_id'=>$community_id])->asArray()->all();
        $data = [];
        if (!empty($user)) {
//            var_dump($this->mobile);
            if (!empty($this->mobile)) {
                foreach ($user as $k=>$v) {
                    if ($v['mobile'] != $this->mobile) {
                        $data[] = $v;
                    }
                }
            } else {
                $data = $user;
            }
        }
        unset($user);
        $this->result['data'] = $data;
        return $this->result;
    }

}
