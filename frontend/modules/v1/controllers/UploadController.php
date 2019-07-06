<?php
/**
 * 上传类
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   UPLOAD
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015-5-27 09:31
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\modules\v1\controllers;

use Yii;
use common\helpers\FastDFSHelper;
use common\helpers\Common;
use yii\web\Controller;

/**
 * 上传类
 *
 * @category Social
 * @package  UPLOAD
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class UploadController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * 上传图片
     * @return array
     */
    public function actionUploadImg()
    {
        if (empty($_FILES)) {
            $rs_arr['code'] = '625';
            $rs_arr['data'] = [];
            $rs_arr['message'] = Common::C('code', '625');
            die(json_encode($rs_arr));
        }
        $fastDfs = new FastDFSHelper();
        $rs_data = $fastDfs->fdfs_upload('file');
        if ($rs_data) {
            $url = Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'];
            $rs_arr['code'] = '200';
            $rs_arr['url']  = $url;
            $rs_arr['message'] = Common::C('code', '200');
            die(json_encode($rs_arr));
        }
        $rs_arr['code'] = '624';
        $rs_arr['url']  = '';
        $rs_arr['message'] = Common::C('code', '624');
        die(json_encode($rs_arr));
    }
}
