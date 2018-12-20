<?php
/**
 * websocket
 * Created by zhanghuihui
 * Date: 2018/12/19
 * Time: 14:33
 */
error_reporting(E_ALL^E_NOTICE);
class SocketService
{
    private $address  = '127.0.0.1';
    private $port = 8090;
    private $_sockets;
    public function __construct($address = '', $port='')
    {

        if(!empty($address)){
            $this->address = $address;
        }
        if(!empty($port)) {
            $this->port = $port;
        }
    }

    public function service(){
        //获取tcp协议号码。
        $tcp = getprotobyname("tcp");
        $sock = socket_create(AF_INET, SOCK_STREAM, $tcp);
        socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
        if($sock < 0)
        {
            throw new Exception("failed to create socket: ".socket_strerror($sock)."\n");
        }
        socket_bind($sock, $this->address, $this->port);
        socket_listen($sock, $this->port);
//        echo "listen on $this->address $this->port ... \n";
        $this->_sockets = $sock;
    }

    public function run(){
        $this->service();
        $clients[] = $this->_sockets;
        while (true){
            $changes = $clients;
            $write = NULL;
            $except = NULL;
            socket_select($changes,  $write,  $except, NULL);
            # 判断是不是新接入的socket
            foreach($changes as $key => $_sock){
                if($this->_sockets == $_sock){ #  新socket
                    if(($newClient = socket_accept($_sock))  === false){
                        die('failed to accept socket: '.socket_strerror($_sock)."\n");
                    }

                    $line = trim(socket_read($newClient, 1024));
                    $this->handshaking($newClient, $line);
                    $clients[$this->getSecWebSocketKey($line)] = $newClient;
                }else{
                    # 不是新socket
                    $cnt = socket_recv($_sock, $buffer,  2048, 0);

                    if($cnt > 0){ #通信正常
                        $msg = $this->message($buffer);
                        # 注意：如果是用户输入内容发送过来则传过来的数据类型一定是json对象
                        if(preg_match('/^html_/',$msg)) { #/^html_/
                            $clients[$msg] = $_sock;
                            unset($clients[$key]);
                        }else {
                            $msgArr = json_decode($msg,true);
                            if($clients[$msgArr['to']] == ''){
                                # 说明该socket没有连接 具体操作后续再补上

                            }else{
                                // 业务逻辑 start

                                // 业务逻辑 end
                                $this->send($clients[$msgArr['to']], $msgArr['msg']);
                            }
                        }
                    }else{
                        // =0 连接关闭 <0 阻塞
                        $this->close($_sock);
                    }
                }
            }
        }
    }

    /**
     * 握手处理
     * @param $newClient socket
     * @return int  接收到的信息
     */
    public function handshaking($newClient, $line){
        $headers = array();
        $lines = preg_split("/\r\n/", $line);
        foreach($lines as $line)
        {
            $line = chop($line);
            if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
            {
                $headers[$matches[1]] = $matches[2];
            }
        }

        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $this->address\r\n" .
            "WebSocket-Location: ws://$this->address:$this->port/websocket/websocket\r\n".
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
        return socket_write($newClient, $upgrade, strlen($upgrade));
    }

    /**
     * 解析接收数据
     * @param $buffer
     * @return null|string
     */
    public function message($buffer){
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if ($len === 126)  {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127)  {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else  {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    /**
     * 发送数据
     * @param $newClinet 新接入的socket
     * @param $msg   要发送的数据
     * @return int|string
     */
    public function send($newClinet, $msg){
        $msg = $this->frame($msg);
        socket_write($newClinet, $msg, strlen($msg));
    }

    public function frame($s) {
        $a = str_split($s, 125);
        if (count($a) == 1) {
            return "\x81" . chr(strlen($a[0])) . $a[0];
        }
        $ns = "";
        foreach ($a as $o) {
            $ns .= "\x81" . chr(strlen($o)) . $o;
        }
        return $ns;
    }

    /**
     * 关闭socket
     */
    public function close($_sockets){
        return socket_close($_sockets);
    }

    /**
     *  获取Sec-WebSocket-Key
     */
    public function getSecWebSocketKey($line){
        $headers = array();
        $lines = preg_split("/\r\n/", $line);
        foreach($lines as $line)
        {
            $line = chop($line);
            if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
            {
                $headers[$matches[1]] = $matches[2];
            }
        }

        return $headers['Sec-WebSocket-Key'];
    }
}

$sock = new SocketService();
$sock->run();
