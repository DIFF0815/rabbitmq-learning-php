<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;


/**
 * Class RpcMqServer
 * rpc 服务端类
 */

class RpcMqServer {

    private $rabbit;

    /**
     * 接收rpc请求来自的队列名称
     * @var string
     */
    private $rpcQueueName;

    public function __construct(){

        $this->rabbit = new RabbitMQ();

        $this->rpcQueueName = 'test_rpc_queue';

    }

    /**
     * 模拟数据处理的函数
     * @param $n
     * @return int
     */
    public function fib($n){

        if($n == 0)
            return 0;
        if ($n ==1)
            return 1;
        return $this->fib($n-1) + $this->fib($n-2);

    }

    /**
     * 启动服务函数
     * @throws ErrorException
     */
    public function run(){

        echo "[Server] Awaiting RPC requests".PHP_EOL;

        $this->rabbit->getChannel()->queue_declare($this->rpcQueueName,false,false,false,false);

        $callback = function($req) {
            $n = intval($req->body);

            echo "[.]fib(", $n, ")" . PHP_EOL;

            $msg = new AMQPMessage((string)$this->fib($n), ['correlation_id' => $req->get('correlation_id')]);

            $req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));

            $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
        };

        $this->rabbit->getChannel()->basic_qos(null,1,null);

        $this->rabbit->getChannel()->basic_consume($this->rpcQueueName,'',false,false,false,false, $callback);

        while ($this->rabbit->getChannel()->is_consuming()){
            $this->rabbit->getChannel()->wait();
        }

    }

    /**
     * 销毁
     */
    public function __destruct(){
        unset($this->rabbit);
    }

}

$server = new RpcMqServer();
$server->run();


