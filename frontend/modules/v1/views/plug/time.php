<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>自定义服务时间V1版</title>
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
    <h2>自定义服务时间V1版</h2>

    <blockquote>
        <p>使用说明：</p>
        <p>非管理员禁止操作！</p>
        <p>仅用于更新 i500_service_time 中的 hours 字段。</p>
        <p><a href="/v1/plug/time2">V2版-设置默认服务时间</a></p>
    </blockquote>

    <!-- ID -->
    <div class="input-group" style="margin-top: 5px;">
        <span class="input-group-addon">ID<span style="color: red;"> [必传]</span></span>
        <input type="text" id="time_id" class="form-control" placeholder="请输入i500_service_time.Id">
    </div>

    <!-- time -->
    <div class="input-group" style="margin-top: 15px;">
        <h4>设置服务时间<span style="color: red;">[必传]</span></h4>

        <label class="checkbox-inline">
            <input type="checkbox" name="service_time"  value="10"> 10:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="11"> 11:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="12"> 12:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="13"> 13:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="14"> 14:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="15"> 15:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="16"> 16:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="17"> 17:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="18"> 18:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="19"> 19:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="20"> 20:00
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="service_time" value="21"> 21:00
        </label>
    </div>

    <div style="margin-top: 15px;text-align: center;">
        <button type="button" class="btn btn-default save-service-time"> 保存 </button>
    </div>

</div>
</body>
</html>

