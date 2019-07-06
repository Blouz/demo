<?php
/**
 * 一行的文件介绍
 *
 * PHP Version 5
 * 可写多行的文件相关说明
 *
 * @category  I500M
 * @package   Member
 * @author    huangdekui <huangdekui@i500m.com>
 * @time      17/2/28
 * @copyright 2017 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      huangdekui@i500m.com
 */
namespace frontend\modules\v9\controllers;

use common\helpers\RequestHelper;
use common\helpers\Common;
use frontend\models\i500_social\Message;
use frontend\models\i500_social\UserFriends;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\UserVisitors;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Post;

class MessageController extends BaseController
{
    /**
     * 系统消息
     * @return array
     */
    public function actionIndex()
    {   
		$mobile = RequestHelper::post('mobile','','');
		$page = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '10', 'intval');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $read = Message::find()->select(['read'])->where(['mobile'=>$mobile])->column();
        if (in_array(0, $read)) {
            $res = Message::updateAll(['read'=>1],['mobile'=>$mobile,'read'=>0]);
            if (!$res) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
        }
        $user_info_time = User::find()->select(['create_time'])->where(['mobile'=>$mobile])->scalar();
		$query = Message::find()->select(['mobile','content','title','create_time','message_type'])
    		->where(['mobile'=>$mobile])->orWhere(['mobile'=>''])
    		->andWhere(['status'=>1])
    		->andWhere(['>=','create_time',$user_info_time])
    		->orderBy('create_time DESC');
		$message = $query->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
		return $this->returnJsonMsg('200',$message,Common::C('code','200'));
	}

    public function actionRead()
    {   
        $mobile = RequestHelper::post('mobile','','');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
//        $user_info_time = User::find()->select(['create_time'])->where(['mobile'=>$mobile])->scalar();
//        $message = Message::find()->select(['mobile','content','title','create_time','message_type'])
//            ->where(['mobile'=>$mobile])->orWhere(['mobile'=>''])
//            ->andWhere(['status'=>1,'read'=>0])
//            ->andWhere(['>=','create_time',$user_info_time])
//            ->count();
//        $message = Message::find()->select(['id'])->where(['mobile'=>$mobile,'read'=>0])->count();
        $friend = UserFriends::find()->select(['id'])->where(['fid'=>$mobile,'read'=>0,'relation_status'=>1])->count();

        $family = UserFriends::find()->select(['id'])->where(['fid'=>$mobile,'read'=>0,'relation_status'=>2])->count();
        //$comments = PostComments::find()->select(['id'])->where(['author_mobile'=>$mobile,'read'=>0])->count();
        //$thumbs = PostThumbs::find()->select(['id'])->where(['author_mobile'=>$mobile,'read'=>0])->count();
        $visitors = UserVisitors::find()->select(['id'])->where(['mobile'=>$mobile,'read'=>0])->count();
        //$interaction = (int)$comments+(int)$thumbs;
        $data = array();
        $data['friend'] = $friend;
        $data['family'] = $family;
        //$data['interaction'] = (string)$interaction;
        $data['visitors'] = $visitors;

        $data['interaction_count'] = '0';
        /*********************************************已读互动消息 start*********************************************************/
        //评论
        $comment_count1 = PostComments::find()->select(['id','post_id'])
                         ->joinWith(['post'=>function($query){
                             $query->select(['id'])->where([Post::tableName().'.status'=>2,'is_deleted'=>2]);
                         }])
                         ->where([
                             'author_mobile' => $mobile,'read'=>1,
                             PostComments::tableName().'.status' => 1,
                             PostComments::tableName().'.type' => 1
                         ])
                         ->count();
        //点赞
        $thumb_count1 = PostThumbs::find()->select(['id','post_id'])
                       ->joinWith(['post'=>function($query){
                            $query->select(['id'])->where([ Post::tableName().'.status'=>2,'is_deleted'=>2]);
                       }])
                       ->where(['author_mobile'=>$mobile,'read'=>1])
                       ->count();
        //访客
        $visitor_count1 = UserVisitors::find()->select(['id'])->where(['mobile'=>$mobile,'read'=>1])->count();
        $interaction_count1 = (int)$comment_count1+(int)$thumb_count1+(int)$visitor_count1;
        //有已读互动消息(访客+评论+点赞)
        if($interaction_count1>0){
            $data['interaction_count'] = '2';
        }
        /*********************************************已读互动消息 end*********************************************************/
        
        /*********************************************未读互动消息 start*********************************************************/
        //评论
        $comment_count = PostComments::find()->select(['id','post_id'])
                         ->joinWith(['post'=>function($query){
                             $query->select(['id'])->where([Post::tableName().'.status'=>2,'is_deleted'=>2]);
                         }])
                         ->where([
                             'author_mobile' => $mobile,'read'=>0,
                             PostComments::tableName().'.status' => 1,
                             PostComments::tableName().'.type' => 1
                         ])
                         ->count();
        //点赞
        $thumb_count = PostThumbs::find()->select(['id','post_id'])
                       ->joinWith(['post'=>function($query){
                            $query->select(['id'])->where([ Post::tableName().'.status'=>2,'is_deleted'=>2]);
                       }])
                       ->where(['author_mobile'=>$mobile,'read'=>0])
                       ->count();
        //访客
        $visitor_count = UserVisitors::find()->select(['id'])->where(['mobile'=>$mobile,'read'=>0])->count();
        $interaction_count = (int)$comment_count+(int)$thumb_count+(int)$visitor_count;
        //互动未读消息
        $data['interactions'] = strval($interaction_count);
        //互动消息(访客+评论+点赞)
        if($interaction_count>0){
            $data['interaction_count'] = '1';
        }
        /*********************************************未读互动消息 end*********************************************************/

        $data['messages_count'] = '0';
        $user_info_time = User::find()->select(['create_time'])->where(['mobile'=>$mobile])->scalar();
        /*********************************************有已读系统消息 start*********************************************************/
        //有已读系统消息
        $messages1 = Message::find()->select(['mobile','content','title','create_time','message_type'])
            ->where(['mobile'=>$mobile,'read'=>1])
            ->andWhere(['status'=>1])
            ->andWhere(['>=','create_time',$user_info_time])
            ->count();
        if($messages1>0){
            $data['messages_count'] = '2';
        }
        /*********************************************有已读系统消息 end*********************************************************/

        /*********************************************有未读系统消息 start*********************************************************/
        //有未读系统消息
        $messages = Message::find()->select(['mobile','content','title','create_time','message_type'])
            ->where(['mobile'=>$mobile,'read'=>0])
            ->andWhere(['status'=>1])
            ->andWhere(['>=','create_time',$user_info_time])
            ->count();
        $data['message'] = strval($messages);
        if($messages>0){
            $data['messages_count'] = '1';
        }
        /*********************************************有未读系统消息 end*********************************************************/
        
        return $this->returnJsonMsg('730',$data,Common::C('code','730'));
    }
}
