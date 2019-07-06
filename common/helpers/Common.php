<?php
/**
 * Common
 *
 * PHP Version 5
 *
 * @category  Social
 * @package   Common
 * @time      2015/8/12
 */

namespace common\helpers;

use Yii;
use common\vendor\pinyin\ChinesePinyin;

class Common
{
    /**
     * 截取字符串
     * @param string $string 字符串
     * @param string $length 限制长度
     * @param string $etc    后缀
     * @return string
     */
    public static function truncate_utf8_string($string, $length, $etc = '...')
    {
        $result = '';
        $string = html_entity_decode(trim(strip_tags($string)), ENT_QUOTES, 'UTF-8');
        $strlen = strlen($string);
        for ($i = 0; (($i < $strlen) && ($length > 0)); $i++) {
            if ($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')) {
                if ($length < 1.0) {
                    break;
                }
                $result .= substr($string, $i, $number);
                $length -= 1.0;
                $i += $number - 1;
            } else {
                $result .= substr($string, $i, 1);
                $length -= 0.5;
            }
        }
        $result = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        if ($i < $strlen) {
            $result .= $etc;
        }
        return $result;
    }

    /**
     * 二维数组去重
     * @param array  $arr 数组
     * @param string $key 键
     * @return mixed
     */
    public static function arrUnique($arr = [], $key = '')
    {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {
                //搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        //sort($arr); //sort函数对数组进行排序
        return $arr;
    }

    /**
     * 验证手机号
     * @param string $mobile 手机号
     * @return bool
     */
    public static function validateMobile($mobile)
    {
        $preg = Yii::$app->params['mobilePreg'];
        if (preg_match($preg, $mobile)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取客户端IP
     * @return string
     */
    public static function getIp()
    {
        $ip = '';
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * 格式化数字
     * @param int $num 数字
     * @return string
     */
    public static function formatNumber($num = 0, $f=2, $w='w')
    {
        /**当数字超过万的时候进行格式化**/
        if ($num > 10000) {
            $num = round(($num/10000), $f).$w;
        }
        return $num;
    }
    /**
     * 获取6位随机数
     * @return int
     */
    public static function getRandomNumber()
    {
        return mt_rand(100000, 999999);
    }

    /**
     * 获取参数
     * @param string $param1 第一个参数
     * @param string $param2 第二个参数
     * @return int
     */    
    public static function C($param1 = '', $param2 = '')
    {
        if ($param1 && $param2) {
            return \Yii::$app->params[$param1][$param2];
        } else {
            return \Yii::$app->params[$param1];
        }
    }

    /**
     * 获取短信模板
     * @param int $type 类型
     * @param int $code 验证码
     * @return string
     */
    public static function getSmsTemplate($type = 1, $code = 0)
    {
        switch ($type) {
            case 1 :
                /**登录发送验证码**/
                $temp = '校验码 '.$code.'，您正在使用验证码登录账号。如非本人操作请忽略本条信息';
                break;
            case 2 :
                /**找回密码**/
                $temp = '校验码 '.$code.'，您正在找回密码，需要进行校验。如非本人操作请忽略本条信息';
                break;
            case 3 :
                /**注册发送验证码**/
                $temp = '校验码 '.$code.'，您正在注册账号，需要进行校验。如非本人操作请忽略本条信息';
                break;
            case 4 :
                /**第三方绑定用户发送验证码**/
                $temp = '校验码 '.$code.'，您正在使用第三方登录绑定到您的账号。如非本人操作请忽略本条信息';
                break;
            case 5 :
                /**地铁项目绑定 发送验证码**/
                $temp = '校验码 '.$code.'，您正在使用手机号绑定聊天室。如非本人操作请忽略本条信息';
                break;
            case 6 :
                /**家庭组 短信**/
                $temp = '恭喜您，您的同学已经同意您加入大家庭啦！';
                break;
            case 7 :
                /**通讯录 短信**/
                $temp = '有人在通讯录中想要添加您为好友，去设置中开启通讯录功能找到您的好友吧！';
                break;
            case 8 :
                /**设置或找回支付密码 发送验证码**/
                $temp = '校验码 '.$code.'，您正在使用手机号设置支付密码。如非本人操作请忽略本条信息';
                break;
            case 9 :
                /**添加银行卡 发送验证码**/
                $temp = '校验码 '.$code.'，您正在添加银行卡。如非本人操作请忽略本条信息';
                break;
            case 10 :
                /**更换手机号 发送验证码**/
                $temp = '校验码 '.$code.'，您正在更换新手机号，需要进行校验。如非本人操作请忽略本条信息';
                break;
            case 11 :
                /**关联账号 发送验证码**/
                $temp = '校验码 '.$code.'，您正在关联账号，需要进行校验。如非本人操作请忽略本条信息';
                break;
            default :
                $temp = '';
        }
        return $temp;
    }

    /**
     * 是否是身份证格式
     * @param string $number 身份证数字
     * @return bool
     */
    public static function isIdCard($number = '')
    {
        // 转化为大写，如出现x
        $number = strtoupper($number);
        //加权因子
        $wi = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码串
        $ai = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        //按顺序循环处理前17位
        $sigma = 0;
        for ($i = 0; $i < 17; $i++) {
            //提取前17位的其中一位，并将变量类型转为实数
            $b = (int) $number{$i};
            //提取相应的加权因子
            $w = $wi[$i];
            //把从身份证号码中提取的一位数字和加权因子相乘，并累加
            $sigma += $b * $w;
        }
        //计算序号
        $snumber = $sigma % 11;
        //按照序号从校验码串中提取相应的字符。
        $check_number = $ai[$snumber];
        if ($number{17} == $check_number) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 通过身份证获取生日
     * @param string $card 身份证
     * @return string
     */
    public static function getBirthdayByCard($card = '')
    {
        $birthday = strlen($card)==15 ? ('19' . substr($card, 6, 6)) : substr($card, 6, 8);
        if (strlen($card) != 15) {
            $year  = substr($birthday, 0, 4);
            $month = substr($birthday, 4, 2);
            $day   = substr($birthday, -2);
            return $year.'-'.$month.'-'.$day;
        }
        return $birthday;
    }

    /**
     * 通过身份证获取性别
     * @param string $card 身份证
     * @return string
     */
    public static function getSexByCard($card = '')
    {
        //1=男 2=女
        $sex = (int)substr($card, 16, 1);
        return $sex % 2 === 0 ? '2' : '1';
        //return substr($card, (strlen($card)==15 ? -2 : -1), 1) % 2 ? '1' : '2';
    }

    /**
     * 通过身份证获取年龄
     * @param string $card 身份证
     * @return string
     */
    public static function getAgeByCard($card = '')
    {
        $date = strtotime(substr($card, 6, 8)); //获得出生年月日的时间戳
        $today = strtotime('today'); //获得今日的时间戳
        $diff = floor(($today-$date)/86400/365); //得到两个日期相差的大体年数
        //strtotime 加上这个年数后得到那日的时间戳后与今日的时间戳相比
        $age = strtotime(substr($card, 6, 8).' +'.$diff.'years') > $today ? ($diff+1) : $diff ;
        return $age;
    }

    /**
     * 通过身份证获取星座
     * @param string $card 身份证
     * @return string
     */
    public static function getConstellationByCard($card = '')
    {
        $bir   = substr($card, 10, 4);
        $month = (int)substr($bir, 0, 2);
        $day   = (int)substr($bir, 2);
        $strValue = '0';
        if (($month == 1 && $day >= 20) || ($month == 2 && $day <= 18)) {
            $strValue = "1"; //水瓶座
        } elseif (($month == 2 && $day >= 19) || ($month == 3 && $day <= 20)) {
            $strValue = "2"; //双鱼座
        } elseif (($month == 3 && $day > 20) || ($month == 4 && $day <= 19)) {
            $strValue = "3"; //白羊座
        } elseif (($month == 4 && $day >= 20) || ($month == 5 && $day <= 20)) {
            $strValue = "4"; //金牛座
        } elseif (($month == 5 && $day >= 21) || ($month == 6 && $day <= 21)) {
            $strValue = "5"; //双子座
        } elseif (($month == 6 && $day > 21) || ($month == 7 && $day <= 22)) {
            $strValue = "6"; //巨蟹座
        } elseif (($month == 7 && $day > 22) || ($month == 8 && $day <= 22)) {
            $strValue = "7"; //狮子座
        } elseif (($month == 8 && $day >= 23) || ($month == 9 && $day <= 22)) {
            $strValue = "8"; //处女座
        } elseif (($month == 9 && $day >= 23) || ($month == 10 && $day <= 23)) {
            $strValue = "9"; //天秤座
        } elseif (($month == 10 && $day > 23) || ($month == 11 && $day <= 22)) {
            $strValue = "10"; //天蝎座
        } elseif (($month == 11 && $day > 22) || ($month == 12 && $day <= 21)) {
            $strValue = "11"; //射手座
        } elseif (($month == 12 && $day > 21) || ($month == 1 && $day <= 19)) {
            $strValue = "12"; //魔羯座
        }
        return $strValue;
    }

    /**
     * 通过日期获取星期
     * @param string $day  日期
     * @param string $type 标识 1=返回大写 2=返回小写
     * @return string
     */
    public static function getWeek($day = '', $type = '1')
    {
        if (!empty($day)) {
            if ($type == '2') {
                $week_array =array("7","1","2","3","4","5","6");
            } else {
                $week_array =array("日","一","二","三","四","五","六");
            }
            return $week_array[@date("w", strtotime($day))];
        }
        return "";
    }

    /**
     * 隐藏身份证信息
     * @param string $card 身份证
     * @return string
     */
    public static function hiddenUserCard($card = '')
    {
        return substr_replace($card, '****', 10, 4);
    }

    /**
     * 距离当前时间展示方法 - 【权威的】
     * @param string $datetime 活跃时间
     * @param int    $nowtime  当前时间
     * @return bool|string
     */
    public static function timeAgoBAK($datetime='', $nowtime = 0)
    {
        $datetime = strtotime($datetime);
        if (empty($nowtime)) {
            $nowtime = time();
        }
        $timediff = $nowtime - $datetime;
        $timediff = $timediff >= 0 ? $timediff : $datetime - $nowtime;
        // 秒
        if ($timediff < 60) {
            return $timediff . '秒前';
        }
        // 分
        if ($timediff < 3600 && $timediff >= 60) {
            return intval($timediff / 60) . '分钟前';
        }
        // 今天
        $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        if ($datetime >= $today) {
            return date('今天 H:i', $datetime);
        }
        // 昨天
        $yestoday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        if ($datetime >= $yestoday) {
            return date('昨天 H:i', $datetime);
        }
        // 今年月份
        $this_year = mktime(0, 0, 0, 1, 1, date('Y'));
        if ($datetime >= $this_year) {
            return date('m月d日 H:i', $datetime);
        }
        // 往年
        return date('Y年m月d日', $datetime);
    }

    /**
     * 距离当前时间展示方法 - 【产品非要这样的，沟通无果,所以...】
     * @param string $datetime 活跃时间
     * @param int    $nowtime  当前时间
     * @return bool|string
     */
    public static function timeAgo($datetime='', $nowtime = 0)
    {
        $datetime = strtotime($datetime);
        if (empty($nowtime)) {
            $nowtime = time();
        }
        $timediff = $nowtime - $datetime;
        $timediff = $timediff >= 0 ? $timediff : $datetime - $nowtime;
        // 秒
        if ($timediff < 60) {
            return '今天';
        }
        // 分
        if ($timediff < 3600 && $timediff >= 60) {
            return '今天';
        }
        // 今天
        $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        if ($datetime >= $today) {
            return '今天';
        }
        // 昨天
        $yestoday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        if ($datetime >= $yestoday) {
            return '昨天';
        }
        // 今年月份
        $this_year = mktime(0, 0, 0, 1, 1, date('Y'));
        if ($datetime >= $this_year) {
            return date('m月d日', $datetime);
        }
        // 往年
        return date('m月d日Y年', $datetime);
    }

    /**
     * 根据经纬度获取两个两点之间的距离(有误差)
     * @param int $lat1 纬度
     * @param int $lng1 经度
     * @param int $lat2 纬度
     * @param int $lng2 经度
     * @return float
     */
    public static function getDistance($lat1 = 0, $lng1 = 0, $lat2 = 0, $lng2 = 0)
    {
        //地球半径
        $R = 6378137;
        //将角度转为弧度
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        //结果
        $s = acos(cos($radLat1)*cos($radLat2)*cos($radLng1-$radLng2)+sin($radLat1)*sin($radLat2))*$R;
        //精度
        $s = round($s* 10000)/10000;
        if ($s > 1000) {
            return round($s/1000, 2).'km';
        } else {
            return round($s).'m';
        }
    }

    /**
     * 根据经纬度获取两个两点之间的距离(有误差)
     * @param int $lat1 纬度
     * @param int $lng1 经度
     * @param int $lat2 纬度
     * @param int $lng2 经度
     * @return float (米)
     */
    public static function getDistanceNew($lat1 = 0, $lng1 = 0, $lat2 = 0, $lng2 = 0)
    {
        if (!floatval($lat1) || !floatval($lng1) || !floatval($lat2) || !floatval($lng2)) {
            return -1;
        }
        //地球近似半径(米)
        $earthRadius = 6367000;
        //将角度转为弧度
        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);
        $lng1 = deg2rad($lng1);
        $lng2 = deg2rad($lng2);
        //经纬差
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        //勾股弦计算
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        //地球半径百分百
        $calculatedDistance = $earthRadius * $stepTwo;   
        return round($calculatedDistance);
    }
    
    /**
     * 格式化图片
     * @param string $image 图片地址
     * @return string
     */
    public static function formatImg($image = '')
    {
        if (!empty($image)) {
            if (!strstr($image, 'http')) {
                return self::C('imgHost').$image;
            }
        }
        return '';
    }
    
    /**
     * 剩余时间处理
     * @param string $datetime 时间 ，2017-06-27 18:00:00
     * @return string 6天23时35分
     */
    public static function formatTime($datetime, $format = true,$showtime = false) {
        $datetime = strtotime($datetime);
        if ($format !== true) {
            if (! is_string($format)) {
                $format = 'Y-m-d H:i:s';
            }
            return date($format, $datetime);
        }

        $sys_datetime_date_format = 'Y-m-d';
        $sys_datetime_time_format = 24;
        $sys_datetime_pretty_format = 1;

        $format = $sys_datetime_date_format;
        if($showtime===true || $sys_datetime_time_format!=='0') {
            $format .= ' ' . ($sys_datetime_time_format === '12' ? 'h:i:s' : 'H:i:s');
        }

        if ($sys_datetime_pretty_format === '0') {
            return date($format, $datetime);
        }

        $timer = $datetime;

        $diff = time() - $timer;
        $day = floor($diff / 86400);

        if ($day > 0) {
            if ($day < 366) {
                return $day . "天前";
            } else {
                return date($format, $timer);
            }
        }

        $free = $diff % 86400;
        if ($free <= 0) {
            return '刚刚';
        }

        $hour = floor($free / 3600);
        if ($hour > 0) {
            return $hour . "小时前";
        }

        $free = $free % 3600;
        if ($free <= 0) {
            return '刚刚';
        }

        $min = floor($free / 60);
        if ($min > 0) {
            return $min . "分钟前";
        }

        $free = $free % 60;
        if ($free > 0) {
            return $free . "秒前";
        }
        return '刚刚';
    }

    /**
     * 创建订单号
     * @param int    $province_id 省份id
     * @param string $mobile      手机号
     * @return bool
     */
    public static function createSn($province_id=0, $mobile='')
    {
        $channelUrl = \Yii::$app->params['channelHost'];
        $url = $channelUrl.'order/create-order-sn?province_id='.$province_id.'&mobile='.$mobile;
        $re = CurlHelper::get($url, true);
        if (isset($re['code']) && $re['code'] == 200) {
            return $re['data'];
        } else {
            return false;
        }
    }

    /**
     * 金额转化成要赠送的积分
     * @param decimal $money  金额
     * @return int
     */
    public static function moneyToScore($money)
    {
        //一元一分
        return floor($money);
    }
	
    /**
     * 敏感词过滤 批量过滤
     * @param string[] word
     * @return string[]
     */
	public static function sensitive_filter($word) {
		$wordvalue=array();
		$arrkeys=array_keys($word);//数组键名集合
		$arrvalues=array_values($word);//数组值集合
		foreach($word as $wv) {
			$wordvalue[]=$wv;
		}
		$conn = \Yii::$app->db_aplan_crm;
		$cmd = $conn->createCommand("select keyword from sensitive_keywords where status='0'");
		$badword = $cmd->queryAll();
		$bad= array();
		$good= array();
		foreach($badword as $bd) {
		   $bad[]=$bd['keyword'];
		}
        $filter_badword = array_combine($bad,array_fill(0,count($bad),'**'));
		for($j=0;$j<count($wordvalue);$j++) {
			$str = strtr($wordvalue[$j], $filter_badword);
			$good[]=$str;
		}
		$newarray=array();
		$k=0;
		foreach($arrkeys as $ak) {
			$newarray[$ak]=$good[$k];
			$k++;
		}
		return $newarray;
	}
	
    /**
     * 敏感词过滤 单个
     * @param string 关键词
     * @return string
     */
	public static function sensitive_filter_one($word) {
	    if (empty($word)) return '';
		$conn = \Yii::$app->db_aplan_crm;
		$cmd = $conn->createCommand("select keyword from sensitive_keywords where status='0'");
		$badword = $cmd->queryAll();
		$bad= array();
		foreach($badword as $bd) {
		   $bad[]=$bd['keyword'];
		}
        $filter_badword = array_combine($bad,array_fill(0,count($bad),'**'));
	    $word = strtr($word, $filter_badword);
		return $word;
	}
	public static function sens_filter_word($word)
	{
		$conn = \Yii::$app->db_aplan_crm;
		$cmd = $conn->createCommand("select keyword from sensitive_keywords where status='0'");
		$badword = $cmd->queryAll();
		$newstr=$word;
		foreach($badword as $bd)
		{
			//var_dump($bd['keyword']);
			//exit;
			$newstr=str_ireplace($bd['keyword'],"***",$newstr);
		}
		return $newstr;
	}
        
     /**
     * 表情解码
     * @param string str
     * @return string
     */   
    public static function userTextDecode($str)
    {
        $text = json_encode($str); //暴露出unicode
        $text = preg_replace_callback('/\\\\\\\\/i',function($str){
                return '\\';
            },$text); //将两条斜杠变成一条，其他不动
        return json_decode($text);
    }   
     /**
     * 表情转码
     * @param string str
     * @return string
     */ 
    public static function userTextEncode($str)
    {
        if(!is_string($str))return $str;
        if(!$str || $str=='undefined')return '';

        $text = json_encode($str); //暴露出unicode
        $text = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i",function($str){
            return addslashes($str[0]);
        },$text); 
        return json_decode($text);
    } 
    
     /**
     * 表情替换为空
     * @param string $text
     * @return string
     */ 
    public static function userTextEmpty($text)
    {
        if(!is_string($text))return $text;
        if(!$text || $text=='undefined')return '';
        $text = json_encode($text); //暴露出unicode
        $text = preg_replace_callback('/(\\\u[ed][0-9a-f]{3})/i',function($str){
            return '';
        },$text);
        return json_decode($text);
    }
    
    /*
     * 计算星座
     * 输入：日期 格式(2017-07-04)
     * 输出：星座名称或者错误信息
     */
    public static function getZodiacSign($date)
    {
        if (empty($date)) {
            return '';
        }
        $time = strtotime($date);
        if (empty($time)) {
            return '';
        }
        $month = intval(date('n',$time));
        $day = intval(date('j',$time));
        // 检查参数有效性
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return '';
        }
        // 星座名称以及开始日期
        $signs = array(
            1 => array(20 => '水瓶座'),
            2 => array(19 => '双鱼座'),
            3 => array(21 => '白羊座'),
            4 => array(20 => '金牛座'),
            5 => array(21 => '双子座'),
            6 => array(22 => '巨蟹座'),
            7 => array(23 => '狮子座'),
            8 => array(23 => '处女座'),
            9 => array(23 => '天秤座'),
           10 => array(24 => '天蝎座'),
           11 => array(22 => '射手座'),
           12 => array(22 => '摩羯座'),
        );
        list($sign_start, $sign_name) = each($signs[$month]);
        if ($day < $sign_start) {
            list($sign_start, $sign_name) = each($signs[$month<2 ? 12: $month-1]);
        }
        return $sign_name;
    }

    /**
     * 查询物流(快递网)
     * @author wyy
     * @param string $express_com 物流代号
     * @param string $express_sn  运单号码
     * @return array
     */
    public static function getExpressKdW($express_com,$express_sn)
    {
        if (empty($express_com) || empty($express_sn)) {
            return [];
        }
        $channelUrl = \Yii::$app->params['channelHost'];
        $url = $channelUrl. 'v1/express/express-kdw';
        $post = [
            'express_com' => $express_com,
            'express_sn'  => $express_sn,
        ];
        $data = CurlHelper::post($url,$post, false);
        //有物流信息
        if (isset($data['code']) && $data['code']=200) {
            return $data['data'];
        }
        return [];
    }

    /**
     * 查询物流(快递100)
     * @author wyy
     * @param string $express_com 物流代号
     * @param string $express_sn  运单号码
     * @return array
     */
    public static function getExpressKdB($express_com,$express_sn)
    {
        if (empty($express_com) || empty($express_sn)) {
            return [];
        }
        $channelUrl = \Yii::$app->params['channelHost'];
        $url = $channelUrl. 'v1/express/express-kdb';
        $post = [
            'express_com' => $express_com,
            'express_sn'  => $express_sn,
        ];
        $data = CurlHelper::post($url,$post, false);
        //有物流信息
        if (isset($data['code']) && $data['code']=200) {
            return $data['data'];
        }
        return [];
    }
    
    /**
     * 获取订单随机编码
     * @param string $prefix 前缀字符串 (SC特权商城，XF会员续费)
     * @author wyy
     * @return string
     */
    public static function getIdsn($prefix='') {
        return $prefix . date('YmdHis') . substr(time(),-5) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * 根据数据库对象 获取数据库的表名
     */
    public static function getDbTableName($tableObj=null) {
        if (empty($tableObj)) return '';
        preg_match("/dbname=([^;]+)/i", $tableObj::getDb()->dsn, $matches);
        $db = isset($matches[1])?'`'.$matches[1].'`':'';
        $table = $tableObj::tableName();
        return $db.'.'.$table;
    }
    
    /**
     * 汉语转拼音
     * @param string $words 汉字
     * @param number $type 类型（1带声调，2不带声调，3首字母）
     * @return string 拼音
     */
    public static function getPinYin($words='',$type=1) {
        $result = '';
        if (empty($words)) {
            return $result;
        }
        $Pinyin = new ChinesePinyin();
        switch ($type) {
            case 1:
                //转成带有声调的汉语拼音
                $result = $Pinyin->TransformWithTone($words);
                break;
            case 2:
                //转成带无声调的汉语拼音
                $result = $Pinyin->TransformWithoutTone($words,' ');
                break;
            case 3:
                //转成汉语拼音首字母
                $result = $Pinyin->TransformUcwords($words);
                break;
        }
        return $result;
    }
}
