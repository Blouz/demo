<?php
/**
 * 访客
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Post
 * @author    liuyanwei <liuyanwei@i500m.com>
 * @time      2016/8/9
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      liuyanwei@i500m.com
 */
namespace frontend\modules\v8\controllers;

use Yii;
use yii\data\Pagination;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\PostThumbs;
use frontend\models\i500_social\PostComments;
use frontend\models\i500_social\PostPhoto;
use frontend\models\i500_social\UserBasicInfo;
/**
 * Post
 *
 * @category Social
 * @package  Post
 * @author   liuyanwei <liuyanwei@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     liuyanwei@i500m.com
 */
class InteractionController extends BaseController
{
    /**
     * Before
     * @param \yii\base\Action $action Action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }
	

    /**
     * 互动列表
     * @param string $mobile  手机号
     * @param int    $status  状态 1=正常   
     * @param int    $type    类型 1=回帖
     * @return array
     */
    public function actionInteraction()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page = RequestHelper::post('page', '1', 'intval');

        $comment_read = PostComments::find()->select(['read'])->where(['author_mobile'=>$mobile])->column();
        if (in_array(0, $comment_read)) {
            $res1 = PostComments::updateAll(['read'=>1],['author_mobile'=>$mobile,'read'=>0]);
            if (!$res1) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
        }

        $thumb_read = PostThumbs::find()->select(['read'])->where(['author_mobile'=>$mobile])->column();
        if (in_array(0, $thumb_read)) {
            $res2 = PostThumbs::updateAll(['read'=>1],['author_mobile'=>$mobile,'read'=>0]);
            if (!$res2) {
                $this->returnJsonMsg('400', [], Common::C('code', '400'));
            }
        }

        $post = Post::find()->select(['id','content'])
							->where(['mobile'=>$mobile,'status'=>2,'is_deleted'=>2])
							->with(['img'=>function($query) {
                            $query->select(['post_id','photo']);
							}])
							->asArray()
							->all();        
        if (empty($post)) {
            $this->returnJsonMsg('4010',[], '暂无动态');
        }
        foreach ($post as $key => $values) {
            $comment = PostComments::find()->select(['mobile','create_time','content','post_id'])
                        ->where( ['post_id'=>$values['id'],'status'=>1,'type'=>1])
                        ->with(['user'=>function($query) {
                            $query->select(['mobile','nickname','avatar']);
                        }])
                        ->offset(($page-1) * 10)
                        ->limit(10)
                        ->orderBy('create_time DESC')
                        ->asArray()
                        ->all();                                           
            foreach ($comment as $k => $v) {
                $nic = array();
                $nic['avatar'] = $v['user']['avatar'];
                $nic['nickname'] = $v['user']['nickname'];
                $nic['comment'] = Common::userTextDecode($v['content']);
                $nic['create_time'] = $v['create_time'];
                $nic['post_id'] = $v['post_id'];
                $nic['post_content'] = Common::userTextDecode($values['content']);
                $nic['post_img'] = $values['img']['photo'];
                if (!empty($nic['comment'])) {
                    $nic['role'] = 1; //评论
                }
                $nickname[] = $nic;
            }     
        }    
        foreach ($post as $key => $values) {
            $thumbs = PostThumbs::find()->select(['mobile','create_time','post_id'])
                    ->where( ['post_id'=>$values['id']])
                    ->with(['user'=>function($query) {
                        $query->select(['mobile','nickname','avatar']);
                    }])
                    ->offset(($page-1) * 10)
                    ->limit(10)
                    ->orderBy('create_time DESC')
                    ->asArray()
                    ->all();                                   
            foreach ($thumbs as $k => $v) {
                $nic = array();
                $nic['avatar'] = $v['user']['avatar'];
                $nic['nickname'] = $v['user']['nickname'];
                $nic['create_time'] = $v['create_time'];
                $nic['post_id'] = $v['post_id'];
                $nic['post_content'] = Common::userTextDecode($values['content']);
                $nic['post_img'] = $values['img']['photo'];
                if (empty($nic['comment'])) {
                    $nic['role'] = 2; //点赞
                }
                $nickname[] = $nic;
            }
        }
          
        if (!empty($nickname)) {
            $sort = array(
                'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'create_time',       //排序字段
            );
            $arrSort = array();
            foreach ($nickname AS $uniqid => $row) {
                foreach($row AS $key=>$value){
                    $arrSort[$key][$uniqid] = $value;
                }
            }
            if ($sort['direction']) {
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $nickname);
            }   
            $this->returnJsonMsg('200',$nickname, Common::C('code','200','data','[]'));
        }   
        $this->returnJsonMsg('4010',[], '暂无动态');
		
    }
	
	
	
    public function actionCountInter()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        $newthumbs = PostThumbs::find()->andWhere(['author_mobile'=>$mobile,'is_checked'=>0])->count('id');
        $newcomments = PostComments::find()->andWhere(['author_mobile'=>$mobile,'is_checked'=>0])->count('id');
        $newinter = $newthumbs + $newcomments;
        $this->returnJsonMsg('200', $newinter, Common::C('code', '200'));
    }
	
	
	
    public function actionInterChecked()
    {
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        $res = PostThumbs::updateAll(['is_checked'=>1],['author_mobile'=>$mobile]);
        $res = PostComments::updateAll(['is_checked'=>1],['author_mobile'=>$mobile]);
        $this->returnJsonMsg('200', $res, Common::C('code', '200'));
    }
}




