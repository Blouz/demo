<?php
/**
 * 需求服务表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    yaoxin <yaoxin@i500m.com>
 * @time      2017/06/21
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      yaoxin@i500m.com
 */
namespace frontend\models\i500_social;

class ShouldDemand extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_should_demand}}';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile','title', 'content','community_id', 'price', 'end_time'], 'required'],
            [['title'], 'string', 'max' => 100],
            [['content'], 'string', 'max' => 2000],
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键ID',
            'mobile' => '手机号',
            'title' => '标题',
            'content' => '内容',
            'community_id' => '小区ID',
            'price' => '赏金',
            'end_time' => '到期时间',
        ];
    }
    /**
     * 获取用户信息
     */
    public function getUser()
    {
        return $this->hasOne(UserBasicInfo::className(), ['mobile' => 'mobile']);
    }
    /**
     * 需求列表 or 详情
     * @param $where
     * @param array $andwhere1
     * @param array $andwhere2
     * @param int $page
     * @param int $size
     * @param string $fileds
     * @param int $limit
     * @param int $type
     * @return array|null|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public function DemandList($where,$andwhere1=[],$andwhere2=[],$page=1,$size=10,$fileds = '*',$type = 1){
        $data = [];
        if($where){
            $model = $this->find()->select($fileds)
                    ->where($where)
                    ->andWhere($andwhere1)
                    ->andWhere($andwhere2);
            switch ($type)
            {
                case 1:
                    $data = $model->orderBy('create_time Desc')->offset(($page-1)*$size)->limit($size)->asArray()->all();
                    break;
                case 2:
                    $data = $model->with(['user'=>function($query){
                                $query->select(['mobile', 'nickname', 'avatar']);
                            }])
                            ->asArray()->one();
                    break;
            }
        }
        return $data;
    }
    /**
     * 需求列表数量
     * @param $where
     * @param array $andwhere1
     * @param array $andwhere2
     * @return array|null|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public function DemandCount($where,$andwhere1=[],$andwhere2=[]){
        $data = 0;
        if($where){
            $data = $this->find()->select(['*'])
                    ->where($where)
                    ->andWhere($andwhere1)
                    ->andWhere($andwhere2)
                    ->count();
        }
        return $data;
    }
}