<?php
/**
 * 接口日志表
 * PHP Version 5
 * @category  MODEL
 * @package   Social
 * @author    wyy <wyy@wyy.com>
 * @time      2017/05/12
 * @copyright 2017 辽宁爱伍佰科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wyy@i500m.com
 */
namespace frontend\models\i500_log;

class ApiLog extends LogBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_api_log}}';
    }
    
    /**
     * 创建接口日志
     * @param array $data 日志信息
     */
    public function insertLog($data = array()) {
        $log = array(
            'mobile' => isset($data['mobile'])?$data['mobile']:'',
            'url' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
            'receive_content' => var_export($data, true),
            'create_time' => date('Y-m-d H:i:s'),
        );
        return $this->insertInfo($log);
    }
    
    /**
     * 修改日志接口返回值
     * @param string $str 接口返回信息
     * @param int $id 接口id
     */
    public function updateLog($str='', $id=0) {
        //id为空
        if(empty($id)){
            return false;
        }
        $log = array(
            'return_content' => $str,
            'update_time' => date('Y-m-d H:i:s'),
        );
        return $this->updateInfo($log, array('id'=>$id));
    }
}
