<?php
/**
 * 服务表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015-09-16
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\models\i500_social;
use yii\behaviors\TimestampBehavior;

/**
 * 服务表
 *
 * @category MODEL
 * @package  Social
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class Service extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_service}}';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['audit_status', 'validateStatus','skipOnEmpty' => false],
            [['mobile','description','price','unit','image','community_city_id', 'community_id'], 'required'],
            [['category_id','son_category_id'], 'required', 'message'=>'请选择类别'],
            [['category_id', 'son_category_id', 'service_way', 'status', 'audit_status', 'community_city_id', 'community_id', 'is_deleted'], 'integer'],
            ['status', 'default', 'value'=>1],
            ['image', 'url'],
            
            ['audit_status', 'default', 'value'=>0],
            ['is_deleted', 'default', 'value'=>2],
            [['price'], 'number'],
            [['mobile','unit'], 'string', 'max' => 11],
            [['image', 'description'], 'string', 'max' => 8000],
            [['title'], 'string', 'max' => 120]
        ];
    }
    public function validateStatus($attribute, $params)
    {
        if ($this->$attribute == 2) {
            $this->addError($attribute, '已经通过审核不允许修改');
        }
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
//        return [
//            TimestampBehavior::className(),
//        ];
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => 'update_time',
                'value' => function() { return date('Y-m-d H:i:s');}
            ],
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mobile' => '手机号',
            'category_id' => '分类ID',
            'son_category_id' => '子分类ID',
            'image' => '服务图片',
            'title' => '标题',
            'price' => '价格',
            'unit' => '单位',
            'service_way' => '服务方式 1=上门服务2=到店体验',
            'description' => '服务内容',
            'audit_status' => '审核状态 0=未审核1=审核中2=审核成功3=审核失败',
            'status' => '上/下架状态 1=上架2=下架',
            'community_city_id' => '小区城市ID',
            'community_id' => '小区ID',
            'is_deleted' => '是否删除1=已删除2=未删除',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }
}
