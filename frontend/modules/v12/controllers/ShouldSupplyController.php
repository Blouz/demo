<?php
/**
 * ShouleServiceController.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/6/21 15:24
 */

namespace frontend\modules\v12\controllers;


use common\helpers\Common;
use frontend\models\i500_social\User;
use yii\data\Pagination;
use common\helpers\FastDFSHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\ShouldAdv;
use frontend\models\i500_social\ShouldSupply;
use frontend\models\i500_social\ShouldSupplyComments;
use frontend\models\i500_social\ShouldSupplyImage;
use frontend\models\i500_social\UserBasicInfo;

class ShouldSupplyController extends BaseShouldController
{
    public $unit = [
        '次','幅','单','分钟','小时','天','周','月','份','个','课时','其他','未定义'
    ];

    /**
     *  共享广场(服务列表)以及服务搜索/他发布的服务
     *  @return array
     */
    public function actionIndex(){
        $where = [];
        $andwhere = ['status'=>1,'community_id'=>$this->community_id];
        //关键字
        $keywords = RequestHelper::post('keywords','','trim');
        if (!empty($keywords)) {
            $where = ['like','title',$keywords];
        }
        //他的手机号
        $user_mobile = RequestHelper::post('user_mobile', '', 'trim');
		if (!empty($user_mobile) && Common::validateMobile($user_mobile)) {
            $andwhere['mobile'] = $user_mobile;
        }
        //页数
        $page = RequestHelper::post('page', '1', 'intval');
        //个数
        $size = RequestHelper::post('page_size', '10', 'intval');

        //服务列表
        $supply= new ShouldSupply();
        $model = $supply->SupplyList($where,$andwhere,$page,$size,['id','mobile','title','content','price','unit']);
        if (!empty($model)) {
            foreach ($model as $key => $value) {
                if($value['photo']){
                    $model[$key]['photo'] = [current($value['photo'])];
                }
            }
        }
        //计算服务数量
        $count = $supply->SupplyCount($where, $andwhere);
        //查询页数
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setPageSize($size, true);
        //广告图片
        $adv = ShouldAdv::find()->select(['images as image'])
            ->where(['type'=>2])
            ->orderBy('create_time DESC')
            ->asArray()
            ->all();
        $data = [];
        $data['list'] = $model;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        $data['advert'] = empty($adv)?[]:$adv;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 服务详情
     * @return array
     */
    public function actionSupplyDetail(){
        //服务ID
        $did = RequestHelper::post('did','','');
        if (empty($did)) {
            return $this->returnJsonMsg('1010',[],Common::C('code','1010'));
        }

        //详情
        $supply = new ShouldSupply();
        $model = $supply->SupplyList([],['community_id'=>$this->community_id ,'id'=>$did],'','',['id','title','content','price','unit','mobile','status'],2);
        if (empty($model)) {
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }

        //评价列表
        $supply_comments = new ShouldSupplyComments();
        $comments_count = $supply_comments ->CommentList(['is_deleted'=>2,'did'=>$did],3);
        $comments = $supply_comments ->CommentList(['is_deleted'=>2,'did'=>$did],2);

        //个人信息
        $users = UserBasicInfo::find()->select(['nickname','mobile','avatar'])->where(['mobile'=>$model['mobile']])->asArray()->one();

        $user_id = User::find()->select(['id'])->where(['mobile'=>$model['mobile']])->asArray()->scalar();

        $data = [];
        $data['item'] = $model;
        $data['item']['comment_count'] = $comments_count;
        $data['item']['user'] = $users;
        $data['item']['user']['user_id'] = $user_id;
        $data['comment'] = $comments;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 发布服务
     *
     * @return array
     */
    public function actionCreate(){
        //标题 title
        $title = RequestHelper::post('title','','trim');
        if (empty($title)) {
            return $this->returnJsonMsg('1003',[],Common::C('code','1003'));
        }
        //价格 price
        $price = RequestHelper::post('price','','trim');
        $price = $this->returnPrice($price);
        if (empty($price)) {
            $this->returnJsonMsg('1004', [], '请重新输入正确的价格');
        }
        //单位 unit
        $unit = RequestHelper::post('unit','','trim');
        if (empty($unit)) {
            return $this->returnJsonMsg('1005',[],Common::C('code','1005'));
        }

        if (!in_array($unit,$this->unit)) {
            return $this->returnJsonMsg('2027',[],Common::C('code','2027'));
        }
        //服务简介 content
        $content = RequestHelper::post('content','','trim');
        if (empty($content)) {
            return $this->returnJsonMsg('1007',[],Common::C('code','1007'));
        }
        //图片
        if(empty($_FILES)){
            return $this->returnJsonMsg('1002',[],Common::C('code','1002'));
        }
        //用户未认证认证信息
        if (!$this->checkUserCert()) {
            $this->returnJsonMsg('2106', [], Common::C('code', '2106'));
        }

        $model_data = [
            'mobile'=>$this->mobile,
            'community_id'=>$this->community_id,
            'title'=>$title,
            'content'=>$content,
            'price'=>$price,
            'unit'=>$unit
        ];
        //保存数据
        $model = new ShouldSupply();
        $supply_id = $model->insertInfo($model_data);
        if (empty($supply_id)) {
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }

        //保存图片
        if (!empty($_FILES)) {
            $fastDfs = new FastDFSHelper();
            foreach ($_FILES as $k => $v) {
                $rs_data = $fastDfs->fdfs_upload($k);
                if ($rs_data) {
                    $supply_img_data = array(
                        'pid' => $supply_id,
                        'image' => Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'],
                    );
                    $supply_img_model = new ShouldSupplyImage();
                    $supply_img_model->insertInfo($supply_img_data);
                }
            }
        }
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }

    /**
     * 服务价格单位列表
     *
     * @return array
     */
    public function actionUnitList(){
        $data = [];
        $data['list'] = $this->unit;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 删除服务
     * @return array
     */
    public function actionSupplyDel(){
        //服务ID
        $did = RequestHelper::post('did','','');
        if (empty($did)) {
            return $this->returnJsonMsg('1010',[],Common::C('code','1010'));
        }

        //判断是否是自己的服务
        $supply_mobile  = ShouldSupply::find()->select(['mobile'])->where(['id'=>$did])->asArray()->scalar();
        if($this->mobile != $supply_mobile){
            return $this->returnJsonMsg('1067',[],Common::C('code','1067'));
        }
        //删除操作
        $res = ShouldSupply::updateAll(['status'=>3],['id'=>$did,'mobile'=>$this->mobile]);
        if (!$res) {
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}