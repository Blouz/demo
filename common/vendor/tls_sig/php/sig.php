<?php
namespace common\vendor\tls_sig\php;
use common\helpers;;
class sig
{
    public static function signature($identifier, $sdkappid,$private_key_path,$path)
    {
        # 这里需要写绝对路径，开发者根据自己的路径进行调整
//        $private_key_path = "D:/WWW/social/common/vendor/tls_sig/php/private_key";
        $command = $path
        . ' ' . escapeshellarg($private_key_path)
        . ' ' . escapeshellarg($sdkappid)
        . ' ' . escapeshellarg($identifier);
        $ret = exec($command, $out, $status);
        if ($status == -1)
        {
            return null;
        }
        return $out;
    }
}
//$res = signature($identifier, $sdkappid, $private_key_path);
//
//var_dump($res);