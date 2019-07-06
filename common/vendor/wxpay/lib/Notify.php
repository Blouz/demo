<?php
/**
 * 描述
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-12-2 上午11:12
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace common\vendor\wxpay;

use common\vendor\wxpay\lib\WxPayNotify;

class Notify extends WxPayNotify
{
    public function NotifyProcess($data, &$msg)
    {
        file_put_contents('/tmp/new_wx_txt.log',  "执行时间：".date('Y-m-d H:i:s')." 112返回结果".var_export($data)."\n", FILE_APPEND);

        //TODO 用户基础该类之后需要重写该方法，成功的时候返回true，失败返回false
        return true;
    }
}