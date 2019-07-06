<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport">
<meta content="yes" name="apple-mobile-web-app-capable">
<meta content="black" name="apple-mobile-web-app-status-bar-style">
<meta name="format-detection" content="telephone=no">
<title>记住手机</title>
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
.btnBox{padding:0 2%;}
.btn,.btnH,.btnS{height:44px; line-height:44px;text-align:center;font-size:1.8rem;border-radius:4px;}
.btnS{height:30px; line-height:30px; font-size:1.4rem;}
.btn,.btnS{background:#fc6a68;color:#fff;}
.btn:visited,.btnS:visited{color:#fff;}
.btnH,.btnH:visited{background:#d8d8d8; color:#747474;}


.redPackets{width:80%;  margin:20px auto 35px;z-index:90;}
.redPacketsBox{top:0; left:0; z-index:110; color:#fc6a68; padding:15px 18px 15px 43.5%;}
.redPacketsB{width:100%;height:50px; margin-top:11%;text-align:center;}
.telbox{color:#020202; font-size:1.4rem; text-align:center; line-height:20px;} 
.formredPacket{width:85%; margin:0 auto 30px;}
.redPacketMsg{widdth:95%; padding:0 2.5% 50px;}
.redPacketMsg .tit{height:32px; background:url(/images/line.gif) repeat-x left center; text-align:center; margin-bottom:10px;}
.redPacketMsg .titB{padding:1px; background:#FBDF61; border-radius:15px; overflow:hidden; font-size:1.6rem;}
.redPacketMsg .titB span{height:30px; line-height:30px; background:#F6A671; color:#fff; padding:0 10px; border-radius:15px;}
.redPacketMsgBox{width:90%; margin:0 auto; line-height:22px;}
</style>
</head>
<body>
<div class="bannerWarp wm"><img src="/images/150826.jpg"></div>
<div class="redPackets pr">
 <img src="/images/bg.png">
 <div class="redPacketsBox wm pa">
  <div class="redPacketsB">
   <span>￥</span>
   <span style="font-size:5.0rem;"><?php echo $data['money'];?></span>
  </div>
 </div>
 <p class="telbox wm mt10">红包已放进您的账户，别放过期了哦!<br><span><?php echo $data['mobile'];?></span></p>
</div>
<div class="formredPacket">
<a href="#" class="btn">下载爱500米客户端</a>
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
