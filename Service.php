<?php


class Service
{
    private $param = [
        'host' => '',
        'port' => '',
        'path' => ''
    ];

    private $config = [
        'max_size' => 2048
    ];

    private $server = null;

    public function __construct($param)
    {
        $this->check();
        $this->init($param);
    }

    private function check()
    {
        $path = $this->param['path'];
        $realPath = realpath(__DIR__.$path);
        if($realPath == false || !file_exists($realPath)){
            exit("{$path} error!");
        }
    }

    private function init($param)
    {
        $this->param = $param;
        $this->createServer();
    }

    private function createServer()
    {
        $this->server = stream_socket_server("tcp://{$this->param['host']}:{$this->param['port']}",$errno,$errstr);
        if(!$this->server){
            exit([$errno,$errstr]);
        }
        echo "====== server is running listen:{$this->param['host']}:{$this->param['port']} ======\n";
    }

    public static function instance($param)
    {
        return new Service($param);
    }

    //运行
    public function run()
    {

        while (true){
            $client = stream_socket_accept($this->server);
            if($client){
                echo "新的请求：\n";
                $buf = fread($client,$this->config['max_size']);
                print_r(" 接收原始数据：".$buf."\n");
                $this->parseProtocol($buf,$class,$method,$params);
                $this->execMethod($client,$class,$method,$params);

            }
        }
    }

    //执行方法
    private function execMethod($client,$class,$method,$params)
    {
        if($class && $method){
            $class = ucfirst($class);
            $file = $this->param['path'].'/'.$class.'.php';
            if(file_exists($file)){
                require_once $file;
                $obj = new $class();
                if(!$params){
                    $data = $obj->$method();
                }else{
                    $data = $obj->$method($params);
                }
                $this->packProtocol($data);
                fwrite($client,$data);
                fclose($client);
                echo " 返回数据：$data \n";
                return;
            }
        }
        fwrite($client,"class or method error");
        fclose($client); //返回数据以后及时关闭连接 不然会导致客户端fread阻塞读 feof读取不到最后一行
    }

    //打包返回数据格式
    private function packProtocol(&$data)
    {
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
    }

    //解包数据格式
    private function parseProtocol($buf,&$class,&$method,&$params)
    {
        $buf = json_decode($buf,true);
        $class = $buf['class'];
        $method = $buf['method'];
        $params = $buf['params'];
    }


}

//启动服务
Service::instance([
    'host' => '127.0.0.1',
    'port' => '8888',
    'path' => './api'
])->run();