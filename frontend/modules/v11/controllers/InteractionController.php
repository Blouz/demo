<?php
/**
 * InteractionController.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/6/19 10:27
 */

namespace frontend\modules\v11\controllers;


use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\UserVisitors;

class InteractionController extends BaseController
{
    /**
     * 互动列表 （评论,点赞，访客）
     *
     * @return array
     */
    public function actionIndex(){
        if (empty($this->mobile)) {
            return $this->returnJsonMsg('604',[],Common::C('code','604'));
        }
        if(!Common::validateMobile($this->mobile)){
            return $this->returnJsonMsg('605',[],Common::C('code','605'));
        }

        //分页
        $page = RequestHelper::post('page','1','intval');
        $page_size = RequestHelper::post('size','10','intval');
        $page = empty($page)?'1':$page;
        $page_size = empty($page_size)?'10':$page_size;

        //评论已读未读
        $user_id = PostComments::find()->select(['id'])->where(['author_mobile'=>$this->mobile,'read'=>0])->asArray()->column();
        if (!empty($user_id)) {
            $post_comments = new PostComments();
            $res = $post_comments->updateInfo(['read'=>1],['id'=>$user_id]);
            if (!$res) {
                return $this->returnJsonMsg('400',[],Common::C('code','400'));
            }
        }

        //点赞已读未读
        $thumbs_id = PostThumbs::find()->select(['id'])->where(['author_mobile'=>$this->mobile,'read'=>0])->asArray()->column();
        if (!empty($thumbs_id)) {
            $post_thumbs = new PostThumbs();
            $res = $post_thumbs->updateInfo(['read'=>1],['id'=>$thumbs_id]);
            if (!$res) {
                return $this->returnJsonMsg('400',[],Common::C('code','400'));
            }
        }

        //访客已读未读
        $visitors_id = UserVisitors::find()->select(['id'])->where(['mobile'=>$this->mobile,'read'=>0])->asArray()->column();
        if (!empty($visitors_id)) {
            $res = UserVisitors::updateAll(['read'=>1],['id'=>$visitors_id]);
            if (!$res) {
                return $this->returnJsonMsg('400',[],Common::C('code','400'));
            }
        }

        //查询当前用户所发的帖子ID
        $post = Post::find()->select(['id'])
                ->where(['mobile'=>$this->mobile,'status'=>2,'is_deleted'=>2])
                ->asArray()
                ->column();

        if (empty($post)) {
            //访客列表
            $list_data = UserVisitors::find()->select(['create_time','visitor_mobile'])
                    ->with(['user'=>function($query) {
                        $query->select(['realname','avatar','mobile','sex','personal_sign']);
                    }])
                    ->where(['mobile'=>$this->mobile])
                    ->orderBy('create_time DESC')
                    ->offset(($page-1)*$page_size)
                    ->limit($page_size)
                    ->asArray()
                    ->all();
            $list = [];
            if($list_data){
                foreach ($list_data as $key =>$value) {
                    if (empty($value['user'])) {
                        continue;
                    }
                    $list[$key]['avatar'] = $value['user']['avatar'];
                    $list[$key]['realname'] = $value['user']['realname'];
                    $list[$key]['sex'] = $value['user']['sex'];
                    $list[$key]['create_time'] = $value['create_time'];
                    $list[$key]['personal_sign'] = $value['user']['personal_sign'];
                    $list[$key]['content'] = "";
                    $list[$key]['diff_id'] = 3;
                    $list[$key]['id'] = $value['visitor_mobile'];
                    $list[$key]['post_content'] = "";
                    $list[$key]['post_img'] = "";
                }
                $list = array_values($list);
            }
        } else {
            $post = implode(',', $post);
            $page_size_count = (int)($page - 1) * $page_size;
            $query = \Yii::$app->db_social;
            //查询互动列表
            $list = $query->createCommand("SELECT
	i500_user_basic_info.`avatar`,i500_user_basic_info.`realname`,i500_user_basic_info.`sex`,tmpA.`create_time`,i500_user_basic_info.`personal_sign`,tmpA.`content`,tmpA.`diff_id`,tmpA.`id`,tmpA.`mobile`
FROM
	(
		(
			SELECT
				`mobile`,
				`create_time`,
				`post_id` AS `id`,
				`content`,
				'1' as diff_id
			FROM
				`i500_post_comments`
			WHERE
				`post_id` IN ({$post})
			AND (`status` = 1)
			AND (`type` = 1)
			AND `author_mobile` = {$this->mobile}
		)
		UNION ALL
        (
            SELECT
                `mobile`,
                `create_time`,
                `post_id` AS `id`,
                 '' as content,
                 '2' as diff_id
            FROM
                `i500_post_thumbs`
            WHERE
                `post_id` IN ({$post})
            AND `author_mobile` = {$this->mobile}
            ORDER BY
                `create_time` DESC
        )
		UNION ALL
        (
            SELECT
                `visitor_mobile` AS `mobile`,
                `create_time`,
                `id`,
                '' as content,
                '3' as diff_id
            FROM
                `i500_user_visitor`
            WHERE
                `mobile` = {$this->mobile}
        )
	) `tmpA`
	INNER JOIN i500_user_basic_info ON i500_user_basic_info.`mobile`=tmpA.`mobile`
ORDER BY
	tmpA.`create_time` DESC
LIMIT {$page_size} OFFSET {$page_size_count}")
                ->queryAll();

        //帖子图片和内容
        if (!empty($list)) {
            foreach ($list as $key =>$value) {
                $list[$key]['post_content'] = '';
                $list[$key]['post_img'] = '';
                if (!empty($value['diff_id']) && $value['diff_id'] != 3){
                    //查询帖子内容和图片
                    $post_data = Post::find()->select(['id','content'])
                        ->where(['mobile'=>$this->mobile,'status'=>2,'is_deleted'=>2,'id'=>$value['id']])
                        ->with(['img'=>function($query) {
                            $query->select(['post_id','photo']);
                        }])
                        ->asArray()
                        ->one();
                    if (!empty($post_data)) {
                        $list[$key]['post_content'] = Common::userTextDecode($post_data['content']);
                        if($post_data['img'] != null){
                            $list[$key]['post_img'] = $post_data['img']['photo'];
                        }
                    }
                }
                if ($value['diff_id']==3) {
                    $list[$key]['id'] = $list[$key]['mobile'];
                }
                unset($list[$key]['mobile']);
            }
        }
        }

        return $this->returnJsonMsg('200',$list,Common::C('code','200'));
    }
}