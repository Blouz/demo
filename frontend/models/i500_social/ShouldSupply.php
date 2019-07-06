<?php
/**
 * ShouldSupply.php
 *
 * PHP Version 5
 *
 * Created by PhpStorm.
 * Category social
 * User MAC
 * Author huangdekui<huangdekui@i500m.com>
 * Time 2017/6/21 15:11
 */
namespace frontend\models\i500_social;

class ShouldSupply extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_should_supply}}';
    }

    /**
     * 图片
     * @return \yii\db\ActiveQuery
     */
    public function getPhoto(){
        return $this->hasMany(ShouldSupplyImage::className(),['pid'=>'id']);
    }

    /**
     * 图片
     * @return \yii\db\ActiveQuery
     */
    public function getUser(){
        return $this->hasOne(UserBasicInfo::className(),['mobile'=>'mobile']);
    }

    /**
     * 服务列表 or 详情
     * @param $where
     * @param array $andwhere
     * @param int $page
     * @param int $size
     * @param string $fileds
     * @param int $type
     * @return array|null|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public function SupplyList($where,$andwhere=[],$page=1,$size=10,$fileds = '*',$type = 1){
        $data = [];
        if($andwhere){
            $model = $this->find()->select($fileds)
                ->with(['photo'=>function($query){
                    $query->select(['pid','image'])->orderBy('create_time DESC');
                }])
                ->where($where)
                ->andWhere($andwhere);

            switch ($type)
            {
                case 1:
                    $data = $model ->orderBy('create_time DESC')->offset(($page-1)*$size)->limit($size)->asArray()->all();
                    break;
                case 2:
                    $data = $model->asArray()->one();
                    break;
            }
        }
        return $data;
    }
    /**
     * 服务列表数量
     * @param $where
     * @param array $andwhere
     * @return array|null|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public function SupplyCount($where = [],$andwhere=[]){
        $data = [];
        if($andwhere){
            $data = $this->find()->select(['id'])
                ->where($where)
                ->andWhere($andwhere)->count();
        }
        return $data;
    }
}