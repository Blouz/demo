<?php

/**
 * 版块话题
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Category
 * @author    xuxiaoyu <xuxiaoyu@i500m.com>
 * @time      2017-04-08
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      xuxiaoyu@i500m.com
 */
namespace frontend\modules\v10\controllers;

use Yii;
use common\helpers\Common;
use common\helpers\RequestHelper;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\User;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\db\Query;
/**
 * Service
 *
 * @category Social
 * @package  Service
 */
class CategoryController extends BaseController
{
    public function actionForum()
    {
    	$mobile = RequestHelper::post('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }
		$cate = new ServiceCategory();
		
		$result = $cate->find()->select(['id','name','description'])
							   ->where(['pid'=>0,'is_topic'=>'1','status'=>'2','is_deleted'=>2])
							   ->asArray()
							   ->all();
		$this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
    }

    public function actionServiceCategory()
    {
    	$mobile = RequestHelper::post('mobile', '', '');
        if (!empty($mobile)) {
            if (!Common::validateMobile($mobile)) {
                $this->returnJsonMsg('605', [], Common::C('code', '605'));
            }
        }      
        $community_id = UserBasicInfo::find()->select(['last_community_id'])->where(['mobile'=>$mobile])->scalar();
    	$pid = RequestHelper::post('pid','','');
        if (empty($pid)) {
            $this->returnJsonMsg('667', [], '版块ID不能为空');
        }
		$cate = new ServiceCategory();
		$field=array();
		$field[]='id';
		$field[]='name';
		$field[]='image';
		$field[]='description';
		
		$condition[ServiceCategory::tableName().'.pid'] = $pid;
		$condition[ServiceCategory::tableName().'.is_topic'] = '1';
		$condition[ServiceCategory::tableName().'.status'] = '2';
		$condition[ServiceCategory::tableName().'.is_deleted'] = '2';
		
		$result = $cate->find()->select($field)
							   ->where($condition)
							   ->asArray()
							   ->all();
							   
		for($i=0;$i<count($result);$i++)
		{
			$result[$i]['image'] = Common::C('imgHost').$result[$i]['image'];
		}
		foreach ($result as $k => $v) {
			$result[$k]['views_nm'] = "0";
            $result[$k]['post_nm'] = "0";
            $post_nm = Post::find()->select(['*'])->where(['forum_id'=>$v['id'],'status'=>2,'is_deleted'=>'2','community_id'=>$community_id])->count();
            if(!empty($post_nm)){
                $result[$k]['post_nm'] = $post_nm;
            }
        }
		$this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
    }
}