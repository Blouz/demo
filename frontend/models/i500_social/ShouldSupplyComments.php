<?php
/**
 * ShouldSupplyComments.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/6/21 15:14
 */
namespace frontend\models\i500_social;

class ShouldSupplyComments extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_should_supply_comments}}';
    }

    /**
     * 图片
     * @return \yii\db\ActiveQuery
     */
    public function getPhoto(){
        return $this->hasMany(ShouldSupplyCommentsImage::className(),['pid'=>'id']);
    }

    /**
     * 用户
     * @return \yii\db\ActiveQuery
     */
    public function getUser(){
        return $this->hasOne(UserBasicInfo::className(),['mobile'=>'mobile']);
    }

    /**
     * 评价列表 or 评价个数 or 评价详情
     * @param $where
     * @param int $type
     * @return array|int|string|\yii\db\ActiveRecord[]
     */
    public function CommentList($where,$type=1){
        $data = [];
        if (!empty($where)) {
            $comments = ShouldSupplyComments::find()->select(['id','mobile','content','create_time'])
                ->with(['photo'=>function($query){
                    $query->select(['pid','image']);
                }])
                ->with(['user'=>function($query){
                    $query->select(['nickname','mobile','avatar']);
                }])
                ->where($where)
                ->orderBy('create_time DESC');

            switch ($type)
            {
                case 1:
                    $data = $comments->asArray()->all();
                    break;
                case 2:
                    $data = $comments->limit(3)->asArray()->all();
                    break;
                case 3:
                    $data = $comments->count();
                    break;
                case 4:
                    $data = $comments->one();
                    break;
            }
        }
        return $data;
    }
}