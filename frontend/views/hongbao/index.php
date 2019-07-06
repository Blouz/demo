<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport">
<meta content="yes" name="apple-mobile-web-app-capable">
<meta content="black" name="apple-mobile-web-app-status-bar-style">
<meta name="format-detection" content="telephone=no">
<script type="text/javascript" src="/js/jquery-2.0.3.js"></script>
<title>填写手机</title>
<style>
html,body,p,form,fieldset,legend,input,textarea,button,select,img,select,menu,nav,header{margin:0;padding:0; border:0;}
html {font-size: 62.5%;}
body{width:100%;font:1.4rem 'microsoft yahei','黑体',Verdana,Arial,Helvetica,sans-serif;color:#303030;background:#FBDF61;}
html,body{-webkit-text-size-adjust:none;height:100%;} 
img{width:100%; height:auto; vertical-align:bottom;}
a{display:block;color:#020202;box-sizing:border-box; text-decoration:none;outline:none;}
a:active{star:expression(this.onFocus=this.blur());}
a:visited{color:#020202;}
:focus{outline:0; }
span{display:inline-block;}
input,textarea{outline:none;border:none;}
input[type="button"],input[type="submit"],input[type="reset"]{cursor:pointer;-webkit-appearance:none;}
input[type="text"],input[type="password"]{background:#fff; color:#303030; font-size:1.4rem;width:100%;}
/*float*/
.fr{float:right;}
.fl{float:left;}
.of{overflow:hidden;}
/*position*/
.pr{position:relative;}
.pa{position:absolute;}
.pf{position:fixed;}
/*warp*/
.wm{width:100%;box-sizing:border-box;}
/*margin padding*/
.mt10{margin-top:10px;}
.mb10{margin-bottom:10px;}

/*btn*/
.btn,.formredPacket p{font-size:1.8rem;border-radius:4px;}
.btn{height:44px; line-height:44px;text-align:center;background:#fc6a68;color:#fff;}
.btn:visited{color:#fff;}
.formredPacket p{height:35px;padding:9px 10px 0; background:#fff; margin-bottom:10px;}

.formredPacket{width:85%; margin:20px auto 30px;}
.redPacketMsg{widdth:95%; padding:0 2.5% 50px;}
.redPacketMsg .tit{height:32px; background:url(/images/line.gif) repeat-x left center; text-align:center; margin-bottom:10px;}
.redPacketMsg .titB{padding:1px; background:#FBDF61; border-radius:15px; overflow:hidden; font-size:1.6rem;}
.redPacketMsg .titB span{height:30px; line-height:30px; background:#F6A671; color:#fff; padding:0 10px; border-radius:15px;}
.redPacketMsgBox{width:90%; margin:0 auto; line-height:22px;}
</style>
</head>
<body>
<div class="bannerWarp wm"><img src="/images/150826.jpg"></div>
<div class="formredPacket">
<p><input type="text" id="mobile" placeholder="请输入您的手机号"></p>
<a style="cursor: pointer;" class="btn getHongBao">立刻领取</a>
</div>
<div class="redPacketMsg">
 <div class="tit"><span class="titB"><span>领取规则</span></span></div>
 <div class="redPacketMsgBox">
  1.红包新老用户同享<br>
  2.红包可与其他优惠叠加使用，首单支付红包不可叠加<br>
  3.红包仅限在爱500米最新版客户端下单且选择在线支付时使用<br>
  4.使用红包时下单手机号码必须为抢红包时手机号码<br>
  5.本活动最终解释权归爱500米所有
 </div>
</div>
</body>
</html>
<input type="hidden" id="token" value="<?php echo Yii::$app->getRequest()->getCsrfToken(); ?>" />
<input type="hidden" id="sign" value="<?php echo $sign;?>" />
<script>
    $(function(){
        $(".getHongBao").click(function(){
            var mobile = $("#mobile").val();
            if (!mobile) {
                alert('请输入手机号');
                return;
            }
            var token = $("#token").val();
            var sign  = $("#sign").val();
            $.ajax(
                {
                    type: "POST",
                    url : "/hongbao/get-hongbao",
                    data: {
                        'mobile'   : mobile,
                        'sign'     : sign,
                        '_csrf'    : token
                    },
                    async: false,
                    dataType: "json",
                    beforeSend: function () {
                    },
                    success: function (result) {
                        if (result.code === 'ok') {
                            window.location.href = '/hongbao/success?mobile='+mobile+'&sign='+sign;
                        } else if(result.code === 'have') {
                            alert(result['msg']);
                            window.location.href = '/hongbao/success?mobile='+mobile+'&sign='+sign;
                        } else {
                            alert(result['msg']);
                        }
                    }
                });
        });
    });
</script>
