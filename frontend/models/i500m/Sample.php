<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-11-6 上午9:29
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace frontend\models\i500m;

use common\helpers\Common;
use common\helpers\CurlHelper;

class Sample extends I500Base
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%sample}}';
    }

    /**
     * 方法描述
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param int   $dis 附近多少公里
     * @return bool|int|array
     */
    public function getNearSampleShop($lng, $lat, $dis)
    {
        if (!empty($lng) && !empty($lat)) {
            $url = Common::C('channelHost').'lbs/near-sample-shop?lng='.$lng.'&lat='.$lat.'&dis='.$dis;
            $res = CurlHelper::get($url);
            if (empty($res['code'])) {
                return 100;
            } else
            if ($res['code'] == 200) {
                return $res['data'];
               // var_dump($res['data']);
            } else {

            }
        } else {
            return false;
        }

    }

    /**
     * 根据样品id 获取样品信息 待图片
     * @param $sample_id
     * @return array
     */
    public function getSampleInfo($sample_id)
    {
        $sample_where['id'] = $sample_id;
        //$shop_sample_where['status']    = '2';
        $field = ['name', 'shop_id','description', 'day_count','status','have_receive_count'=>'used'];
        $sample_info = $this->find()->select($field)->where($sample_where)->asArray()->one();
        $sample_info['image'] = '';
       // var_dump($sample_info);
        if (!empty($sample_info)) {

            $sample_image_model = new SampleImage();
            $image = $sample_image_model->getField('image', ['sample_id'=>$sample_id, 'type'=>1]);
            $sample_info['image'] = empty($image) ?'':$image;
            //$image_list = ArrayHelper::index($sample_image_list, 'sample_id');


        }
        return $sample_info;
    }
}