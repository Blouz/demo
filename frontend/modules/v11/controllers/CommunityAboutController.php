<?php
/**
 * 小区百科
 *
 * PHP Version 5.6
 *
 * @category  Social
 * @package   Login
 * @author    wyy <wyy@i500m.com>
 * @time      2017/05/08
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wyy@i500m.com
 */
namespace frontend\modules\v11\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\CommunityAbout;
use frontend\models\i500_social\CommunityAboutThumbs;

class CommunityAboutController extends BaseController
{
    /**
     * 小区百科首页
     * @param int $qid 小区id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @author wyy
     * @return json
     */
    public function actionIndex() {
        //小区id
        $qid = RequestHelper::post('qid', 0, 'intval');
        if (empty($qid)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
        if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        //获取小区赞最多的百科相册
        $image_info = CommunityAbout::find()->select(['id','imgurl','thumbs_num'])
                        ->where(['qid'=>$qid,'type'=>2,'is_deleted'=>2])
                        ->orderBy('thumbs_num DESC,create_time DESC')
                        ->asArray()
                        ->one();
        $data['image'] = array();
        if (!empty($image_info)) {
            //该小区相册总个数
            $image_count = CommunityAbout::find()->select('id')->where(['qid'=>$qid,'type'=>2,'is_deleted'=>2])->count();
            
            $data['image'] = [array(
                'id' => $image_info['id'],
                'count' => $image_count,
                'imgurl' => $image_info['imgurl'],
                'thumbs_num' => $image_info['thumbs_num'],
            )];
        }
        
        //获取小区赞最多的百科简介列表
        $data['list'] = CommunityAbout::find()->select(['id','mobile','create_time','content','thumbs_num'])
                        ->where(['qid'=>$qid,'type'=>1,'is_deleted'=>2])
                        ->orderBy('thumbs_num DESC,create_time DESC')
                        ->limit(3)
                        ->asArray()
                        ->all();
        //追加信息
        foreach ($data['list'] as $k=>$v) {
            //获取昵称和头像
            $userinfo = UserBasicInfo::find()->select(['nickname','avatar'])->where(['mobile'=>$v['mobile']])->asArray()->one();
            $v['nickname'] = empty($userinfo) ? '' : $userinfo['nickname'];
            $v['avatar'] = empty($userinfo) ? '' : $userinfo['avatar'];
            //是否点击
            $zan = CommunityAboutThumbs::find()->select('id')->where(['mobile'=>$mobile,'about_id'=>$v['id']])->scalar();
            $v['thumbs_is'] = empty($zan) ? 0 : 1;//0未赞 1已赞
            //时间转换
            $v['date'] = date("m月d日",strtotime($v['create_time']));
            $data['list'][$k] = $v;
        }
        
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    /**
     * 小区百科相册
     * @param int $qid 小区id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @param int $page 分页 非必填（默认1）
     * @author wyy
     * @return json
     */
    public function actionImageAll() {
        //小区id
        $qid = RequestHelper::post('qid', 0, 'intval');
        if (empty($qid)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        
        $data['count'] = CommunityAbout::find()->select('id')->where(['qid'=>$qid,'type'=>2,'is_deleted'=>2])->count();
        //根据小区id获取小区图片
        $data['list'] = CommunityAbout::find()->select(['id','mobile','create_time','imgurl','thumbs_num'])
                    ->where(['qid'=>$qid,'type'=>2,'is_deleted'=>2])
                    ->orderBy('create_time DESC')
                    ->offset(($page-1)*20)
                    ->limit(20)
                    ->asArray()
                    ->all();
        //追加信息
        foreach ($data['list'] as $k=>$v) {
            //获取昵称和头像
            $userinfo = UserBasicInfo::find()->select(['nickname','avatar'])->where(['mobile'=>$v['mobile']])->asArray()->one();
            $v['nickname'] = empty($userinfo) ? '' : $userinfo['nickname'];
            $v['avatar'] = empty($userinfo) ? '' : $userinfo['avatar'];
            //是否点击
            $zan = CommunityAboutThumbs::find()->select('id')->where(['mobile'=>$mobile,'about_id'=>$v['id']])->scalar();
            $v['thumbs_is'] = empty($zan) ? 0 : 1;//0未赞 1已赞
            //时间转换
            $v['date'] = (date('Y')==date("Y",strtotime($v['create_time']))?'':date("Y年",strtotime($v['create_time']))).date("m月",strtotime($v['create_time']));
            $data['list'][$k] = $v;
        }
        
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    /**
     * 小区百科简介
     * @param int $qid 小区id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @param int $page 分页 非必填（默认1）
     * @author wyy
     * @return json
     */
    public function actionContentAll() {
        //小区id
        $qid = RequestHelper::post('qid', 0, 'intval');
        if (empty($qid)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        
        $data['count'] = CommunityAbout::find()->select('id')->where(['qid'=>$qid,'type'=>1,'is_deleted'=>2])->count();
        //根据小区id获取小区简介
        $data['list'] = CommunityAbout::find()->select(['id','mobile','create_time','content','thumbs_num'])
                    ->where(['qid'=>$qid,'type'=>1,'is_deleted'=>2])
                    ->orderBy('create_time DESC')
                    ->offset(($page-1)*10)
                    ->limit(10)
                    ->asArray()
                    ->all();
        //追加信息
        foreach ($data['list'] as $k=>$v) {
            //获取昵称和头像
            $userinfo = UserBasicInfo::find()->select(['nickname','avatar'])->where(['mobile'=>$v['mobile']])->asArray()->one();
            $v['nickname'] = empty($userinfo) ? '' : $userinfo['nickname'];
            $v['avatar'] = empty($userinfo) ? '' : $userinfo['avatar'];
            //是否点击
            $zan = CommunityAboutThumbs::find()->select('id')->where(['mobile'=>$mobile,'about_id'=>$v['id']])->scalar();
            $v['thumbs_is'] = empty($zan) ? 0 : 1;//0未赞 1已赞
            //时间转换
            $v['date'] = date("m月d日",strtotime($v['create_time']));
            $data['list'][$k] = $v;
        }
        
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    /**
     * 我的小区百科相册
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @param int $page 分页 非必填（默认1）
     * @author wyy
     * @return json
     */
    public function actionMyImageAll() {
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        
        $data['count'] = CommunityAbout::find()->select('id')->where(['mobile'=>$mobile,'is_deleted'=>2,'type'=>2])->count();
        //获取我的小区百科
        $data['list'] = CommunityAbout::find()->select(['id','create_time','imgurl','thumbs_num'])
                    ->where(['mobile'=>$mobile,'is_deleted'=>2,'type'=>2])
                    ->orderBy('create_time DESC')
                    ->offset(($page-1)*20)
                    ->limit(20)
                    ->asArray()
                    ->all();
        //获取昵称和头像
        $userinfo = UserBasicInfo::find()->select(['nickname','avatar'])->where(['mobile'=>$mobile])->asArray()->one();
        $nickname = empty($userinfo) ? '' : $userinfo['nickname'];
        $avatar = empty($userinfo) ? '' : $userinfo['avatar'];
        //追加信息
        foreach ($data['list'] as $k=>$v) {
            //获取昵称和头像
            $v['nickname'] = $nickname;
            $v['avatar'] = $avatar;
            //是否点击
            $zan = CommunityAboutThumbs::find()->select('id')->where(['mobile'=>$mobile,'about_id'=>$v['id']])->scalar();
            $v['thumbs_is'] = empty($zan) ? 0 : 1;//0未赞 1已赞
            //时间转换
            $v['date'] = (date('Y')==date("Y",strtotime($v['create_time']))?'':date("Y年",strtotime($v['create_time']))).date("m月",strtotime($v['create_time']));
            $data['list'][$k] = $v;
        }
        
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    /**
     * 我的小区百科简介
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @param int $page 分页 非必填（默认1）
     * @author wyy
     * @return json
     */
    public function actionMyContentAll() {
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        
        $data['count'] = CommunityAbout::find()->select('id')->where(['mobile'=>$mobile,'is_deleted'=>2,'type'=>1])->count();
        //获取我的小区百科
        $data['list'] = CommunityAbout::find()->select(['id','create_time','content','thumbs_num'])
                    ->where(['mobile'=>$mobile,'is_deleted'=>2,'type'=>1])
                    ->orderBy('create_time DESC')
                    ->offset(($page-1)*10)
                    ->limit(10)
                    ->asArray()
                    ->all();
        //获取昵称和头像
        $userinfo = UserBasicInfo::find()->select(['nickname','avatar'])->where(['mobile'=>$mobile])->asArray()->one();
        $nickname = empty($userinfo) ? '' : $userinfo['nickname'];
        $avatar = empty($userinfo) ? '' : $userinfo['avatar'];
        //追加信息
        foreach ($data['list'] as $k=>$v) {
            //获取昵称和头像
            $v['nickname'] = $nickname;
            $v['avatar'] = $avatar;
            //是否点击
            $zan = CommunityAboutThumbs::find()->select('id')->where(['mobile'=>$mobile,'about_id'=>$v['id']])->scalar();
            $v['thumbs_is'] = empty($zan) ? 0 : 1;//0未赞 1已赞
            //时间转换
            $v['date'] = date("m月d日",strtotime($v['create_time']));
            $data['list'][$k] = $v;
        }
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    /**
     * 获取小区百科详情
     * @param int $id 小区百科id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @author wyy
     * @return json
     */
    public function actionGetInfo() {
        //详情id
        $id= RequestHelper::post('id', 0, 'intval');
        if (empty($id)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        //根据id获取小区百科详情
        $data = CommunityAbout::find()->select(['id','mobile','type','create_time','content','imgurl','thumbs_num'])->where(['is_deleted'=>2,'id'=>$id])->asArray()->one();
        if (empty($data)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        //获取昵称和头像
        $userinfo = UserBasicInfo::find()->select(['nickname','avatar'])->where(['mobile'=>$data['mobile']])->asArray()->one();
        $data['nickname'] = empty($userinfo) ? '' : $userinfo['nickname'];
        $data['avatar'] = empty($userinfo) ? '' : $userinfo['avatar'];
        //是否点击
        $zan = CommunityAboutThumbs::find()->select('id')->where(['mobile'=>$mobile,'about_id'=>$id])->scalar();
        $data['thumbs_is'] = empty($zan) ? 0 : 1;//0未赞 1已赞
        
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    /**
     * 发布小区百科
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @param int $qid 小区id
     * @param int $type 类型 1简介 2照片
     * @param string $content 简介内容
     * @param string $imgurl 照片url
     * @author wyy
     * @return json
     */
    public function actionAdd() {
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        //小区id
        $qid = RequestHelper::post('qid', 0, 'intval');
        if (empty($qid)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //类型
        $type= RequestHelper::post('type', 0, 'intval');
        if (!in_array($type, array(1,2))) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //简介内容
        $content = RequestHelper::post('content', '', '');
		if (empty($content) && $type==1) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //照片url
        $imgurl = RequestHelper::post('imgurl', '', '');
		if (empty($imgurl) && $type==2) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        
        $about_data = array(
            'qid' => $qid,
            'mobile' => $mobile,
            'type' => $type,
            'content' => $content,
            'imgurl' => $imgurl,
        );
        //插入数据
        $about_model = new CommunityAbout();
        $about_id = $about_model->insertInfo($about_data);
        if (empty($about_id)) {
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        
        return $this->returnJsonMsg('200',[array('id'=>$about_id)],Common::C('code','200'));
    }
    
    /**
     * 删除我的小区百科
     * @param string $id 小区百科id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @author wyy
     * @return json
     */
    public function actionDelete() {
        //详情id
        $id= RequestHelper::post('id', 0, 'intval');
        if (empty($id)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        //根据id获取小区百科详情
        $about = CommunityAbout::findOne(['id'=>$id,'mobile'=>$mobile,'is_deleted'=>2]);
        if (empty($about)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        $about->is_deleted = 1;
        $about->save();
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
    
    /**
     * 小区百科点赞
     * @param string $id 小区百科id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @author wyy
     * @return json
     */
    public function actionThumbs() {
        //详情id
        $id= RequestHelper::post('id', 0, 'intval');
        if (empty($id)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        //根据id获取小区百科详情
        $about = CommunityAbout::findOne(['id'=>$id,'is_deleted'=>2]);
        if (empty($about)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        
        $thumbs = CommunityAboutThumbs::findOne(['mobile'=>$mobile,'about_id'=>$id]);
        //已赞
        if ($thumbs){
            $this->returnJsonMsg('511', [], '已赞');
        }
        
        //插入赞
        $thumbs_data = array(
            'mobile' => $mobile,
            'about_id' => $id,
        );
        $thumbs_model = new CommunityAboutThumbs();
        $thumbs_id = $thumbs_model->insertInfo($thumbs_data);
        if (empty($thumbs_id)) {
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        
        //小区百科赞数+1
        $about->thumbs_num += 1;
        $about->save();
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
}
