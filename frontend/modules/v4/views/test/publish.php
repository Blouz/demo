<?php
/**
 * 简介
 *
 * PHP Version 5
 *
 * @category  SOCIALWAP
 * @package   CONTROLLER
 * @author    zhengyu <zhengyu@iyangpin.com>
 * @time      15/9/23 14:44
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      zhengyu@iyangpin.com
 */

?>
<!doctype html>
<html lang="zh">
<head>
<meta charset="utf-8">
<title>推送测试</title>
<meta name="description" content="zy">
<meta name="author" content="zy">
<script type="text/javascript" src="/js/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="/js/json2.js"></script>
<style type="text/css">
.zcss_t1 .zcss_td_l{width:200px;}
.zcss_t1 .zcss_td_r{width:500px;}
.zcss_t1 .zcss_td_r input{width:95%;}
.zcss_textarea1{height:100px;}

.zcss_tr_1{background-color: LightSkyBlue;}
.zcss_tr_2{background-color: LightYellow;}
.zcss_tac{text-align:center;}
</style>
</head>
<body>


<table border="1" class="zcss_t1">
    <tr>
        <td colspan="2" class="zcss_tac">获取通道信息，组合长连接url</td>
    </tr>
    <tr class="zcss_tr_1">
        <td class="zcss_td_l">通道名</td>
        <td class="zcss_td_r"><input type="text" class="zjs_sign_ch" value="test_channel_1" placeholder="这里输入通道名" /></td>
    </tr>
    <tr class="zcss_tr_1">
        <td class="zcss_td_l">通道超时时间</td>
        <td class="zcss_td_r"><input type="text" class="zjs_sign_expire" value="600" placeholder="通道超时时间，单位秒" /></td>
    </tr>

    <tr>
        <td colspan="2" class="zcss_tac"><input type="button" class="zjs_btn_sign" value="获取通道信息,返回数据组合长连接url" style="margin:10px;" /></td>
    </tr>

    <tr class="zcss_tr_2">
        <td class="zcss_td_l">返回数据</td>
        <td class="zcss_td_r"><div class="zcss_textarea1 zjs_sign_return"></div></td>
    </tr>
    <tr class="zcss_tr_2">
        <td class="zcss_td_l">通道名</td>
        <td class="zcss_td_r"><input type="text" class="zjs_sub_ch" value="" placeholder="这里输入通道名" /></td>
    </tr>
    <tr class="zcss_tr_2">
        <td class="zcss_td_l">序列</td>
        <td class="zcss_td_r"><input type="text" class="zjs_sub_seq" value="" placeholder="从发布时的返回值中获得" /></td>
    </tr>
    <tr class="zcss_tr_2">
        <td class="zcss_td_l">token</td>
        <td class="zcss_td_r"><input type="text" class="zjs_sub_token" value="" placeholder="从发布时的返回值中获得" /></td>
    </tr>
    <tr class="zcss_tr_2">
        <td class="zcss_td_l">获取通道数据的长连接url</td>
        <td class="zcss_td_r zjs_sub_url"></td>
    </tr>
</table>


<br />
<hr />
<br />


<table border="1" class="zcss_t1">
    <tr>
        <td colspan="2" class="zcss_tac">发布数据</td>
    </tr>
    <tr class="zcss_tr_1">
        <td class="zcss_td_l">通道名</td>
        <td class="zcss_td_r"><input type="text" class="zjs_pub_ch" value="" placeholder="这里输入通道名" /></td>
    </tr>
    <tr class="zcss_tr_1">
        <td class="zcss_td_l">内容</td>
        <td class="zcss_td_r"><input type="text" class="zjs_pub_content" value="" placeholder="向通道发布的数据" /></td>
    </tr>
    <tr>
        <td colspan="2" class="zcss_tac"><input type="button" class="zjs_btn_pub" value="发布数据" style="margin:10px;" /></td>
    </tr>
    <tr class="zcss_tr_2">
        <td class="zcss_td_l">返回数据</td>
        <td class="zcss_td_r"><div class="zcss_textarea1 zjs_pub_return"></div></td>
    </tr>
</table>


<script type="text/javascript">
$(function()
{
    $(".zjs_btn_sign").click(function()
    {
        var cname=$(".zjs_sign_ch").val();
        var expire=$(".zjs_sign_expire").val();
        $.post
        (
            "/v1/test/ajax?act=sign",
            {
                "cname":cname,
                "expire":expire
            },
            function(str)
            {
                console.log(str);
                $(".zjs_sign_return").html(str);

                obj=JSON.parse(str);
                $(".zjs_sub_ch").val(obj.data.cname);
                $(".zjs_sub_seq").val(obj.data.seq);
                $(".zjs_sub_token").val(obj.data.token);
                $(".zjs_pub_ch").val(obj.data.cname);

                var url="http://channel.test.i500m.com/icomet/sub?cname="+obj.data.cname+"&seq="+obj.data.seq+"&token="+obj.data.token;
                var html='<a class="zjs_sub_url" href="'+url+'" target="_blank">新页面打开此url</a>';
                html+='<br />';
                html+='<span>'+url+'</span>';
                $(".zjs_sub_url").html(html);
            }
        );
    });
    $(".zjs_btn_pub").click(function()
    {
        var cname=$(".zjs_pub_ch").val();
        var content=$(".zjs_pub_content").val();
        $.post
        (
            "/v1/test/ajax?act=pub",
            {
                "cname":cname,
                "content":content
            },
            function(str)
            {
                console.log(str);
                $(".zjs_pub_return").html(str);
            }
        );
    });
});


</script>


</body>
</html>
