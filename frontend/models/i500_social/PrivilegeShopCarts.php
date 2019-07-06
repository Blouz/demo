<?php
/**
 * 特权商城购物车
 * User: wyy
 * Date: 20170828
 */
namespace frontend\models\i500_social;

class PrivilegeShopCarts extends SocialBase {
    /**
     * 表连接
     * @return string
     */
    public static function tableName()
    {
        return "{{%i500_privilege_shop_carts}}";
    }

    /**
     * 商品
     */
    public function getGoods(){
        return $this->hasOne(PrivilegeGoods::className(),['id'=>'g_id']);
    }

    /**
     * 商品图片
     */
    public function getGoodsImage(){
        return $this->hasOne(PrivilegeGoodsImage::className(),['g_id'=>'g_id']);
    }

    /**
     * 商品规格
     */
    public function getGoodsSpec(){
        return $this->hasOne(PrivilegeSpecification::className(),['id'=>'s_id']);
    }
    
    /**
     * 购物车商品总数量
     */
    public static function getCartCount($mobile) {
        $sum = self::find()->select(['sum(num)'])->where(['mobile'=>$mobile])->scalar();
        return !empty($sum) ? $sum : '0';
    }
    
    
    /**
     * 我的购物车
     * @param string $mobile 用户
     * @param array $ids 购物车ids
     * @return array 购物车商品
     */
    public static function getCartList($mobile,$ids=[]) {
        $andwhere = [];
        //购物车id
        if ($ids){
            $andwhere = ['id'=>$ids];
        }
        $list = self::find()->select(['id','g_id','s_id','num','price'])
                ->with(['goods'=>function($query){
                    $query->select(['id','status','title']);
                }])
                ->with(['goodsImage'=>function($query){
                    $query->select(['g_id','image'])->orderBy('create_time asc');
                }])
                ->with(['goodsSpec'=>function($query){
                    $query->select(['id','price','total_num']);
                }])
                ->where(['mobile'=>$mobile])->orderBy('create_time desc')
                ->andWhere($andwhere)
                ->asArray()->all();
        $data = [];
        //列表及总价
        $data['list'] = [];
        $data['price_all'] = 0;
        $data['num_all'] = 0;
        foreach ($list as $key=>$val) {
            $val2 = [
                'id'    => $val['id'],
                'g_id'  => $val['g_id'],
                's_id'  => $val['s_id'],
                'price' => 0,
                'num'   => (int)$val['num'],
                'title'  => '',
                'image' => '',
                'total' => 0,
                'error' => '',
            ];
            //商品下架
            if (empty($val['goods']) || $val['goods']['status']!=2) {
                $val2['error'] = '2201';
            //商品下架
            } else if (empty($val['goodsSpec'])) {
                $val2['error'] = '2201';
            //库存不足
            } else if ($val['goodsSpec']['total_num']<$val['num']) {
                $val2['error'] = '2202';
            }
            //新价格覆盖加入购物车时的价格
            $val2['price'] = isset($val['goodsSpec']['price']) ? round($val['goodsSpec']['price'],2) : round($val['price'],2);
            $val2['total'] = round($val2['price']*$val2['num'],2);
            $data['price_all'] += $val2['total'];
            $data['num_all'] += $val2['num'];
            //商品信息
            $val2['title'] = isset($val['goods']['title']) ? $val['goods']['title'] : $val2['title'];
            $val2['image'] = isset($val['goodsImage']['image']) ? $val['goodsImage']['image'] : $val2['image'];
            
            $data['list'][] = $val2;
        }
        return $data;
    }
    
    /**
     * 立即购买/加入购物车  获取商品信息
     * @param int $s_id 规格id
     * @param int $num 数量
     * @return array 购物车商品
     */
    public static function getBuyList($s_id, $num) {
        //购物车id
        if (empty($s_id) || empty($num)){
            $data['error'] = '511';
            return $data;
        }
        
        //商品规格信息
        $goods_spec = PrivilegeSpecification::find()->select(['id','price','g_id','total_num'])->where(['id'=>$s_id])->asArray()->one();
        if (empty($goods_spec)) {
            $data['error'] = '2201';
            return $data;
        }
        
        //商品信息
        $goods = PrivilegeGoods::find()->select(['id','title','status'])->where(['id'=>$goods_spec['g_id']])->asArray()->one();
        if (empty($goods) || $goods['status']!=2) {
            $data['error'] = '2201';
            return $data;
        }
        
        //商品图片
        $goods_image = PrivilegeGoodsImage::find()->select(['image'])->where(['g_id'=>$goods['id']])->orderBy('create_time asc')->scalar();
        $goods_image = empty($goods_image) ? '' : $goods_image;
        
        //库存不足
        if ($goods_spec['total_num'] < $num) {
            $data['error'] = '2202';
            $data['total_num'] = $goods_spec['total_num'];
            return $data;
        }
        //列表及总价
        $data['list'][] = [
            'id'    => 0,
            'g_id'  => $goods['id'],
            's_id'  => $goods_spec['id'],
            'price' => round($goods_spec['price'],2),
            'num'   => (int)$num,
            'title'  => $goods['title'],
            'image' => $goods_image,
            'total' => round($goods_spec['price']*$num,2),
            'error' => '',
        ];
        $data['price_all'] = round($goods_spec['price']*$num,2);
        
        return $data;
    }
}