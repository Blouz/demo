<?php
/**
 * 百科
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
use frontend\models\i500_social\BaikeSort;
use frontend\models\i500_social\BaikeAbout;
use frontend\models\i500_social\BaikeAboutImg;
use frontend\models\i500_social\BaikeAboutThumbs;
use frontend\models\i500_social\BaikeSearch;
use frontend\models\i500_social\BaikeSearchTemp;
use frontend\models\i500_social\BaikeSortSearch;
use frontend\models\i500_social\BaikeSortSearchTemp;
use common\helpers\FastDFSHelper;

class BaikeController extends BaseController
{
    /**
     * 百科分类
     * @param int $pid 父级id 非必填（默认0）
     * @author wyy
     * @return json
     */
    public function actionSortList() {
        //父级id
        $pid = RequestHelper::post('pid', 0, 'intval');
        
        //根据父id查询分类
        $data = BaikeSort::find()->select(['id','name','pic'])
                    ->where(['pid'=>$pid,'is_deleted'=>2])
                    ->orderBy('rank ASC,id DESC')
                    ->asArray()
                    ->all();
        return $this->returnJsonMsg('200',$data,Common::C('code','200'));
    }
    
    /**
     * 百科搜索
     * @param string $mobile 手机号
     * @param string $search 搜索内容
     * @author wyy
     * @return json
     */
    public function actionSortSearch() {
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        //搜索内容
        $search = RequestHelper::post('search', '', '');
        if (empty($search)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        
        //根据当前手机号获取小区id
        $community_id = UserBasicInfo::find()->select('last_community_id')->where(['mobile'=>$mobile])->scalar();
        $community_id = empty($community_id) ? 0 : $community_id;

        //记录查询内容
        $sdata = array(
            'community_id' => $community_id,
            'content' => $search,
            'mobile' => $mobile,
        );
        (new BaikeSearch())->insertInfo($sdata);
        
        //根据分类名查询分类
        $data['sort'] = BaikeSort::find()->select(['id','name'])
                    ->where(['is_deleted'=>2])
                    ->andWhere(['like','name',$search])
                    ->orderBy('rank ASC,id DESC')
                    ->limit(20)
                    ->asArray()
                    ->all();
        //根据百科标题查询内容
        $data['about'] = BaikeAbout::find()->select(['id','name'])
                    ->where(['is_deleted'=>2,'community_id'=>$community_id])
                    ->andWhere(['like','name',$search])
                    ->orderBy('create_time DESC')
                    ->limit(20)
                    ->asArray()
                    ->all();
        //追加信息
        foreach ($data['about'] as $k=>$v) {
            //获取第一个图片
            $vimgurl = BaikeAboutImg::find()->select('imgurl')->where(['about_id'=>$v['id']])->orderBy('id ASC')->scalar();
            $v['imgurl'] = empty($vimgurl) ? '' : $vimgurl;
            $data['about'][$k] = $v;
        }
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    /**
     * 根据分类id获取百科列表
     * @param int $sort_id 分类id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @param int $page 分页 非必填（默认1）
     * @author wyy
     * @return json
     */
    public function actionAboutList() {
        //分类id
        $sort_id= RequestHelper::post('sort_id', 0, 'intval');
        if (empty($sort_id)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        
        //根据当前手机号获取小区id
        $community_id = UserBasicInfo::find()->select('last_community_id')->where(['mobile'=>$mobile])->scalar();
        $community_id = empty($community_id) ? 0 : $community_id;
        
        //根据分类id获取百科列表
        $data = BaikeAbout::find()->select(['id','name','tel','address','mobile','create_time','thumbs_num'])
                ->where(['is_deleted'=>2,'sort_id'=>$sort_id,'community_id'=>$community_id])
                ->orWhere(['is_deleted'=>2,'sort_id'=>$sort_id,'community_id'=>0])
                ->orderBy('create_time DESC')
                ->offset(($page-1)*10)
                ->limit(10)
                ->asArray()
                ->all();
        //追加信息
        foreach ($data as $k=>$v) {
            //获取第一个图片
            $vimgurl = BaikeAboutImg::find()->select('imgurl')->where(['about_id'=>$v['id']])->orderBy('id ASC')->scalar();
            $v['imgurl'] = empty($vimgurl) ? '' : $vimgurl;
            //获取昵称和头像
            $userinfo = UserBasicInfo::find()->select(['nickname','avatar'])->where(['mobile'=>$v['mobile']])->asArray()->one();
            $v['nickname'] = empty($userinfo) ? '' : $userinfo['nickname'];
            $v['avatar'] = empty($userinfo) ? '' : $userinfo['avatar'];
            //是否点击
            $zan = BaikeAboutThumbs::find()->select('id')->where(['mobile'=>$mobile,'about_id'=>$v['id']])->scalar();
            $v['thumbs_is'] = empty($zan) ? 0 : 1;//0未赞 1已赞
            $data[$k] = $v;
        }
        
        return $this->returnJsonMsg('200',$data,Common::C('code','200'));
    }
    
    /**
     * 百科详情
     * @param string $id 百科id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @author wyy
     * @return json
     */
    public function actionAboutInfo() {
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
        //根据当前手机号获取小区id
        $community_id = UserBasicInfo::find()->select('last_community_id')->where(['mobile'=>$mobile])->scalar();
        $community_id = empty($community_id) ? 0 : $community_id;
        
        //根据id获取百科详情
        $data = BaikeAbout::find()->select(['id','name','tel','address','mobile','create_time','content','thumbs_num','sort_id'])->where(['is_deleted'=>2,'id'=>$id])->asArray()->one();
        if (empty($data)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        
        $sort_info = BaikeSort::find()->select(['id','name'])->where(['id'=>$data['sort_id']])->asArray()->one();
        if (!empty($sort_info)) {
            //统计该百科的分类使用记录
            $sdata = array(
                'mobile' => $mobile,
                'community_id' => $community_id,
                'sort_id' => $sort_info['id'],
                'sort_name' => $sort_info['name'],
            );
            (new BaikeSortSearch())->insertInfo($sdata);
        }
        
        //百科图片
        $data['images'] = BaikeAboutImg::find()->select(['id','imgurl','create_time'])->where(['about_id'=>$id])->orderBy('id ASC')->asArray()->all();
        //获取昵称和头像
        $userinfo = UserBasicInfo::find()->select(['nickname','avatar'])->where(['mobile'=>$data['mobile']])->asArray()->one();
        $data['nickname'] = empty($userinfo) ? '' : $userinfo['nickname'];
        $data['avatar'] = empty($userinfo) ? '' : $userinfo['avatar'];
        //是否点击
        $zan = BaikeAboutThumbs::find()->select('id')->where(['mobile'=>$mobile,'about_id'=>$id])->scalar();
        $data['thumbs_is'] = empty($zan) ? 0 : 1;//0未赞 1已赞
        
        return $this->returnJsonMsg('200',[$data],Common::C('code','200'));
    }
    
    /**
     * 我的百科列表
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @param int $page 分页 非必填（默认1）
     * @author wyy
     * @return json
     */
    public function actionMyAboutList() {
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        //分页
        $page = RequestHelper::post('page', 1, 'intval');
        
        //根据分类id获取百科列表
        $data = BaikeAbout::find()->select(['id','name','tel','address','create_time','thumbs_num'])
                ->where(['is_deleted'=>2,'mobile'=>$mobile])
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
        foreach ($data as $k=>$v) {
            //获取第一个图片
            $vimgurl = BaikeAboutImg::find()->select('imgurl')->where(['about_id'=>$v['id']])->orderBy('id ASC')->scalar();
            $v['imgurl'] = empty($vimgurl) ? '' : $vimgurl;
            //获取昵称和头像
            $v['nickname'] = $nickname;
            $v['avatar'] = $avatar;
            //是否点击
            $zan = BaikeAboutThumbs::find()->select('id')->where(['mobile'=>$mobile,'about_id'=>$v['id']])->scalar();
            $v['thumbs_is'] = empty($zan) ? 0 : 1;//0未赞 1已赞
            $data[$k] = $v;
        }
        
        return $this->returnJsonMsg('200',$data,Common::C('code','200'));
    }
    
    /**
     * 创建百科
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @param int $sort_id 分类id
     * @param string $name 名称(店铺或场地)
     * @param string $tel 联系方式
     * @param string $address 地址(服务者位置)
     * @param string $content 详情内容
     * @param string $about_img 图片集json
     * @author wyy
     * @return json
     */
    public function actionAboutAdd() {
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        //分类id
        $sort_id= RequestHelper::post('sort_id', 0, 'intval');
        if (empty($sort_id)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //名称
        $name = RequestHelper::post('name', '', '');
		if (empty($name)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        //联系方式
        $tel = RequestHelper::post('tel', '', '');
        //地址
        $address = RequestHelper::post('address', '', '');
        //详情内容
        $content = RequestHelper::post('content', '', '');
		if (empty($content)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        
        //上传图片
        if (empty($_FILES)) {
            $this->returnJsonMsg('511', [], Common::C('code', '511'));
        }
        
        //根据当前手机号获取小区id
        $community_id = UserBasicInfo::find()->select('last_community_id')->where(['mobile'=>$mobile])->scalar();
        $community_id = empty($community_id) ? 0 : $community_id;
        
        /* 暂不判断重复
        //百科名称已存在
        $isdata = BaikeAbout::findOne(['is_deleted'=>2,'name'=>$name]);
        if ($isdata) {
            $this->returnJsonMsg('511', [], '百科名称已存在');
        }
        */
        
        $baike_about_data = array(
            'sort_id' => $sort_id,
            'community_id' => $community_id,
            'mobile' => $mobile,
            'name' => $name,
            'tel' => $tel,
            'address' => $address,
            'content' => $content,
        );
        //插入数据
        $baike_about_model = new BaikeAbout();
        $about_id = $baike_about_model->insertInfo($baike_about_data);
        if (empty($about_id)) {
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }

        //上传图片
        if (!empty($_FILES)) {
            $fastDfs = new FastDFSHelper();
            foreach ($_FILES as $k => $v) {
                $rs_data = $fastDfs->fdfs_upload($k);
                if ($rs_data) {
                    $baike_about_img_data = array(
                        'about_id' => $about_id,
                        'imgurl' => Common::C('imgHost').$rs_data['group_name'].'/'.$rs_data['filename'],
                    );
                    $baike_about_img_model = new BaikeAboutImg();
                    $baike_about_img_model->insertInfo($baike_about_img_data);
                }
            }
        }
        
        return $this->returnJsonMsg('200',[array('id'=>$about_id)],Common::C('code','200'));
    }
    
    /**
     * 删除我的百科
     * @param string $id 百科id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @author wyy
     * @return json
     */
    public function actionAboutDelete() {
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
        
        //根据id获取百科详情
        $about = BaikeAbout::findOne(['id'=>$id,'mobile'=>$mobile,'is_deleted'=>2]);
        if (empty($about)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        $about->is_deleted = 1;
        $about->save();
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
    
    /**
     * 百科点赞
     * @param string $id 百科id
     * @param string $mobile 手机号
     * @param string $token 密钥
     * @author wyy
     * @return json
     */
    public function actionAboutThumbs() {
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
        
        //根据id获取百科详情
        $about = BaikeAbout::findOne(['id'=>$id,'is_deleted'=>2]);
        if (empty($about)) {
            $this->returnJsonMsg('404', [], Common::C('code', '404'));
        }
        
        $thumbs = BaikeAboutThumbs::findOne(['mobile'=>$mobile,'about_id'=>$id]);
        //已赞
        if ($thumbs){
            $this->returnJsonMsg('511', [], '已赞');
        }
        
        //插入赞
        $thumbs_data = array(
            'mobile' => $mobile,
            'about_id' => $id,
        );
        $baike_about_thumbs_model = new BaikeAboutThumbs();
        $thumbs_id = $baike_about_thumbs_model->insertInfo($thumbs_data);
        if (empty($thumbs_id)) {
            return $this->returnJsonMsg('400',[],Common::C('code','400'));
        }
        
        //百科赞数+1
        $about->thumbs_num += 1;
        $about->save();
        
        return $this->returnJsonMsg('200',[],Common::C('code','200'));
    }
    
    /**
     * 百科搜索热门词
     * @param string $mobile 手机号
     * @author wyy
     * @return json
     */
    public function actionAboutSearchHot() {
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        //根据当前手机号获取小区id
        $community_id = UserBasicInfo::find()->select('last_community_id')->where(['mobile'=>$mobile])->scalar();
        $community_id = empty($community_id) ? 0 : $community_id;
        
        //根据小区id获取热门关键词
        $data = BaikeSearchTemp::find()->select(['content','num'])
                    ->where(['community_id'=>$community_id])
                    ->orderBy('num DESC')
                    ->limit(9)
                    ->asArray()
                    ->all();
        return $this->returnJsonMsg('200',$data,Common::C('code','200'));
    }
    
    /**
     * 百科搜索热门分类
     * @param string $mobile 手机号
     * @author wyy
     * @return json
     */
    public function actionSortSearchHot() {
        //手机号
        $mobile = RequestHelper::post('mobile', '', '');
		if (empty($mobile)) {
            $this->returnJsonMsg('604', [], Common::C('code', '604'));
        }
        
        //根据当前手机号获取小区id
        $community_id = UserBasicInfo::find()->select('last_community_id')->where(['mobile'=>$mobile])->scalar();
        $community_id = empty($community_id) ? 0 : $community_id;
        
        //根据小区id获取热门分类
        $data = BaikeSortSearchTemp::find()->select(['sort_id','sort_name','num'])
                    ->where(['community_id'=>$community_id])
                    ->orderBy('num DESC')
                    ->limit(9)
                    ->asArray()
                    ->all();
        return $this->returnJsonMsg('200',$data,Common::C('code','200'));
    }
}
