<?php

/* 
 * @author    wangleilei <wangleilei@i500m.com>
 * @time      16-10-14 上午11:47
 * wangleilei@i500m.com
 */

/**
 * 邻居说列表展示
 *
 * PHP Version 8
 *
 */
namespace frontend\modules\v8\controllers;

///use frontend\controllers\RestController;
use frontend\models\i500_social\Recruit;
use frontend\models\i500m\Community;
use Yii;
use common\helpers\Common;
use common\helpers\SsdbHelper;
use common\helpers\RequestHelper;
use frontend\models\i500_social\ServiceCategory;
use frontend\models\i500_social\ServiceSetting;
use frontend\models\i500_social\UserBasicInfo;
use frontend\models\i500_social\Post;
use frontend\models\i500_social\PostPhoto;
use yii\helpers\ArrayHelper;
use yii\db\Query;

use frontend\models\i500_social\UserOrder;

class PostsController extends BaseController
{
    public function actionPostList()
    {
        //热度邻居说
        $city_id = RequestHelper::post('community_city_id', '', '');
        $comm_id = RequestHelper::post('community_id', '', '');
        $category_id = RequestHelper::post('category_id', '', '');
        $page=RequestHelper::post('page', '', '');
        if($page>0)
        {
            $page = $page - 1;
        }
        $post = new Post();
        $field=array();
        $field[]='i500_post.id';
        $field[]='i500_post.mobile';
        $field[]='i500_post.title';
        $field[]='i500_post.content';
        $field[]='i500_post.post_img';
        //$field[]='i500_post.thumbs';     
        $field[]='i500_post.views';
        $field[]='i500_post.create_time';
        $field[]='i500_user_basic_info.nickname as nickname';
        $field[]='i500_user_basic_info.realname as realname';
        $field[]='i500_user_basic_info.avatar as avatar';
        $field[]='i500_user_basic_info.personal_sign as sign';
        
        //帖子评论数量，包括评论的回复
        $comments = (new Query())->select('count(id)')->from('i500_post_comments'); 
        $field['comments'] = $comments->where('post_id=i500_post.id AND status=1 ');
        
        //邻居说点赞
        $thumbs = (new Query())->select('count(id)')->from('i500_post_thumbs'); 
        $field['zan'] = $thumbs->where('post_id=i500_post.id AND status=1');
                
        $condition[Post::tableName().'.community_city_id'] = $city_id;
        $condition[Post::tableName().'.community_id'] = $comm_id;
        $condition[Post::tableName().'.status'] = '2';
        $condition[Post::tableName().'.is_deleted'] = '2';
        
        $postlist = $post->find()->select($field)
                                ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_post.mobile')
                                ->andwhere($condition)
                                ->orderBy('i500_post.id DESC')
                                ->offset($page)
                                ->limit(10)
                                ->asArray()
                                ->all();
        
        //图片集
        $listarr = array();
        foreach($postlist as $pl)
        {
            $picture_list=array();
            $postid = $pl['id'];
            $pic = PostPhoto::find()->select(['id as pid','photo','width','height'])
                    ->where(['post_id'=>$postid])
                    ->asArray()
                    ->all();
            
            $picture_list['topic'] = $pl;
            $picture_list['pictures'] = $pic;
            $listarr[] = $picture_list;
        }
//        var_dump($listarr);
//        exit;
        //话题列表
        $topic = new ServiceCategory();
        $col = array();
        $col[]='i500_service_category.id as topic_id';
//        $col[]='i500_service_category.pid';
        $col[]='i500_service_category.description';
       
        if(!empty($category_id))
        {
            $map[ServiceCategory::tableName().'.id'] = $category_id;
        }
        $map[ServiceCategory::tableName().'.is_topic'] = '1';
        $map[ServiceCategory::tableName().'.status'] = '2';
        $map[ServiceCategory::tableName().'.is_deleted'] = '2';
        
        $topiclist = $topic->find()->select($col)
                                ->andwhere($map)
                                ->orderBy('i500_service_category.id DESC')
                                ->offset($page)
                                ->limit(10)
                                ->asArray()
                                ->all();
        $result=array();
        $result['postlist']=$listarr;
        $result['topiclist']=$topiclist;
        
        $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
    }
    //邻居说内页
    public function actionTopicinnerList()
    {
        $city_id = RequestHelper::post('community_city_id', '', '');
        $comm_id = RequestHelper::post('community_id', '', '');
        $category_id = RequestHelper::post('category_id', '', '');
        $page=RequestHelper::post('page', '', '');
        if($page>0)
        {
            $page = $page - 1;
        }
        if(empty($category_id))
        {
            $this->returnJsonMsg('5000',[], '话题id不能为空');	
        }
        
        $post = new Post();
        $field=array();
        $field[]='i500_post.id';
        $field[]='i500_post.mobile';
        $field[]='i500_post.title';
        $field[]='i500_post.content';
        $field[]='i500_post.post_img';
        //$field[]='i500_post.thumbs';     
        $field[]='i500_post.views';
        $field[]='i500_post.create_time';
        $field[]='i500_user_basic_info.nickname as nickname';
        $field[]='i500_user_basic_info.realname as realname';
        $field[]='i500_user_basic_info.avatar as avatar';
        $field[]='i500_user_basic_info.personal_sign as sign';
        //帖子评论数量，包括评论的回复
        $comments = (new Query())->select('count(id)')->from('i500_post_comments'); 
        $field['comments'] = $comments->where('post_id=i500_post.id AND status=1 ');
        //邻居说点赞
        $thumbs = (new Query())->select('count(id)')->from('i500_post_thumbs'); 
        $field['zan'] = $thumbs->where('post_id=i500_post.id AND status=1');
                
        $condition[Post::tableName().'.community_city_id'] = $city_id;
        $condition[Post::tableName().'.community_id'] = $comm_id;
        $condition[Post::tableName().'.status'] = '2';
        $condition[Post::tableName().'.is_deleted'] = '2';
        $condition[Post::tableName().'.forum_id'] = $category_id;
        
        
        $hotlist = $post->find()->select($field)
                                ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_post.mobile')
                                ->andwhere($condition)
                                ->orderBy('zan DESC')
                                ->offset(0)
                                ->limit(6)
                                ->asArray()
                                ->all();
        //热门邻居说图片集
        $hotlistarr = array();
        foreach($hotlist as $hl)
        {
            $picture_list=array();
            $post_id = $hl['id'];
            $pic = PostPhoto::find()->select(['id as pid','photo','width','height'])
                    ->where(['post_id'=>$post_id])
                    ->asArray()
                    ->all();
            
            $picture_list['topic'] = $hl;
            $picture_list['pictures'] = $pic;
            $hotlistarr[] = $picture_list;
        }
        //话题      
        $topic = new ServiceCategory();
        $col = array();
        $col[]='i500_service_category.id as topic_id';
        $col[]='i500_service_category.pid';
        $col[]='i500_service_category.description';
      
        
        $map[ServiceCategory::tableName().'.id'] = $category_id;
//        $map[ServiceCategory::tableName().'.is_topic'] = '1';
        $map[ServiceCategory::tableName().'.status'] = '2';
        $map[ServiceCategory::tableName().'.is_deleted'] = '2';
        
        $topiclist = $topic->find()->select($col)
                                ->andwhere($map)
                                ->asArray()
                                ->one();
        $result=array();
        foreach($hotlist as $key=>$value)
        {
            if($value['zan']<10)
            {
                 unset($hotlist[$key]);
            }
        }
        
        //邻居说数量
        $topicnum = Post::find()->where(['forum_id'=>$category_id,'status'=>2,'is_deleted'=>2])->count();
        
        //非热度邻居说
        $posts = new Post();
        $column=array();
        $column[]='i500_post.id';
        $column[]='i500_post.mobile';
        $column[]='i500_post.title';
        $column[]='i500_post.content';
        $column[]='i500_post.post_img';
        //$column[]='i500_post.thumbs';     
        $column[]='i500_post.views';
        $column[]='i500_post.create_time';
        $column[]='i500_user_basic_info.nickname as nickname';
        $column[]='i500_user_basic_info.realname as realname';
        $column[]='i500_user_basic_info.avatar as avatar';
        $column[]='i500_user_basic_info.personal_sign as sign';
        //帖子评论数量，包括评论的回复
        $repeat= (new Query())->select('count(id)')->from('i500_post_comments'); 
        $column['comments'] = $repeat->where('post_id=i500_post.id AND status=1 ');
        //邻居说点赞
        $zan = (new Query())->select('count(id)')->from('i500_post_thumbs'); 
        $column['zan'] = $zan->where('post_id=i500_post.id AND status=1');
                
        $con[Post::tableName().'.community_city_id'] = $city_id;
        $con[Post::tableName().'.community_id'] = $comm_id;
        $con[Post::tableName().'.status'] = '2';
        $con[Post::tableName().'.is_deleted'] = '2';
        $con[Post::tableName().'.forum_id'] = $category_id;
        
        $postlist = $posts->find()->select($column)
                                ->join('LEFT JOIN','i500_user_basic_info','i500_user_basic_info.mobile=i500_post.mobile')
                                ->andwhere($con)
                                ->orderBy('i500_post.id DESC')
                                ->offset($page)
                                ->limit(10)
                                ->asArray()
                                ->all();
        
        //非热门邻居说图片集
        $postlistarr = array();
        foreach($postlist as $pl)
        {
            $pic_list=array();
            $post_id = $pl['id'];
            $pict = PostPhoto::find()->select(['id as pid','photo','width','height'])
                    ->where(['post_id'=>$post_id])
                    ->asArray()
                    ->all();
            
            $pic_list['topic'] = $pl;
            $pic_list['pictures'] = $pict;
            $postlistarr[] = $pic_list;
        }
        
        
        $result['topicnum'] = $topicnum;
        $result['topiclist']=$topiclist;
        $result['popularlist']=$hotlistarr;
        $result['postlist']=$postlistarr;
        
        $this->returnJsonMsg('200', $result, Common::C('code','200','data','[]'));
    }
    //话题列表
    public function actionTopicList()
    {
        $city_id = RequestHelper::post('community_city_id', '', '');
        $comm_id = RequestHelper::post('community_id', '', '');
        $page=RequestHelper::post('page', '', '');
        if($page>0)
        {
            $page = $page - 1;
        }
        $topic = new ServiceCategory();
        $col = array();
        $col[]='i500_service_category.id';
//        $col[]='i500_service_category.pid';
        $col[]='i500_service_category.description';
        //话题下邻居说数量
        $replys= (new Query())->select('count(id)')->from('i500_post'); 
        $col['topnum'] = $replys->where('forum_id=i500_service_category.id AND status=2 AND is_deleted=2');
        
        
        $map[ServiceCategory::tableName().'.is_topic'] = '1';
        $map[ServiceCategory::tableName().'.status'] = '2';
        $map[ServiceCategory::tableName().'.is_deleted'] = '2';
        
        $topiclist = $topic->find()->select($col)
                                ->join('LEFT JOIN','i500_post','i500_post.forum_id = i500_service_category.id')
                                ->andwhere($map)
                                ->orderBy('i500_service_category.id DESC')
                                ->offset($page)
                                ->limit(10)
                                ->asArray()
                                ->all();
         $this->returnJsonMsg('200', $topiclist, Common::C('code','200','data','[]'));
    }
}