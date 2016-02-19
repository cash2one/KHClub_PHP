<?php if (!defined('THINK_PATH')) exit();?><!--<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    &lt;!&ndash;<script type="text/javascript" src="js/jquery-1.10.1.min.js"></script>&ndash;&gt;
    <title>调用用户的媒体设备</title>
</head>
<body>
<video id="video" width="500px" height="500px" style="border:5px solid #C63; margin-left: 400px" autoplay></video>


<script>
    //兼容写法获取媒体对象
    navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia  || navigator.mozGetUserMedia;
    //检测浏览器是否支持这个办法
    if(!navigator.getUserMedia){
        alert("对不起你的浏览器不支持该功能");
    }
    //获取video录像元素的对象
    var video = document.getElementById('video');
    navigator.getUserMedia(
            {
                video:true,
                audio:true
            },
            function(ys){
                video.src = window.URL.createObjectURL(ys);
                video.play();
            },
            function(error){
                alert(error.name||error);
            }
    );
</script>
</body>
</html>-->
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title></title>
</head>
<body style="background-color: red; margin: 0">
<br/>
姓名：<?php echo ($user["name"]); ?><br/>
密码：<?php echo ($user["password"]); ?><br/>
地址：<?php echo ($user["addr"]); ?>

</body>
</html>