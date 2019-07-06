<?php
/**
 * SupplyCommentController.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/6/22 10:18
 */

namespace frontend\modules\v12\controllers;


use common\helpers\Common;
use yii\data\Pagination;
use common\helpers\FastDFSHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\ShouldSupplyComments;
use frontend\models\i500_social\ShouldSupplyCommentsImage;
use frontend\models\i500_social\ShouldSupplyOrder;

class SupplyCommentController extends BaseShouldController
{
    /**
     * 评价列表
     * @return array
     */
    public function actionList(){
        //服务ID
        $did =  RequestHelper::post('did','','');
        if (empty($did)) {
            return $this->returnJsonMsg('1010',[],Common::C('code','1010'));
        }
        //页数
        $page = RequestHelper::post('page', '1', 'intval');
        //个数
        $size = RequestHelper::post('page_size', '10', 'intval');

        //评价列表
        $info = ShouldSupplyComments::find()->select(['id','mobile','author_mobile','content','create_time'])
            ->with(['photo'=>function($query){
                $query->select(['pid','image']);
            }])
            ->with(['user'=>function($query){
                $query->select(['nickname','mobile','avatar']);
            }])
            ->where(['is_deleted'=>2,'did'=>$did])
            ->orderBy('create_time Desc');

        $list =  $info->offset(($page-1)*$size)->limit($size)->asArray()->all();
        //计算数量
        $count = $info->count();
        //计算页数
        $pages= new Pagination(['totalCount' => $count]);
        $pages->setPageSize($size, true);
        $data = [];
        $data['list'] = $list;
        $data['count'] = $count;
        $data['pageCount'] = $pages->pageCount;
        $data['hasmore'] = ($page < $pages->pageCount) ? 1 : 0;
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }

    /**
     * 服务评价
     *
     * @return array
     */
    public function actionCommentsAdd(){
        //订单号
        $idsn =  RequestHelper::post('idsn','','trim');
        if (empty($idsn)) {
            return $this->returnJsonMsg('1068',[],Common::C('code','1068'));
        }
        //查询订单是否存在
        $sipply_order = ShouldSupplyOrder::find()->select(['id','did','mobile','status'])->where(['idsn'=>$idsn,'mobile'=>$this->mobile])->asArray()->one();
        if (empty($sipply_order)) {
            return $this->returnJsonMsg('1034',[],Common::C('code','1034'));
        }

        if($sipply_order['status'] != 8){
            return $this->returnJsonMsg('2029',[],Common::C('code','2029'));
        }

        //评价内容
        $content =  RequestHelper::post('content','','trim');
        if (empty($content)) {
            return $this->returnJsonMsg('1069',[],Common::C('code','1069'));
        }
        //查询是否已经评价
        $comment = ShouldSupplyComments::find()->select(['id'])->where(['mobile'=>$this->mobile,'oid'=>$sipply_order['id']])->asArray()->one();
        if (!empty($comment)) {
            return $this->returnJsonMsg('1047',[],Common::C('code','1047'));
        }

        //保存数据
        $comments_data = [
            'oid'=>$sipply_order['id'],
            'did'=>$sipply_order['did'],
            'mobile'=>$this->mobile,
            'author_mobile'=>$sipply_order['mobile'],
            'content'=>$content
        ];
        $model = new ShouldSupplyComments();
        $supply_id = $model->insertInfo($comments_data);
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
                    $supply_img_model = new ShouldSupplyCommentsImage();
                    $supply_img_model->insertInfo($supply_img_data);
                }
            }
        }
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}