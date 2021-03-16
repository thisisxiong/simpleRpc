<?php


class Client
{
    private $urlInfo = array();

    public function __construct($url)
    {
        $this->urlInfo = parse_url($url);
    }

    public static function instance($url)
    {
        return new Client($url);
    }

    public function __call($name,$args)
    {
        $client = stream_socket_client("tcp://{$this->urlInfo['host']}:{$this->urlInfo['port']}",$errno,$errstr);
        if(!$client){
            exit("{$errno}:{$errstr}");
        }

        $data = [
            'class' => str_replace('/','',$this->urlInfo['path']),
            'method' => $name,
            'params' => $args
        ];
        //fwrite($client,json_encode($data));
        stream_socket_sendto($client,json_encode($data));

        /**
         * Warning：如果服务器没有关闭由 fsockopen() 所打开的连接
         * feof() 会一直等待直到超时而返回 TRUE。
         * 默认的超时限制是 60 秒，可以使用 stream_set_timeout() 来改变这个值
         * 在服务端发送完数据及时关闭连接解决feof等待的问题
         */
        // $content = '';
        // do{
        //
        //     $tmp = fread($client,128);
        //     stream_set_blocking($client, 0); //非阻塞模式 会影响fread、fgets是否阻塞
        //     if(strlen($tmp) == 0){
        //         break;
        //     }
        //     $content .= $tmp;
        // }while(true);
        $content = '';
        while (!feof($client)){
            $content .= fread($client,128);
        }

        fclose($client);
        return $content;
    }
}

$cli = Client::instance("http://127.0.0.1:8888/user");
// echo $cli->getInfo()."\n";
echo $cli->getUser(['name' => 'jack','grade' => 'third']);
