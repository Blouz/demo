<?php
/**
 * YII AR 操作类
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   BASE
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/05 09:21
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */

namespace frontend\models;

use yii\db\ActiveRecord;
use yii\data\Pagination;

/**
 * BASE
 *
 * @category Social
 * @package  BASE
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class Base extends ActiveRecord
{
    /**
     * 获取列表
     * @param array  $cond      条件
     * @param string $field     字段
     * @param string $order     排序
     * @param string $and_where like != 的情况
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getList($cond = array(), $field = '*', $order = '', $and_where = '')
    {
        $list = [];
        if ($cond || $and_where) {
            $list = $this->find()
                ->select($field)
                ->where($cond)
                ->andWhere($and_where)
                ->orderBy($order)
                ->asArray()
                ->all();
        }
        return $list;
    }

    /**
     * 获取分页列表
     * @param array  $cond      条件
     * @param string $field     字段
     * @param string $order     排序
     * @param int    $page      当前页数
     * @param int    $size      每页显示几条
     * @param string $and_where like != 的情况
     * @return array|\yii\db\ActiveRecord[]
     */
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
    /**
     * 分页说明
     *
     * @param array  $map      条件
     * @param array  $andMap   条件
     * @param string  $fields   字段
     * @param bool  $is_array   是否返回数组
     * @param int    $pageSize 条件
     * @param string $order    条件
     * @param int    $sort     条件
     *
     * @return array
     */
    public function getPageItem($map = [], $andMap = [], $fields = '*',$is_array = true, $pageSize = 20, $order = 'id', $sort = SORT_DESC)
    {
        $query = $this->find()->select($fields)->where($map)->andWhere($andMap);
        //echo $query->createCommand()->sql;
        $count = $query->count();
        $pages = new Pagination(['totalCount' =>$count, 'pageSize' => $pageSize]);
        $list = $query->orderBy([$order=>$sort])->offset($pages->offset)->limit($pages->limit)->all();
        //var_dump($list);
        return ['item'=>$list,'count'=>$count,'pageCount'=>$pages->pageCount];

    }

    /**
     * 获取记录数
     * @param array  $cond      条件
     * @param string $and_where like != 的情况
     * @return int|string
     */
    public function getCount($cond = array(), $and_where = '')
    {
        $num = 0;
        if ($cond || $and_where) {
            $num = $this->find()->where($cond)->andWhere($and_where)->count();
        }
        return $num;
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

    /**
     * 根据字段名称 和条件获取一个字段信息
     * @param string $field 查询字段
     * @param array  $map   查询条件
     * @return string
     */
    public function getField($field, $map)
    {
        $name = '';
        if ($map) {
            $name = $this->find()->select($field)->where($map)->scalar();
        }
        return $name;

    }



    /**
     * 查询表，返回分页数据
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array  $arr_where    arr_where
     * @param string $str_andwhere 字符串where条件
     * @param array  $arr_order    arr_order
     * @param string $str_field    str_field
     * @param int    $int_offset   int_offset If not set or less than 0, it means starting from the beginning
     * @param int    $int_limit    int_limit If not set or less than 0, it means no limit
     *
     * @return array
     */
    public function getRecordList(
        $arr_where,
        $str_andwhere = '',
        $arr_order = array(),
        $str_field = '*',
        $int_offset = -1,
        $int_limit = -1
    ) {
        $arr = $this->find()
            ->select($str_field)
            ->where($arr_where)
            ->andWhere($str_andwhere)
            ->orderBy($arr_order)
            ->offset($int_offset)
            ->limit($int_limit)
            ->asArray()
            ->all();
        return $arr;
    }

    /**
     * 查询条件的记录总数
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array  $arr_where    arr_where
     * @param string $str_andwhere 字符串where条件
     * @param string $str_field    str_field
     *
     * @return array
     */
    public function getRecordListCount($arr_where, $str_andwhere = '', $str_field = 'count(*) as num')
    {
        $arr = $this->find()
            ->select($str_field)
            ->where($arr_where)
            ->andWhere($str_andwhere)
            ->asArray()
            ->one();
        if (!$arr) {
            $arr = array();
        }
        return $arr;
    }

    /**
     * 修改1条记录
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array  $arr_where    查询条件
     * @param string $str_andwhere 字符串where条件
     * @param array  $arr_set      set的数据
     *
     * @return array array('result'=>0/1,'data'=>array(),'msg'=>'')
     */
    public function updateOneRecord($arr_where, $str_andwhere = '', $arr_set = array())
    {
        $active_record = $this->find()
            ->where($arr_where)
            ->andWhere($str_andwhere)
            ->one();
        foreach ($arr_set as $key => $value) {
            $active_record->$key = $value;
        }
        try {
            $result = $active_record->update();
            if ($result === false) {
                return array('result' => 0, 'data' => array(), 'msg' => 'failed');
            } else {
                return array('result' => 1, 'data' => array(), 'msg' => '');
            }
        } catch (\Exception $e) {
            return array('result' => 0, 'data' => array(), 'msg' => $e->getMessage());
        }
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
     * 删除1条记录
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array  $arr_where    查询条件
     * @param string $str_andwhere 字符串where条件
     *
     * @return array array('result'=>0/1,'data'=>array(),'msg'=>'')
     */
    public function delOneRecord($arr_where, $str_andwhere = '')
    {
        $active_record = $this->find()
            ->where($arr_where)
            ->andWhere($str_andwhere)
            ->one();
        if ($active_record) {
            try {
                $result = $active_record->delete();
                if ($result === false) {
                    return array('result' => 0, 'data' => array(), 'msg' => 'failed');
                } else {
                    return array('result' => 1, 'data' => array(), 'msg' => '');
                }
            } catch (\Exception $e) {
                return array('result' => 0, 'data' => array(), 'msg' => $e->getMessage());
            }
        } else {
            return array('result' => 0, 'data' => array(), 'msg' => 'failed');
        }
    }

    /**
     * 获取一条记录
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array  $arr_where    where条件
     * @param string $str_andwhere 字符串where条件
     * @param string $str_field    字段
     *
     * @return array
     */
    public function getOneRecord($arr_where, $str_andwhere = '', $str_field = '*')
    {
        $arr = $this->find()
            ->select($str_field)
            ->where($arr_where)
            ->andWhere($str_andwhere)
            ->asArray()
            ->one();
        if (!$arr) {
            $arr = array();
        }
        return $arr;
    }


    /**
     * 查询表，返回分页数据
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array  $arr_where          arr_where
     * @param array  $arr_where_param    where绑定数组
     * @param string $str_andwhere       字符串where条件
     * @param array  $arr_andwhere_param andwhere绑定数组
     * @param array  $arr_order          arr_order
     * @param string $str_field          str_field
     * @param int    $int_offset         If not set or less than 0, it means starting from the beginning
     * @param int    $int_limit          If not set or less than 0, it means no limit
     *
     * @return array
     */
    public function getRecordListParam(
        $arr_where,
        $arr_where_param = array(),
        $str_andwhere = '',
        $arr_andwhere_param = array(),
        $arr_order = array(),
        $str_field = '*',
        $int_offset = -1,
        $int_limit = -1
    ) {
        $arr = $this->find()
            ->select($str_field)
            ->where($arr_where, $arr_where_param)
            ->andWhere($str_andwhere, $arr_andwhere_param)
            ->orderBy($arr_order)
            ->offset($int_offset)
            ->limit($int_limit)
            ->asArray()
            ->all();
        return $arr;
    }

    /**
     * 查询表，返回分页数据
     *
     * Author zhengyu@iyangpin.com
     *
     * @param array  $arr_where          arr_where
     * @param array  $arr_where_param    where绑定数组
     * @param string $str_andwhere       字符串where条件
     * @param array  $arr_andwhere_param andwhere绑定数组
     * @param string $str_field          str_field
     *
     * @return array
     */
    public function getRecordListParamCount(
        $arr_where,
        $arr_where_param = array(),
        $str_andwhere = '',
        $arr_andwhere_param = array(),
        $str_field = 'count(*) as num'
    ) {
        $arr = $this->find()
            ->select($str_field)
            ->where($arr_where, $arr_where_param)
            ->andWhere($str_andwhere, $arr_andwhere_param)
            ->asArray()
            ->one();
        if (!$arr) {
            $arr = array();
        }
        return $arr;
    }


    /**
     * 获取第一列的值
     * @param array $field 参数
     * @param array $where 条件
     * @param array $andWhere 条件
     * @return array
     */
    public function getCloumn( $field=array(), $where=array(), $andWhere=array() ){

        $result =  $this->find()
                   ->select($field)
                   ->where($where)
                   ->andWhere($andWhere)
                   ->column();
        if (!$result) {
            $result = array();
        }
        return $result;
    }
}
