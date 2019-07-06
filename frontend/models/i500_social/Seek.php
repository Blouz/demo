<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-18 上午11:34
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
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
class Seek extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_need}}';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile','description','price','unit','image','community_city_id', 'community_id'], 'required'],
            [['category_id', 'son_category_id', 'service_way', 'status', 'community_city_id', 'community_id', 'is_deleted'], 'integer'],
            [['category_id','son_category_id'], 'required', 'message'=>'请选择类别'],
            ['status', 'default', 'value'=>1],
            ['is_deleted', 'default', 'value'=>2],
            [['price'], 'number'],
            [['mobile','unit'], 'string', 'max' => 11],
            [['image', 'description'], 'string', 'max' => 255],
            [['address'], 'string', 'max' => 255],
            [['title'], 'string', 'max' => 120],
            [['sendtime'], 'string', 'max' => 120],
        ];
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
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
            'id' => 'ID主键递增',
            'mobile' => '手机号',
            'category_id' => '分类ID',
            'son_category_id' => '子分类ID',
            'image' => '服务图片',
            'title' => '标题',
            'price' => '价格',
            'unit' => '单位',
            'service_way' => '服务方式 1=上门服务2=到店体验',
            'description' => '服务描述',
            'status' => '上/下架状态 1=上架2=下架',
            'community_city_id' => '小区城市ID',
            'community_id' => '小区ID',
            'is_deleted' => '是否删除1=已删除2=未删除',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['is_deleted'],$fields['status']);
        return $fields;
    }
    public function getCategory()
    {
        /**
         * 第一个参数为要关联的字表模型类名称，
         *第二个参数指定 通过子表的 customer_id 去关联主表的 id 字段
         */
        return $this->hasOne(ServiceCategory::className(), ['id' => 'category_id']);
    }
}
