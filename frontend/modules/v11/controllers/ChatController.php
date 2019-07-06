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

namespace frontend\modules\v11\controllers;


use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\Chat;
use frontend\models\i500_social\ChatContent;
use yii\db\Query;
use common\helpers\CurlHelper;

class ChatController extends BaseController
{
    public function actionRecordChat()
    {
        $data = file_get_contents('php://input');
        $res = json_decode($data,true);
        $sdkappid = Common::C('sdkappid');
        if($res['MsgBody'][0]['MsgType']=="TIMTextElem")
        {
            $uid = $res['From_Account'];
            $comm_id = UserBasicInfo::find()->select(['last_community_id'])
                                  ->join('left join','i500_user','i500_user.mobile=i500_user_basic_info.mobile')
                                  ->where(['i500_user.id'=>$uid])
                                  ->scalar();

            $chat = new Chat();
            $chat->from_user = $res['From_Account'];
            $chat->community_id = (int)$comm_id;
            $type = 0;
            if(isset($res['GroupId']))
            {
                $chat->group_id = $res['GroupId'];
                $type = 1;
            }
            $chat->chat_type = $type;      
//            $chat->platform = $this->get_source($res['OptPlatform']);
            $success = $chat->save(false);
            $chat_id = $chat->primaryKey;
            $content = "";
            if((int)$chat_id>0)
            {
                $chat_content = new ChatContent();
                $chat_content->chat_id = $chat_id;
                $message = $res['MsgBody'];
                $chat_content->content = json_encode($message);
                $chat_content->save(false);
            }
        }
//            if($success)
//            {
                $result = array();
                $result['ActionStatus'] = "OK";
                $result['ErrorInfo'] = "";
                $result['ErrorCode'] = 0;
                return json_encode($result);
//            }
//            else
//            {
//                $result = array();
//                $result['ActionStatus'] = "FAIL";
//                $result['ErrorInfo'] = "保存数据失败";
//                $result['ErrorCode'] = 1;
//                return json_encode($result);
//            }
//        }
    }
    private function get_source($source)
    {
        switch ($source)
        {
            case "Unkown":
                return 0;
            case "RESTAPI":
                return 1;
            case "Web":
                return 2;
            case "Android":
                return 3;
            case "iOS":
                return 4;
            case "Windows":
                return 5;
            case "Mac":
                return 6;
            default :
                return 0;
        }
    }
}