<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
        <title>SSDB Manage</title>
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
        <h2>SSDB Manage</h2>

        <div style="margin-top: 15px;margin-bottom: 15px;text-align: center;">
            <button type="button" class="btn btn-default btn-get-all-key">获取所有SSDB KEY(<=1000)</button>
        </div>

        <div class="row all-keys" style="margin-bottom: 15px;">

        </div>

        <div style="margin-top: 10px; text-align: center; display: none;" class="alert alert-success key-value" role="alert">
        </div>

        <!-- ssdb_key -->
        <div class="input-group" style="margin-top: 5px;">
            <span class="input-group-addon">SSDB KEY</span>
            <input type="text" placeholder="不要输入前缀" class="form-control" id="ssdb_key">
        </div>
        <div style="margin-top: 15px;text-align: center;">
            <button type="button" class="btn btn-default btn-get-key">获取Value</button>
            <button type="button" class="btn btn-default btn-del-key">清空Value</button>
        </div>

    </div>
    </body>
</html>

