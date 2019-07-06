<?php
/**
 * 头条
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Post
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/05/13
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\modules\v11\controllers;

use Yii;
use yii\db\Query;
use yii\data\Pagination;
use common\helpers\Common;
use yii\helpers\ArrayHelper;
use common\helpers\CurlHelper;
use common\helpers\FastDFSHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\User;
use frontend\models\i500_social\Headlines;
use frontend\models\i500_social\HeadThumbs;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\HeadlinesComments;
use frontend\models\i500_social\HeadAd;
use frontend\models\i500_social\HeadBanner;
use frontend\models\i500_social\LoginLog;
use frontend\models\i500_social\HeadUserAd;
use frontend\models\i500_social\HeadJournal;
use frontend\models\i500_social\HeadJournalPhoto;

/**
 * Head
 *
 * @category Social
 * @package  Head
 * @author   yaoxin <yaoxin@i500m.com>
 * @license  http://www.i500m.com/ license
 * @link     yaoxin@i500m.com
 */
class HeadController extends BaseController
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
     * 社区头条列表
     * @return array
     */
    public function actionHeadList()
    {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '10', 'intval');
        $head = Headlines::find()->select(['id','title','thumbs','share_num','create_time','cover_image','style','special_id'])
                                 ->with(['headspecial'=>function($query){
                                     $query->select(['id','name']);
                                 }])
                                 ->where(['is_deleted'=>2, 'status'=>2])
                                 ->andWhere(['<', 'create_time', date("Y-m-d H:i:s", time())]);
        $count = $head->count();
        $head_list = $head->orderBy('create_time DESC')->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
        $data =  array();

        $pages=new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);
		if($page == 1) {
            $login_count = LoginLog::find()->where(['mobile'=>$this->mobile])->count();

            $ad_count = HeadUserAd::find()->where(['is_open', 'login_count'])->where(['mobile'=>$this->mobile])->asArray()->one();

            if(empty($ad_count)) {
                $is_open = 1;
            }elseif($login_count != $ad_count['login_count']){
                $headuserad = new HeadUserAd();
                $head_user_ad['is_open'] = 1;
                $head_user_ad['login_count'] = $login_count;
                $ret = $headuserad->updateInfo($head_user_ad, ['mobile'=>$this->mobile]);
            }

            $head_ad = HeadUserAd::find()->where(['is_open', 'login_count'])->where(['mobile'=>$this->mobile])->asArray()->one();
            if(!empty($head_ad)) {
                $is_open = $head_ad['is_open'];
            }
            if((int)$is_open == 1) {
                $headad = HeadAd::find()->select(['id','imageurl', 'link'])->where(['status'=>2])->orderBy('create_time Desc')->asArray()->one();
                if(!empty($headad)) {
                    $ad['id'] = '';
                    $ad['title'] = '';
                    $ad['thumbs'] = '';
                    $ad['share_num'] = '';
                    $ad['create_time'] = '';
                    $ad['cover_image'] = '';
                    $ad['style'] = '';
                    $ad['special_id'] = '';
                    $ad['headspecial'] = null;
                    $ad['comment_num'] = '';
                    $ad['is_thumbs'] = '';
                    $ad['is_todays'] = '';
                    $ad['imageurl'] = $headad['imageurl'];
                    $ad['link'] = $headad['link'];
                    $ad['is_ad'] = (string)1;
                    $model = array();
                    foreach($head_list as $k => $v) {
                        if((int)$k > 6) {
                            if((int)$k == 7) {
                                $model[$k] = $ad;
                                $model[$k+1] = $head_list[$k];
                            }else{
                                $model[$k+1] = $head_list[$k];
                            }
                        }else{
                            $model[$k] = $head_list[$k];
                        }
                    }
                }
            }
		}
        if($page == 1) {
            if($is_open == 1) {
                $data['item'] = $model;
            }else{
                $data['item'] = $head_list;
            }
        }else{
            $data['item'] = $head_list;
        }
        

        $banner = [];
        if($page == 1) {
            $banner = HeadBanner::find()->select(['id', 'imageurl', 'head_id'])
                                        ->where(['status'=>2])
                                        ->orderBy('sort Asc')
                                        ->asArray()
                                        ->all();
        }

        if (!empty($data['item'])) {
            $start_time = date("Y-m-d 00:00:00", time());
            $end_time = date("Y-m-d 23:59:59", time());
            $end = Headlines::find()->select(['id'])
                                    ->where(['is_deleted'=>2, 'status'=>2])
                                    ->andWhere(['>' ,'create_time', $start_time])
                                    ->orderBy('create_time Asc')
                                    ->asArray()
                                    ->one();
            foreach ($data['item'] as $key => $value) {
				if(!empty($value['id'])) {
					$headcomment = HeadlinesComments::find()->where(['head_id'=>$value['id'], 'status'=>2])->count();
					$data['item'][$key]['comment_num'] = $headcomment;
					$data['item'][$key]['is_thumbs'] = $this->_checkPostThumbs($this->mobile, $value['id']);
					if($value['id'] == $end['id']) {
						$data['item'][$key]['is_todays'] = (string)1;
					}else{
						$data['item'][$key]['is_todays'] = (string)2;
					}
					$data['item'][$key]['imageurl'] = '';
					$data['item'][$key]['link'] = '';
					$data['item'][$key]['is_ad'] = (string)2;
				}
            }
        }
        $data['count'] = $count;
        $data['pageCount']=$pages->pageCount;

        $banner_model['banner'] = $banner;
        $list[] = $data;
        $list[] = $banner_model;
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }


    /**
     * 邻居说最新三条
     * @return array
     */
    public function actionHeadHot()
    {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }
        $page = 1;
        $page_size = 3;
        $head = Headlines::find()->select(['id','title','thumbs','share_num','create_time','cover_image','style'])
        ->where(['is_deleted'=>2, 'status'=>2]);
        $count = $head->count();
        $head_list = $head->orderBy('create_time DESC')->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
        $data =  array();
    
        $pages=new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);
        if($page == 1) {
            $login_count = LoginLog::find()->where(['mobile'=>$this->mobile])->count();
    
            $ad_count = HeadUserAd::find()->where(['is_open', 'login_count'])->where(['mobile'=>$this->mobile])->asArray()->one();
    
            if(empty($ad_count)) {
                $is_open = 1;
            }elseif($login_count != $ad_count['login_count']){
                $headuserad = new HeadUserAd();
                $head_user_ad['is_open'] = 1;
                $head_user_ad['login_count'] = $login_count;
                $ret = $headuserad->updateInfo($head_user_ad, ['mobile'=>$this->mobile]);
            }
    
            $head_ad = HeadUserAd::find()->where(['is_open', 'login_count'])->where(['mobile'=>$this->mobile])->asArray()->one();
            if(!empty($head_ad)) {
                $is_open = $head_ad['is_open'];
            }
            if((int)$is_open == 1) {
                $headad = HeadAd::find()->select(['id','imageurl', 'link'])->where(['status'=>2])->asArray()->one();
                $ad['id'] = '';
                $ad['title'] = '';
                $ad['thumbs'] = '';
                $ad['share_num'] = '';
                $ad['create_time'] = '';
                $ad['cover_image'] = '';
                $ad['style'] = '';
                $ad['comment_num'] = '';
                $ad['is_thumbs'] = '';
                $ad['is_todays'] = '';
                $ad['imageurl'] = $headad['imageurl'];
                $ad['link'] = $headad['link'];
                $ad['is_ad'] = (string)1;
                $model = array();
                foreach($head_list as $k => $v) {
                    if((int)$k > 6) {
                        if((int)$k == 7) {
                            $model[$k] = $ad;
                            $model[$k+1] = $head_list[$k];
                        }else{
                            $model[$k+1] = $head_list[$k];
                        }
                    }else{
                        $model[$k] = $head_list[$k];
                    }
                }
            }
        }
        if($page == 1) {
            if($is_open == 1) {
                $data['item'] = $model;
            }else{
                $data['item'] = $head_list;
            }
        }else{
            $data['item'] = $head_list;
        }
    
        if (!empty($data['item'])) {
            $start_time = date("Y-m-d 00:00:00", time());
            $end_time = date("Y-m-d 23:59:59", time());
            $end = Headlines::find()->select(['id'])
            ->where(['is_deleted'=>2, 'status'=>2])
            ->andWhere(['>' ,'create_time', $start_time])
            ->orderBy('create_time Asc')
            ->asArray()
            ->one();
            foreach ($data['item'] as $key => $value) {
                if(!empty($value['id'])) {
                    $headcomment = HeadlinesComments::find()->where(['head_id'=>$value['id'], 'status'=>2])->count();
                    $data['item'][$key]['comment_num'] = $headcomment;
                    $data['item'][$key]['is_thumbs'] = $this->_checkPostThumbs($this->mobile, $value['id']);
                    if($value['id'] == $end['id']) {
                        $data['item'][$key]['is_todays'] = (string)1;
                    }else{
                        $data['item'][$key]['is_todays'] = (string)2;
                    }
                    $data['item'][$key]['imageurl'] = '';
                    $data['item'][$key]['link'] = '';
                    $data['item'][$key]['is_ad'] = (string)2;
                }
            }
        }
        $data['count'] = $count;
        $data['pageCount']=$pages->pageCount;
        $list[] = $data;
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }
    
    /**
     * 头条详情
     * @param int    $post_id 帖子ID
     * @param int    $type    类型 1=详情页调用
     * @return array
     */
    public function actionHeadView()
    {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $head_id = RequestHelper::post('head_id', '', '');
        if (empty($head_id)) {
            $this->returnJsonMsg('6022', [], '头条ID不能为空');
        }

        $data = Headlines::find()->select(['id','title','thumbs','share_num','create_time','content','cover_image','style','views'])
                                 ->where(['is_deleted'=>2,'status'=>2, 'id'=>$head_id])
                                 ->asArray()
                                 ->one();
        if (!empty($data)) {
            $start_time = strtotime(date("Y-m-d 00:00:00", time()));
            $end_time = strtotime(date("Y-m-d 23:59:59", time()));
            $headcomment = HeadlinesComments::find()->where(['head_id'=>$data['id'], 'status'=>2, 'is_sift'=>2])->count();
            $data['comment_num'] = $headcomment;
            $data['content'] = strip_tags($data['content']);
            $data['is_thumbs'] = $this->_checkPostThumbs($this->mobile, $data['id']);
            if(strtotime($data['create_time']) > $start_time && strtotime($data['create_time']) < $end_time) {
                $data['is_todays'] = (string)1;
            }else{
                $data['is_todays'] = (string)2;
            }
        }
        
        $view[] = $data;
        $this->returnJsonMsg('200', $view, Common::C('code', '200'));
    }
    /**
     * 为头条点赞
     * @return array
     */
    public function actionThumbsForHead()
    {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $head_id = RequestHelper::post('head_id', '0', 'intval');
        if (empty($head_id)) {
            $this->returnJsonMsg('6022', [], '头条ID不能为空');
        }

        $head_rs = Headlines::find()->select(['id','thumbs'])->where(['id'=>$head_id,'status'=>2,'is_deleted'=>2])->asArray()->one();
        if (empty($head_rs)) {
            $this->returnJsonMsg('6023', [], '头条不存在');
        }
        $head_thumbs = new HeadThumbs();
        $head_thumbs_where['mobile']  = $this->mobile;
        $head_thumbs_where['head_id'] = $head_id;
        $head_thumbs_info = $head_thumbs->getInfo($head_thumbs_where, true, 'id');
        if (!empty($head_thumbs_info)) {
            $this->returnJsonMsg('6024', [], '已对该头条点过赞');
        }

        $rs = $this->_setPostNumber($head_id, $head_rs['thumbs']+1, '1');
        $head_thumbs_add_data['mobile']  = $this->mobile;
        $head_thumbs_add_data['head_id'] = $head_id;
        $add_rs = $head_thumbs->insertInfo($head_thumbs_add_data);
        if (!$rs || !$add_rs) {
            $this->returnJsonMsg('400', [], Common::C('code', '400'));
        }
        $rs_data['thumbs'] = Common::formatNumber($head_rs['thumbs']+1);
        //记录用户活跃时间
        $this->saveUserActiveTime(['mobile'=>$this->mobile]);

        $date[] = $rs_data;

        $this->returnJsonMsg('200', $date, Common::C('code', '200'));
    }

    /**
     * 评论列表
     * @return array
     */
    public function actionCommentList()
    {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $head_id = RequestHelper::post('head_id', '0', 'intval');
        if (empty($head_id)) {
            $this->returnJsonMsg('6022', [], '头条ID不能为空');
        }

        $head_rs = Headlines::find()->select(['id','thumbs'])->where(['id'=>$head_id,'status'=>2,'is_deleted'=>2])->asArray()->one();
        if (empty($head_rs)) {
            $this->returnJsonMsg('6023', [], '头条不存在');
        }

        $data = HeadlinesComments::find()->select(['id', 'mobile', 'content', 'create_time', 'author_mobile'])
                                         ->with(['userbasicinfo'=>function($query) {
                                             $query->select(['nickname','avatar', 'mobile']);
                                         }])
                                         ->where(['head_id'=>$head_id, 'status'=>2, 'is_sift'=>2])
                                         ->orderBy('create_time Desc')
                                         ->asArray()
                                         ->all();
        $list['comment'] = $data;
        $list['count'] = count($data);
        $model[] = $list;
        $this->returnJsonMsg('200', $model, Common::C('code', '200'));
    }

    /**
     * 发布评论
     * @return array
     */
    public function actionReleaseComments()
    {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $head_id = RequestHelper::post('head_id', '0', 'intval');
        if (empty($head_id)) {
            $this->returnJsonMsg('6022', [], '头条ID不能为空');
        }

        $content = RequestHelper::post('content', '', '');
        if (empty($content)) {
            $this->returnJsonMsg('6032', [], '评论不能为空');
        }

        $head_rs = Headlines::find()->select(['id'])->where(['id'=>$head_id,'status'=>2,'is_deleted'=>2])->asArray()->one();
        if (empty($head_rs)) {
            $this->returnJsonMsg('6023', [], '头条不存在');
        }

        $headcomments = new HeadlinesComments();
        $headcomments->head_id = $head_id;
        $headcomments->mobile = $this->mobile;
        $headcomments->content = $content;
        $re = $headcomments->save();
        if(!$re) {
            $this->returnJsonMsg('6025', [], '发布评论失败');
        }else{
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }
    }

    /**
     * 头条投稿
     * @return array
     */
    public function actionReleaseHead()
    {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $content = RequestHelper::post('content', '', '');
        if (empty($content)) {
            $this->returnJsonMsg('6034', [], '内容不能为空');
        }


        $head = new HeadJournal();
        $head->content = $content;
        $head->mobile = $this->mobile;
        $re = $head->save();
        if(!$re) {
            $this->returnJsonMsg('6025', [], '投稿失败');
        }else{
            $head_id = $head->primaryKey;
            if (!empty($_FILES)) {

                $fastDfs = new FastDFSHelper();
                $keys = [
                    'head_journal_id',
                    'photo',
                    'width',
                    'height',
                ];
                foreach ($_FILES as $k => $v) {
                    $rs_data = $fastDfs->fdfs_upload($k);
                    $size = [];
                    if (!empty($v['name'])) {
                        $tmp = explode('.' ,$v['name']);
                        if (count($tmp) == 2) {
                            $size = explode('_' ,$tmp[0]);
                        }
                    }
                    $width = ArrayHelper::getValue($size, 0, 0);
                    $height = ArrayHelper::getValue($size, 1, 0);

                    if ($rs_data) {
                        $data_img[] = [
                            $head_id,
                            Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'],
                            $width,
                            $height
                        ];
                    }
                }
                if (!empty($data_img)) {
                   $res =  Yii::$app->db_social->createCommand()->batchInsert(HeadJournalPhoto::tableName(), $keys, $data_img)->execute();
                }
            }
            $this->returnJsonMsg('200', [], Common::C('code', '200'));
        }
    }
    /**
     * 专题头条
     * @return array
     */
    public function actionSpecialHead()
    {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $special_id = RequestHelper::post('special_id', '', '');
        if (empty($special_id)) {
            $this->returnJsonMsg('6036', [], '专题ID不能为空');
        }

        $page = RequestHelper::post('page', '1', 'intval');
        $page_size = RequestHelper::post('page_size', '10', 'intval');
        $head = Headlines::find()->select(['id','title','thumbs','share_num','create_time','cover_image','style'])
                                 ->where(['is_deleted'=>2, 'status'=>2, 'special_id'=>$special_id])
                                 ->andWhere(['<', 'create_time', date("Y-m-d H:i:s", time())]);
        $count = $head->count();
        $head_list = $head->orderBy('create_time DESC')->offset(($page-1) * $page_size)->limit($page_size)->asArray()->all();
        $data =  array();
        $data['item'] = $head_list;
        $pages=new Pagination(['totalCount' => $count]);
        $pages->setPageSize($page_size, true);

        if (!empty($data['item'])) {
            foreach ($data['item'] as $key => $value) {
                $headcomment = HeadlinesComments::find()->where(['head_id'=>$value['id'], 'status'=>2])->count();
                $data['item'][$key]['comment_num'] = $headcomment;
                $data['item'][$key]['is_thumbs'] = $this->_checkPostThumbs($this->mobile, $value['id']);
            }
        }
        $data['count'] = $count;
        $data['pageCount']=$pages->pageCount;

        $list[] = $data;
        $this->returnJsonMsg('200', $list, Common::C('code', '200'));
    }

    /**
     * 分享数量及记录
     * @return array
     */
    public function actionShare() {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $head_id = RequestHelper::post('head_id','0','intval');
        if(empty($head_id)){
            $this->returnJsonMsg('6022', [], '头条ID不能为空');
        }

        $res = Headlines::updateAllCounters(['share_num'=>+1],['id'=>$head_id]);
        if($res) {
            $this->returnJsonMsg('200', [] , Common::C('code', '200'));
        }else{
            $this->returnJsonMsg('400', [] , Common::C('code', '400'));
        }
    }

    /**
     * 关闭广告
     * @return array
     */
    public function actionCloseAd() {
        if (empty($this->mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        if (!Common::validateMobile($this->mobile)) {
            $this->returnJsonMsg('605', [], Common::C('code', '605'));
        }

        $info = HeadUserAd::find()->where(['mobile'=>$this->mobile])->asArray()->one();
        $count = LoginLog::find()->where(['mobile'=>$this->mobile])->count();
        if(empty($info)) {
            $user_ad = new HeadUserAd();
            $user_ad->mobile = $this->mobile;
            $user_ad->status = '2';
            $user_ad->is_open = '2';
            $user_ad->login_count = $count;
            $res = $user_ad->save();
        }else{
            $headuserad = new HeadUserAd();
            $head_user_ad['is_open'] = '2';
            $head_user_ad['login_count'] = $count;
            $res = $headuserad->updateInfo($head_user_ad, ['mobile'=>$this->mobile]);
        }

        if($res) {
            $this->returnJsonMsg('200', [] , Common::C('code', '200'));
        }else{
            $this->returnJsonMsg('400', [] , Common::C('code', '400'));
        }
    }
    /**
     * 设置头条相关数量
     * @param int $post_id 帖子ID
     * @param int $num     相关数量
     * @param int $type    操作类型 1=点赞 2=查看
     * @return bool
     */
    private function _setPostNumber($head_id = 0, $num = 0, $type = 1)
    {
        $head_model = new Headlines();
        $head_where['id'] = $head_id;
        if ($type == '1') {
            /**点赞**/
            $head_update['thumbs'] = $num;
        } else {
            /**查看**/
            $head_update['views']  = $num;
        }
        return $head_model->updateInfo($head_update, $head_where);
    }
    /**
     * 判断当前用户头条是否点赞
     * @param string $mobile  手机号
     * @param int    $post_id 帖子ID
     * @return int
     */
    private function _checkPostThumbs($mobile = '', $head_id = 0)
    {
        $head_thumbs_model = new HeadThumbs();
        $head_thumbs_where['mobile']  = $mobile;
        $head_thumbs_where['head_id'] = $head_id;
        $head_thumbs_fields = 'id';
        $head_thumbs_info = $head_thumbs_model->getInfo($head_thumbs_where, true, $head_thumbs_fields);
        if (empty($head_thumbs_info)) {
            return '0';
        }
        return '1';
    }
}
