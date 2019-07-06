<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
        <title>User Sms or Token</title>
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
        <h2>User Sms or Token</h2>

        <!-- 手机号 -->
        <div class="input-group" style="margin-top: 5px;">
            <span class="input-group-addon">手机号</span>
            <input type="text" class="form-control" id="mobile">
        </div>
        <div style="margin-top: 15px;text-align: center;">
            <button type="submit" class="btn btn-default btn-get-token">获取Token</button>
            <button type="submit" class="btn btn-default btn-get-code">获取验证码</button>
        </div>

            <div style="margin-top: 10px; text-align: center; display: none;" class="alert alert-success token" role="alert">
            </div>

        <table class="table table-hover table-code" style="display: none; margin-top: 10px;">
            <tr>
                <th>手机号</th>
                <th>验证码</th>
                <th>短信类型</th>
                <th>创建时间</th>
                <th>有效期</th>
            </tr>
            <tr>
                <td class="mobile"></td>
                <td class="code"></td>
                <td class="type"></td>
                <td class="create_time"></td>
                <td class="expires_in"></td>
            </tr>
        </table>

        <!-- 字符串 -->
        <div class="input-group" style="margin-top: 10px;">
            <span class="input-group-addon">MD5前</span>
            <input type="text" class="form-control" id="md5_before">
        </div>
        <div class="input-group" style="margin-top: 10px;">
            <span class="input-group-addon">MD5后</span>
            <input type="text" class="form-control" readonly id="md5_after">
        </div>
        <div style="margin-top: 5px;text-align: center;">
            <button type="submit" class="btn btn-default btn-md5">MD5</button>
        </div>
        <div class="input-group" style="margin-top: 10px;">
            <span class="input-group-addon">错误代码</span>
            <input type="text" placeholder="例如：400" class="form-control" id="error_code">
        </div>
        <div style="margin-top: 10px;text-align: center;">
            <button type="submit" class="btn btn-default btn-get-error-msg">获取错误信息</button>
        </div>
        <div style="margin-top: 10px; text-align: center; display: none;" class="alert alert-success error-msg" role="alert">
        </div>

        <div class="input-group" style="margin-top: 10px;">
            <span class="input-group-addon">解绑号码</span>
            <input type="text" placeholder="例如：13264185553" class="form-control" id="bind_user">
        </div>
        <div style="margin-top: 10px;text-align: center;">
            <button type="submit" class="btn btn-default btn-remove-bind-user">解绑</button>
        </div>
        <div style="margin-top: 10px; text-align: center; display: none;" class="alert alert-success remove-bind-user-msg" role="alert">
        </div>

        <div class="input-group" style="margin-top: 10px;">
            <span class="input-group-addon">手机号码</span>
            <input type="text" placeholder="例如：13264185553" class="form-control" id="remove_user">
        </div>
        <div style="margin-top: 10px;text-align: center;">
            <button type="submit" class="btn btn-default btn-remove-user">删除</button>
            <button type="submit" class="btn btn-default btn-check-user">是否存在</button>
        </div>
        <div style="margin-top: 10px; text-align: center; display: none;" class="alert alert-success user-msg" role="alert">
        </div>
    </div>
    </body>
</html>

