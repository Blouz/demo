<?php
/**
 * 标签相关接口
 *
 * PHP Version 8
 *
 * @category  Social
 * @package   Service
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/02/28
 * @copyright 2016 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\modules\v9\controllers;

use Yii;
use yii\db\Query;
use yii\data\Pagination;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserLabel;
use frontend\models\i500_social\TagClass;
use frontend\models\i500_social\Label;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\Integral;
use frontend\models\i500_social\IntegralLevel;
use frontend\models\i500_social\UserFriends;


class LabelController extends BaseController
{
    /**
     * 查询所有标签
     * @return array()
    **/
    public function actionTagList()
    {
        $label = new Label();
        $tagclass = new TagClass();
        //遍历出所有标签
        $label = $tagclass::find()->select(['id','name'])->where(['is_reveal' => '1'])
               ->with(['label' => function($query) {
                    $query->select(['id', 'label', 'classify_id']);
               }]);
        $data = $label->asArray()->all();
        $this->returnJsonMsg('200', $data, Common::C('code', '200'));
    }
	
	
    /**
     * 查询用户标签
     * @return array()
    **/
    public function actionUserLabel()
    { 
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        $id = RequestHelper::post('id', '', '');

        $label = new Label();
        $userlabel = new UserLabel();
        $tagclass = new TagClass();
        $label_model = $tagclass::find()->select(['id','name'])->where(['is_reveal' => '1'])
                     ->with(['label' => function($query) {
                          $query->select(['id', 'label', 'classify_id']);
                     }])->asArray()->all();
        if (!empty($id)) {
            $user_mobile = User::find()->select(['mobile'])->where(['id'=>$id])->scalar();
        }

        $cond = '0';
        if(isset($user_mobile)) {
            if($mobile != $user_mobile) {
                $cond = '1';
            }
        }

        if(!empty($user_mobile)) {
            $user = $userlabel->find()->select(['label_id'])->where(['mobile'=>$user_mobile])->asArray()->all();
        }else{
            $user = $userlabel->find()->select(['label_id'])->where(['mobile'=>$mobile])->asArray()->all();
        }
        
        foreach($label_model as $k => $v) {
            $k1 = $k;
            foreach($v['label'] as $k => $v) {
                $k2 = $k;
                $id = $v['id'];
                foreach($user as $k => $v) {
                    if ($v['label_id'] == $id) {
                        $label_model[$k1]['label'][$k2]['is_checked'] = '1';
                    }
                } 
            }
        }
        foreach($label_model as $k => $v) {
            $k1 = $k;
            foreach($v['label'] as $k => $v) {
                if(!isset($v['is_checked'])) {
                    $label_model[$k1]['label'][$k]['is_checked'] = '2';
                }
            }
        }
        if ($cond == '1') {
            foreach($label_model as $k => $v) {
                $table_change = array();
                $table_change = ['你'=>'TA'];
                $table_change += ['您'=>'TA'];
                $classname = strtr($v['name'],$table_change);
                $label_model[$k]['name'] = $classname;
            }
        }
        $this->returnJsonMsg('200', $label_model, Common::C('code', '200'));
    }
    
    /**
     * 查询用户标签（新20171027 ios要求list格式）
     * @return array()
    **/
    public function actionUserLabelNew()
    { 
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $user_mobile = RequestHelper::post('user_mobile', '', '');
        $id = RequestHelper::post('id', '', '');

        $label = new Label();
        $userlabel = new UserLabel();
        $tagclass = new TagClass();
        $label_model = $tagclass::find()->select(['id','name'])->where(['is_reveal' => '1'])
                     ->with(['label' => function($query) {
                          $query->select(['id', 'label', 'classify_id']);
                     }])->asArray()->all();
        if (!empty($id)) {
            $user_mobile = User::find()->select(['mobile'])->where(['id'=>$id])->scalar();
        }

        $cond = '0';
        if(isset($user_mobile)) {
            if($mobile != $user_mobile) {
                $cond = '1';
            }
        }

        if(!empty($user_mobile)) {
            $user = $userlabel->find()->select(['label_id'])->where(['mobile'=>$user_mobile])->asArray()->all();
        }else{
            $user = $userlabel->find()->select(['label_id'])->where(['mobile'=>$mobile])->asArray()->all();
        }
        
        foreach($label_model as $k => $v) {
            $k1 = $k;
            foreach($v['label'] as $k => $v) {
                $k2 = $k;
                $id = $v['id'];
                foreach($user as $k => $v) {
                    if ($v['label_id'] == $id) {
                        $label_model[$k1]['label'][$k2]['is_checked'] = '1';
                    }
                } 
            }
        }
        foreach($label_model as $k => $v) {
            $k1 = $k;
            foreach($v['label'] as $k => $v) {
                if(!isset($v['is_checked'])) {
                    $label_model[$k1]['label'][$k]['is_checked'] = '2';
                }
            }
        }
        if ($cond == '1') {
            foreach($label_model as $k => $v) {
                $table_change = array();
                $table_change = ['你'=>'TA'];
                $table_change += ['您'=>'TA'];
                $classname = strtr($v['name'],$table_change);
                $label_model[$k]['name'] = $classname;
            }
        }
        $this->returnJsonMsg('200', [array('list'=>$label_model)], Common::C('code', '200'));
    }
	
	
    /**
     * 用户添加标签
     * @return array()
    **/
    public function actionAddTag()
    {
        $label = new Label();
        $tagclass = new TagClass();
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $label_id = $_POST['label_id'];
        if (empty($label_id)) {
            $this->returnJsonMsg('514', [], Common::C('code', '514'));
        }
        $avatar = RequestHelper::post('avatar', '', '');
        if (empty($avatar)) {
            $this->returnJsonMsg('662', [], Common::C('code', '662'));
        }
        $time = date('Y-m-d H:i:s', time());
        //添加标签
        $tag = json_decode($label_id, true);
        //var_dump($tag);exit;
        foreach($tag as $k => $v)
        {
            $user_model = new UserLabel();
            $cond = $user_model::find()->where(['mobile'=>$mobile, 'label_id'=>$v])->asArray()->one();
            if(empty($cond)) {
                $user = new UserLabel();
                $user->mobile = $mobile;
                $user->label_id = $v;
                $user->create_time = $time;
                $user->status = 2;
                $res1 = $user->save();
                if (!$res1) {
                    $this->returnJsonMsg('400', [], Common::C('code', '400'));
                }
            }
        }
        //更改用户认证级别
        $user = User::find()->select(['step'])->where(['mobile'=>$mobile])->asArray()->one();
        if($user['step'] == '6') {
            $step = User::updateAll(['step'=>'7'], ['mobile'=>$mobile]);
        }

        //上传头像
        $info = UserBasicInfo::find()->where(['mobile'=>$mobile])->asArray()->one();
        if(!empty($info)) {
            $res2 = UserBasicInfo::updateAll(['avatar'=>$avatar],['mobile'=>$mobile]);
            if (!$res2) {
                $this->returnJsonMsg('663', [], Common::C('code', '663'));
            }
        }else{
           $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
	
	
    /**
     * 用户删除标签
     * @return array()
    **/
    public function actionDeleteTag()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
		
        $label_id = $_POST['label_id'];
        if (empty($label_id)) {
            $this->returnJsonMsg('514', [], Common::C('code', '514'));
        }
        $tag = json_decode($label_id, true);
        $user = new UserLabel();   
        foreach($tag as $k => $v)
        {
            $result = $user::find()->where(['mobile'=>$mobile, 'label_id'=>$v])->one();
            $result->delete();
        }  
        if($result) {
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }else{
			$this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
    }
	
	
    /**
     * 用户更新标签
     * @return Array()
    **/
    public function actionUploadTag()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $label_id = $_POST['label_id'];
        if (empty($label_id)) {
            $this->returnJsonMsg('514', [], Common::C('code', '514'));
        }
        $time = date('Y-m-d H:i:s', time());
        $tag = json_decode($label_id, true);
        //删除标签
        $result = UserLabel::deleteAll(['mobile'=>$mobile]);
        //添加标签
        foreach($tag as $k => $v) {
            $user = new UserLabel();
            $user->mobile = $mobile;
            $user->label_id = $v;
            $user->create_time = $time;
            $user->status = 2;
            $res1 = $user->save();
            if (!$res1) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
    /**
     * 用户上传头像
     * @return array()
    **/
    public function actionUploadAvatar()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $avatar = RequestHelper::post('avatar', '', '');
        if (empty($avatar)) {
            $this->returnJsonMsg('662', [], Common::C('code', '662'));
        }
        $info = UserBasicInfo::find()->where(['mobile'=>$mobile])->asArray()->one();
        if(!empty($info)) {
            $res = UserBasicInfo::updateAll(['avatar'=>$avatar],['mobile'=>$mobile]);
        }else{
           $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
         
        if($res) {
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }else{
           $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
    }


    /**
     * 根据标签推荐好友
     * @return array()
     * @author huangdekui
     **/
    public function actionTagFriend(){
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page = RequestHelper::post('page', 1, 'intval');
        $size = RequestHelper::post('size', 10, 'intval');
        //获取小区id
        $community_id = UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$mobile])->asArray()->one();

        //获取当前用户标签
        $data  = UserLabel::find()->select(['label_id'])->where(['mobile'=>$mobile,'status'=>2])->asArray()->column();

        //获取与当前用户有相同标签的用户
        $lab = UserLabel::find()->select([UserLabel::tableName().'.mobile'])->joinWith(['user'=>function($query) use($community_id){
            $query->select([UserBasicInfo::tableName().'.id'])->where(['last_community_id'=>$community_id['last_community_id']]);
        }])
            ->where(['label_id'=>$data,'status'=>2])
            ->andWhere(['<>',UserLabel::tableName().'.mobile',$mobile])
            ->groupBy(UserLabel::tableName().'.mobile')
            ->orderBy('count(label_id) DESC')
            ->limit(20)
            ->asArray()
            ->column();
        if(!empty($lab)){
            //获取有相同标签用户的信息
            $user = UserBasicInfo::find()->select(['mobile','nickname','avatar','age','personal_sign','sex'])
                ->with(['label'=>function($query){
                    $query->select(['mobile','label_id'])->where(['status'=>2])->groupBy('label_id,mobile')
                    ->with(['labelName'=>function($data){
                        $data->select(['id','label','classify_id'])->where(['is_reveal'=>1])->with(['tagClass'=>function($data1){
                            $data1->select(['id','name'])->where(['is_reveal'=>1]);
                        }]);
                    }]);
                }])
                ->where(['mobile'=>$lab,'last_community_id'=>$community_id['last_community_id']])
                ->offset(($page-1) * $size)
                ->limit($size)
                ->asArray()
                ->all();
            if(!empty($user)){
                foreach($user as $key =>$value){
                    $user[$key]['level'] = $this->_getLevel($value['mobile']);
                    $user[$key]['label'] = [];
                    //标签
                    foreach($value['label'] as $k =>$v){
                        //标签名为空
                        if(empty($v['labelName']['label'])) {
                            continue;
                        }
                        //分类标签为空
                        if(empty($v['labelName']['tagClass'])) {
                            continue;
                        }
                        $user[$key]['label'][] = [
                            'label_id' => $v['label_id'],
                            'mobile' => $v['mobile'],
                            'labelName' => [
                                'id' => $v['labelName']['id'],
                                'label' => $v['labelName']['label'],
                            ],
                        ];
                    }
                }
            }
        } else {

            $field[] = 'i500_user_basic_info.id';
            $field[] = 'i500_user_basic_info.mobile';
            $field[] = 'i500_user_basic_info.nickname';
            $field[] = 'i500_user_basic_info.avatar';
            $field[] = 'i500_user_basic_info.sex';
            $field[] = 'i500_user_basic_info.personal_sign';
            $field[] = 'i500_user_basic_info.age';
            //用户个人
            $user_list = UserBasicInfo::find()->select($field)
                        ->with(['label'=>function($query){
                            $query->select(['mobile','label_id'])->where(['status'=>2])->groupBy('label_id,mobile')
                            ->with(['labelName'=>function($data){
                                $data->select(['id','label','classify_id'])->where(['is_reveal'=>1])->with(['tagClass'=>function($data1){
                                    $data1->select(['id','name'])->where(['is_reveal'=>1]);
                                }]);
                            }]);
                        }])
                        ->joinWith(['user' => function ($query) {
                            $query->select(['mobile'])->where(['step' => 8]);
                        }])
                        ->where(['last_community_id' => $community_id])
                        ->andWhere(['<>', UserBasicInfo::tableName() . '.mobile', $mobile])
                        ->offset(($page - 1) * 10)
                        ->limit(10)
                        ->asArray()
                        ->all();
            $user = [];
            if (!empty($user_list)) {
                foreach ($user_list as $key => $value) {
                    if ($value['mobile'] != $mobile) {
                        $user[$key]['nickname'] = $value['nickname'];
                        $user[$key]['avatar'] = $value['avatar'];
                        $user[$key]['personal_sign'] = $value['personal_sign'];
                        $user[$key]['mobile'] = $value['mobile'];
                        $user[$key]['sex'] = $value['sex'];
                        $user[$key]['age'] = $value['age'];
                        $user[$key]['level'] = $this->_getLevel($value['mobile']);
                        
                        $user[$key]['label'] = [];
                        //标签
                        foreach($value['label'] as $k =>$v){
                            //标签名为空
                            if(empty($v['labelName']['label'])) {
                                continue;
                            }
                            //分类标签为空
                            if(empty($v['labelName']['tagClass'])) {
                                continue;
                            }
                            $user[$key]['label'][] = [
                                'label_id' => $v['label_id'],
                                'mobile' => $v['mobile'],
                                'labelName' => [
                                    'id' => $v['labelName']['id'],
                                    'label' => $v['labelName']['label'],
                                ],
                            ];
                        }
                    }
                }
            }
        }

        $this->returnJsonMsg('200', $user, Common::C('code', '200'));
    }

    //获取等级
    private function _getLevel($mobile = ''){
        if(!empty($mobile)){
            $score = Integral::find()->select(['score'])->where(['mobile'=>$mobile])->scalar();
            $level = IntegralLevel::find()->select(['gradation','level_name'])
                ->orderBy('gradation')
                ->asArray()
                ->all();
            if(count($level)>0)
            {
                for($i=0;$i<count($level);$i++)
                {
                    if($score>$level[$i]['gradation'])
                    {
                        continue;
                    }
                    else
                    {
                        $data[] = $level[$i]['level_name'];
                        break;
                    }
                }
            }else{
                $data['level_name'] = "1";
            }
            return $data;
        }
    }
}
?>