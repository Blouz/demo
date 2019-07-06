<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>设置自定义服务时间V2版</title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/plug/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/plug/bootstrap/css/bootstrap-theme.min.css">
    <script type="text/javascript" src="/js/jquery-2.0.3.js"></script>
    <script type="text/javascript" src="/js/plug.js"></script>
    <script src="/plug/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
    <h2>设置自定义服务时间V2</h2>

    <blockquote>
        <p>使用说明：</p>
        <p>非管理员禁止操作！</p>
        <p>仅用于更新/新增 i500_service_week_time 中服务时间数据。</p>
        <p><a href="/v1/plug/time">V1版-设置服务时间</a></p>
    </blockquote>

    <!-- UID -->
    <div class="input-group" style="margin-top: 5px;">
        <span class="input-group-addon">UID<span style="color: red;"> [必传]</span></span>
        <input type="text" id="uid" class="form-control" placeholder="请输入UID">
    </div>
    <!-- Mobile -->
    <div class="input-group" style="margin-top: 5px;">
        <span class="input-group-addon">Mobile<span style="color: red;"> [必传]</span></span>
        <input type="text" id="mobile" class="form-control" placeholder="请输入手机号">
    </div>

    <div class="input-group" style="margin-top: 15px;">
        <h4>默认服务时间如下</h4>
        <h5>周一 ~ 周五 （19：00 至 21：00）</h5>
        <h5>周六 ~ 周日 （10：00 至 21：00）</h5>
    </div>

    <div style="margin-top: 15px;text-align: center;">
        <button type="button" class="btn btn-default save-default-service-time"> 保存 </button>
    </div>

</div>
</body>
</html>

