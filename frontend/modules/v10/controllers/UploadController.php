<?php

/* 
 * 
 * @category  Social
 * @package   Post
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      2017
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wangleilei@i500m.com
 */

namespace frontend\modules\v10\controllers;
use common\helpers\Common;
use common\helpers\RequestHelper;
use common\vendor\qiniu\src\Qiniu\Auth;
class UploadController extends BaseController
{
    
    public function actionUpload()
    {
        $key = RequestHelper::post('key', '', '');

//        $size = $_FILES["file"]["size"];

        $accessKey = Common::C('accessKey');
        $secretKey = Common::C('secretKey');
        $res = '';
        if(!empty($_FILES))
        {
            // 构建鉴权对象
            $auth = new Auth($accessKey, $secretKey);
            // 要上传的空间
            $bucket = 'video';
            // 生成上传 Token
            $token = $auth->uploadToken($bucket);
            $url = Common::C('uploadbase');
            $data = array();
            $data['key'] = $key;
            $data['token'] = $token;
            $data['file'] = new \CURLFile($_FILES["file"]['tmp_name']);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1 );
            curl_setopt($ch, CURLOPT_HEADER, 0 );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data );

            $res = curl_exec($ch);
            curl_close ( $ch );
//            return $res;
        }      
        $res = json_decode($res);
        if(empty($res))
        {
            $res = array('hash'=>'','key'=>'');
        }
        $this->returnJsonMsg('200',$res, Common::C('code','200','data','[]'));
    }
}