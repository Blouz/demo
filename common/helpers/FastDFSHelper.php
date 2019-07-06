<?php
/**
 * 上传图片类（FastDFSHelper）
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   FastDFSHelper
 * @author    linxinliang <linxinliang@iyangpin.com>
 * @time      2015/8/12
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      linxinliang@iyangpin.com
 */
namespace common\helpers;

use Yii;

/**
 * 上传图片类（FastDFSHelper）
 *
 * @category Social
 * @package  FastDFSHelper
 * @author   linxinliang <linxinliang@iyangpin.com>
 * @license  http://www.i500m.com/ license
 * @link     linxinliang@iyangpin.com
 */
class FastDFSHelper
{
    protected $server;
    protected $storage;
    protected $config;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->storage = fastdfs_tracker_query_storage_store();
        //echo 'ip_addr:'.$this->storage['ip_addr'].'<br>port:'.$this->storage['port'];
        $this->server  = fastdfs_connect_server($this->storage['ip_addr'], $this->storage['port']);
        if (!$this->server) {
            echo "<pre>";
            echo fastdfs_get_last_error_no();
            echo fastdfs_get_last_error_info();
            //echo ('连接fastdfs服务失败', fastdfs_get_last_error_no(), "error info: " . fastdfs_get_last_error_info());
            exit(1);
        }
        $this->storage['sock'] = $this->server['sock'];
    }

    /**
     * 通过文件路径上传文件
     * @param string $filename 文件名称
     * @return array
     */
    public function fdfs_upload_by_filename($filename = '')
    {
        $file_info = fastdfs_storage_upload_by_filename($filename);
        fastdfs_tracker_close_all_connections();
        if ($file_info) {
            return $file_info;
        }
        return false;
    }

    /**
     * 上传文件
     * @param string $input_name 表单名称
     * @return array
     */
    public function fdfs_upload($input_name = '')
    {
        $file_tmp = $_FILES[$input_name]['tmp_name'];
        $real_name = $_FILES[$input_name]['name'];
        $filename = dirname($file_tmp) . "/" . $real_name;
        @rename($file_tmp, $filename);

        return $this->fdfs_upload_by_filename($filename);
    }

    /**
     * 方法描述
     * @param string $file_tmp  文件缓存
     * @param string $real_name 文件名称
     * @return array
     */
    public function fdfs_upload_name_size($file_tmp = '', $real_name = '')
    {
        $filename = dirname($file_tmp) . "/" . $real_name;
        @rename($file_tmp, $filename);
        return $this->fdfs_upload_by_filename($filename);
    }


    /**
     * 下载文件
     * @param string $group_name 组名
     * @param string $file_id    文件ID
     * @return string
     */
    public function fdfs_down($group_name = '', $file_id = '')
    {
        $file_content = fastdfs_storage_download_file_to_buff($group_name, $file_id);
        return $file_content;
    }

    /**
     * 删除文件
     * @param string $group_name 组名
     * @param string $file_id    文件ID
     * @return bool
     */
    public function fdfs_del($group_name = '', $file_id = '')
    {
        return fastdfs_storage_delete_file($group_name, $file_id);
    }

    /**
     * 上传
     * @return array
     */
    public function upload()
    {
        $fds = new FastDFSHelper();
        $data=$fds->fdfs_upload('Filedata');
        echo json_encode($data);
    }

//    /**
//     * 关闭链接服务
//     */
//    public function __destruct()
//    {
//        fastdfs_disconnect_server($this->server);
//    }
}
