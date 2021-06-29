<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

use RabbitMQ\RabbitMQ;


/**
 * 原理：
 * 1.首先客户端通过RPC向服务端发出请求（我这里有一堆东西需要你给我处理一下，correlation_id：这是我的请求标识，reply_to：你处理完过后把结果返回到这个队列中。）
 *
 * 2.服务端拿到了请求，开始处理并返回（correlation_id：这是你的请求标识 ，原封不动的给你。 这时候客户端用自己的correlation_id与服务端返回的id进行对比。是我的，就接收。）
 */


/**
 * Class RpcMqClient
 * rpc 客户端类
 */

class RpcMqClient{

    private $rabbit;

    /** 回调的队列名称
     * @var mixed
     */
    public $callbackQueueName;

    /**
     * 发送rpc请求到目标队列名称
     * @var string
     */
    public $rpcQueueName;

    /**
     * 返回的数据
     * @var
     */
    private $response;

    /**
     * 每个rpc请求的唯一标识
     * @var
     */
    private $corr_id;

    public function __construct(){

        $this->rabbit = new RabbitMQ();

        $this->rpcQueueName = 'test_rpc_queue';

        //第一种声明匿名的回调队列：名称类似这样amq.gen-C_jxd8mEKM7ppwfaK0V6gQ, 在管理后台是不会显示的（推荐用这种方式）
        list($this->callbackQueueName,,) = $this->rabbit->getChannel()->queue_declare('',false,false,true,false);

        //第二种声明自己定义的回调队列：在后台会显示，但是回调完成后会消失，所以推荐第一种匿名的方式
        //$this->callbackQueueName = 'test_rpc_callback_queue';
        //$this->rabbit->getChannel()->queue_declare($this->callbackQueueName,false,false,true,false);

        $this->rabbit->getChannel()->basic_consume($this->callbackQueueName,'',false,true,false,false,array($this,'on_response'));


    }

    /**
     * 回调值判断
     * @param $rep
     */
    public function on_response($rep){
        if($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    /**
     * 发送rpc请求方法
     * @param $n
     * @return int
     * @throws ErrorException
     */
    public function call($n){

        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            (string)$n,
            array('correlation_id'=> $this->corr_id, 'reply_to'=> $this->callbackQueueName)
        );

        $this->rabbit->getChannel()->basic_publish($msg,'',$this->rpcQueueName);

        while (!$this->response){
            $this->rabbit->getChannel()->wait();
        }
        return intval($this->response);
    }

}


//测试测试
$client = new RpcMqClient();
echo $client->callbackQueueName. PHP_EOL;
for ($i=1; $i<=10; $i++){
    $num = $i+10;
    $response = $client->call($num);
    echo " call {$num} Got ", $response, PHP_EOL;
}





