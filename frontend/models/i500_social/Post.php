<?php

namespace frontend\models\i500_social;

use Yii;

/**
 * This is the model class for table "{{%i500_post}}".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $mobile
 * @property integer $forum_id
 * @property string $title
 * @property string $post_img
 * @property integer $thumbs
 * @property integer $views
 * @property integer $top
 * @property integer $community_city_id
 * @property integer $community_id
 * @property integer $status
 * @property integer $is_deleted
 * @property string $create_time
 */
class Post extends \yii\db\ActiveRecord
{
    public static function getDB()
    {
        return \Yii::$app->db_social;
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%i500_post}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {

        return [
            // [['mobile','forum_id', 'content', 'community_id'], 'required'],
            [['forum_id', 'views', 'top', 'community_id', 'status', 'is_deleted','video_time'], 'integer'],
            [['create_time'], 'safe'],
            [['mobile'], 'string', 'max' => 11],
            [['video_url'], 'string', 'max' => 100],
            [['title'], 'string', 'max' => 120],
            ['forum_id', 'default', 'value' => 108],//默认官方版块 解决andorid 没有选择版块问题
            [['content'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键ID',
            'uid' => '用户ID(用户唯一标示)',
            'mobile' => '手机号(用户唯一标示)',
            'forum_id' => '版块ID',
            'title' => '帖子标题',
            'content' => '帖子内容',
            'post_img' => '帖子图片 多个用,分割',
            'thumbs' => '点赞数',
            'views' => '浏览量',
            'top' => '是否置顶 1=是2=否',
             'community_city_id' => '小区城市ID',
            'community_id' => '小区ID',
            'status' => '是否禁用 1=禁用2=可用',
            'is_deleted' => '是否删除1=已删除2=未删除',
            'create_time' => '创建时间',
            'video_url' => '视频url',
            'video_time' => '视频播放时长',
        ];
    }
	
	
     /**
     * 获取信息 一条
     * @param array  $cond      条件
     * @param bool   $asArray   是否作为数组返回
     * @param string $field     字段
     * @param string $and_where 字段
     * @param string $order     排序
     * @return array|null|ActiveRecord
     */
    public function getInfo($cond = array(), $asArray = true, $field = '*', $and_where = '', $order = '')
    {   

        $info = [];
        if ($cond) {
            if ($asArray) {
                $info = $this->find()->select($field)->where($cond)->andWhere($and_where)->orderBy($order)->asArray()->one();

            } else {
                $info = $this->find()->select($field)->where($cond)->andWhere($and_where)->orderBy($order)->one();
            }

        }

        return $info;

    }

    /**
     * 更新信息
     * @param array $data 数据
     * @param array $cond 条件
     * @return bool
     */
    public function updateInfo($data = array(), $cond = array())
    {
        $re = false;
        if ($cond && $data) {
            $re = $this->updateAll($data, $cond);
        }
        return $re !== false;
    }
    
     /**
     * Insert 1条记录
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array $arr_field_value 新记录的数据
     *
     * @return array array('result'=>0/1,'data'=>array(),'msg'=>'')
     */
    public function insertOneRecord($arr_field_value)
    {

        foreach ($arr_field_value as $key => $value) {

            $this->$key = $value;
        }

        try {
            $result = $this->insert();

            if ($result === false) {
                return array('result' => 0, 'data' => array(), 'msg' => 'failed');
            } else {
                return array('result' => 1, 'data' => array('new_id' => $this->id), 'msg' => '');
            }
        } catch (\Exception $e) {
            return array('result' => 0, 'data' => array(), 'msg' => $e->getMessage());
        }
    }
    /**
     * 获取版块
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(ServiceCategory::className(), ['id'=>'forum_id']);
    }

    /**
     * 获取作者
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(UserBasicInfo::className(), ['mobile'=>'mobile']);
    }
    /**
     * 获取作者
     * @return \yii\db\ActiveQuery
     */
    public function getPhoto()
    {
        return $this->hasMany(PostPhoto::className(), ['post_id'=>'id']);
    }
	
	public function getImg()
    {
        return $this->hasOne(PostPhoto::className(), ['post_id'=>'id']);
    }

    public function getServiceCategory()
    {
        return $this->hasOne(ServiceCategory::className(), ['id'=> 'forum_id']);
    }

    public function getPostComments()
    {
        return $this->hasMany(PostComments::className(), ['post_id'=> 'id']);
    }

    public function getPageList($cond = array(), $field = '*', $order = '', $page = 1, $size = 10, $and_where = '')
    {

        $list = [];
        if ($cond || $and_where) {
            $list = $this->find()
                ->select($field)
                ->where($cond)
                ->andWhere($and_where)
                ->orderBy($order)
                ->offset(($page-1) * $size)
                ->limit($size)
                ->asArray()
                ->all();
        }
        return $list;
    }

    
}
