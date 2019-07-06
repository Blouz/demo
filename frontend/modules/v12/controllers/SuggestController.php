<?php
/**
 * 意见反馈
 * PHP Version 5
 * @category  Social
 * @package   Post
 * @author    wyy <wyy@i500m.com>
 * @time      2017-07-31
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wyy@i500m.com
 */
namespace frontend\modules\v12\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Suggest;
use frontend\models\i500_social\SuggestImg;
use common\helpers\FastDFSHelper;

class SuggestController extends BaseController {
    //意见反馈
    public function actionIndex() {
        $step = User::find()->select(['step'])->where(['mobile'=>$this->mobile,'step'=>8])->asArray()->one();
        if (empty($step)) {
            $this->returnJsonMsg('6001',[],'没有权限');
        }
        //联系人电话
        $contract_mobile = RequestHelper::post('contract_mobile', '', 'trim');
        if(!empty($contract_mobile) && Common::validateMobile($contract_mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        //反馈内容
        $content = RequestHelper::post('content', '', 'trim');
        if(empty($content)) {
            $this->returnJsonMsg('6015', [], '用户意见反馈不能为空');
        }
        //保存信息
        $suggest = new Suggest();
        $suggest->mobile = $this->mobile;
        $suggest->contract_mobile = $contract_mobile;
        $suggest->content = $content;//Common::sens_filter_word($content);
        $suggest->create_time = date("Y-m-d H:i:s");
        $res = $suggest->save();
        //保存失败
        if(empty($res)) {
            $this->returnJsonMsg('500', [], Common::C('code', '500'));
        }
        //上传图片
        if (!empty($_FILES)) {
            $fastDfs = new FastDFSHelper();
            foreach ($_FILES as $k => $v) {
                $rs_data = $fastDfs->fdfs_upload($k);
                if ($rs_data) {
                    $baike_about_img_data = array(
                        'sid' => $suggest->id,
                        'imgurl' => Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'],
                    );
                    $img_model = new SuggestImg();
                    $img_model->insertInfo($baike_about_img_data);
                }
            }
        }
        $this->returnJsonMsg('200', [], Common::C('code', '200'));
    }
}
