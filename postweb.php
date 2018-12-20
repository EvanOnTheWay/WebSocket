<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>websocket</title>
</head>
<body>
<input id="text" value="">
<input type="submit" value="send" onclick="start()">
<div id="msg"></div>
</body>
<script>
    /**
     0：未连接
     1：连接成功，可通讯
     2：正在关闭
     3：连接已关闭或无法打开
     */


    //创建一个webSocket 实例
    var webSocket  = new  WebSocket("ws://127.0.0.1:8090");


    webSocket.onerror = function (event){
        onError(event);
    };

    // 打开websocket
    webSocket.onopen = function (event){
        onOpen(event);
        webSocket.send("html_page1");
    };

    //监听消息
    webSocket.onmessage = function (event){
        onMessage(event);
    };


    webSocket.onclose = function (event){
        onClose(event);
    }

    window.onbeforeunload = function () {
        websocket.close();
    }


    //关闭监听websocket
    function onError(event){
        document.getElementById("msg").innerHTML = "<p>close</p>";
        console.log("error"+event.data);
        websocket.close();
    };

    function onOpen(event){
        console.log("open:"+sockState());
        document.getElementById("msg").innerHTML = "<p>Connect to Service</p>";
    };

    function onMessage(event){
        console.log("onMessage");
        document.getElementById("msg").innerHTML += "<p>response:"+event.data+"</p>"
    };

    function onClose(event){
        document.getElementById("msg").innerHTML = "<p>close</p>";
        console.log("close:"+sockState());
        webSocket.close();
    }

    function sockState(){
        var status = ['未连接','连接成功，可通讯','正在关闭','连接已关闭或无法打开'];
        return status[webSocket.readyState];
    }

    function start(event){
        console.log(webSocket);
        var msg = document.getElementById('text').value;
        document.getElementById('text').value = '';
        console.log("send:"+sockState());
        console.log("msg="+msg);
        // 发送内容包括发送对象，以json形式发送给后台操作
        var sendmsg = {"to":"html_page2","msg":msg,}
        webSocket.send(JSON.stringify(sendmsg));
        // document.getElementById("msg").innerHTML += "<p>request"+msg+"</p>"
    };

</script>

</html>
