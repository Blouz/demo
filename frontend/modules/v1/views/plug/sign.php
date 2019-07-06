<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
        <title>Sign签名小工具</title>
        <meta name="description" content="">
        <meta name="keywords" content="">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <link rel="stylesheet" href="/plug/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" href="/plug/bootstrap/css/bootstrap-theme.min.css">
        <script type="text/javascript" src="/js/jquery-2.0.3.js"></script>
        <script src="/plug/bootstrap/js/bootstrap.min.js"></script>
    </head>
    <body>
    <div class="container">
        <h2>Sign签名小工具</h2>

        <!-- appID -->
        <div class="input-group" style="margin-top: 5px;">
            <span class="input-group-addon">appID</span>
            <input type="text" class="form-control" readonly value="I500_SOCIAL">
        </div>

        <!-- app_code -->
        <div class="input-group" style="margin-top: 5px;">
            <span class="input-group-addon">appCode</span>
            <input type="text" class="form-control" readonly value="DKJA@(SL)RssMAKDKas!L">
        </div>

        <!-- timestamp -->
        <div class="input-group" style="margin-top: 5px;">
            <span class="input-group-addon">timestamp</span>
            <input type="text" class="form-control" readonly value="<?php echo $data['timestamp'];?>">
        </div>

        <div style="margin-top: 10px; text-align: center;" class="alert alert-success" role="alert">
            在地址栏中传递参数，第一个参数用?连接，其余用&连接。
        </div>


        <?php if(!empty($data['msg'])) {
            ?>
            <div style="margin-top: 10px; text-align: center;" class="alert alert-success" role="alert">
                <?php echo $data['msg'];?>
            </div>
            <?php
        }?>
    </div>
    </body>
</html>

