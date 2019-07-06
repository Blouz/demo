<?php

namespace frontend\models\i500_social;

use Yii;

/**
 * This is the model class for table "{{%i500_post_comments}}".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $mobile
 * @property integer $post_id
 * @property string $content
 * @property integer $thumbs
 * @property integer $status
 * @property integer $is_deleted
 * @property string $create_time
 */
class PostComments extends \yii\db\ActiveRecord
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
        return '{{%i500_post_comments}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['post_id','content','type', 'author_mobile'], 'required'],
            [['uid', 'post_id', 'thumbs', 'status','type'], 'integer'],
            [['create_time'], 'safe'],
            ['status', 'default', 'value' => 1],
            [['mobile','author_mobile'], 'string', 'max' => 11],
            [['content'], 'string', 'max' => 255]
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
            'post_id' => '帖子ID',
            'content' => '评论内容',
            'thumbs' => '点赞数',
            'status' => '状态',
            'create_time' => '创建时间',
        ];
    }
    public function getUser()
    {
        return $this->hasOne(UserBasicInfo::className(), ['mobile' => 'mobile']);
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


    public function getImg()
    {
        return $this->hasMany(PostCommentsPhoto::className(), ['comment_id'=>'id']);
    }

    public function getPost()
    {
        return $this->hasMany(Post::className(), ['id'=>'post_id']);
    }

      /**
     * 插入信息
     * @param array $data 数据
     * @return bool
     */
    public function insertInfo($data = array())
    {
        $re = false;
        if ($data) {
            $model = clone $this;
            foreach ($data as $k => $v) {
                $model->$k = $v;
            }
            $re = $model->save(false);
        }
        if ($re) {
            return ($re== true) ? $model->id : false;
        }
    }
}
