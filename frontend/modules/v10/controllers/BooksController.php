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
use yii\helpers\ArrayHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use yii\db\Query;

class BooksController extends BaseController
{
    public function actionGetBookList()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $book_list = $_POST['book_list'];
          
        $book_list = json_decode($book_list,true);
//        var_dump($book_list);
//        exit;
        $key = array();
        $values = array();
        foreach($book_list as $b)
        {
            $key[] = $b['mobile'];
            $values[] = $b['name'];
        }
        $in = array();
        $out = array();
        $full = array();
        $field[] = "nickname";
        $field[] = "avatar";
        $field[] = "mobile";
        $field['invited'] = (new Query())->select('count(id)')->from("i500_user_friends")->where("uid=i500_user_basic_info.mobile and fid=$mobile");
        $field['invite'] = (new Query())->select('count(id)')->from("i500_user_friends")->where("fid=i500_user_basic_info.mobile and uid=$mobile");
        $book = UserBasicInfo::find()->select($field)->where(['mobile'=>$key])->andWhere(['<>','mobile',$mobile])->asArray()->all();
        if(!empty($book))
        {
            foreach($book as $bk)
            {
                if(in_array($bk['mobile'],$key))
                {                   
                    $value = $bk['mobile'];
                    $index = array_search($value,$key);
                    unset($book_list[$index]);
                    $top = array();
                    $top['nickname'] = $bk['nickname'];
                    $top['avatar'] = $bk['avatar'];
                    $top['mobile'] = $bk['mobile'];
                    $top['invite'] = $bk['invite'];
                    $top['invited'] = $bk['invited'];
                    $top['name'] = $values[$index];
                    $in[] = $top;
                }

            }
        }
        foreach($book_list as $bl)
        {
            $out[] = $bl;
        }
        $full['neiber'] = $in;
        $full['phone_book'] = $out;
        $this->returnJsonMsg('200',$full, Common::C('code','200','data','[]'));
    }
    public function actionSetContact()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $open_contact = RequestHelper::post('open_contact', 0,  'intval');
        $res = User::updateAll(['open_contact'=>$open_contact],['mobile'=>$mobile]);
        $this->returnJsonMsg('200',$res, Common::C('code','200','data','[]'));
    }
}