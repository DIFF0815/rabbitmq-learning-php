<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use \PhpAmqpLib\Wire\AMQPTable;
use RabbitMQ\RabbitMQ;


/**
 * 消息的return机属性和自定义消息头
 **/


/**
 * Class Producer
 * 这里用路由模型做例子 ，其他模型类似
 */
class Producer{

    private $rabbit;

    private $exchangeName;
    private $queueName;
    private $routingKey;

    public function __construct(){

        $this->rabbit = new RabbitMQ;

        $this->exchangeName = 'test_use_header_ex';
        $this->queueName = 'test_use_header_queue';
        $this->routingKey = 'test_use_header_key';

        //创建交换机
        $this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);
        $this->rabbit->createQueue($this->queueName, false, true);
        $this->rabbit->bindQueue($this->queueName, $this->exchangeName, $this->routingKey);

    }


    /**
     * 启动
     */
    public function run(){

        // 创建头消息,各种数据格式
        $headers = new AMQPTable(
            [
                'a1' => 1,
                'b1' => 2,
            ]
        );
        //为消息头添加数据
        $headers->set('c1',3);
        $headers->set('d1',5);
        //获取消息头数据
        $headersData = $headers->getNativeData();
        var_dump($headersData);
        echo PHP_EOL;

        //为消息添加消息头
        $msg = "this a header message ";
        //第一种方式 set 方式
        $message = new AMQPMessage($msg);
        $message->set('delivery_mode', AMQPMessage::DELIVERY_MODE_PERSISTENT);
        $message->set('application_headers', $headers);

        /*//第二种 创建消息时直接指定
        $message = new AMQPMessage($msg,[
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => $headers
        ]);*/

        //扩展知识  message 属性
        //        'content_type' => 'shortstr',                 //消息的内容类型，如：text/plain
        //        'content_encoding' => 'shortstr',             //消息内容编码
        //        'application_headers' => 'table_object',      //设置消息的header,类型为table_object
        //        'delivery_mode' => 'octet',                   //1（nopersistent）非持久化，2（persistent）持久化
        //        'priority' => 'octet',                        //消息的优先级
        //        'correlation_id' => 'shortstr',               //关联ID(比如rpc的时候可以利用这个)
        //        'reply_to' => 'shortstr',                     //用于指定回复的队列的名称(比如rpc的时候可以利用这个)
        //        'expiration' => 'shortstr',                   //消息的失效时间
        //        'message_id' => 'shortstr',                   //消息ID
        //        'timestamp' => 'timestamp',                   //消息的时间戳
        //        'type' => 'shortstr',                         //类型
        //        'user_id' => 'shortstr',                      //用户ID
        //        'app_id' => 'shortstr',                       //应用程序ID
        //        'cluster_id' => 'shortstr',                   //集群ID



        //
        $this->rabbit->getChannel()->basic_publish($message,$this->exchangeName,$this->routingKey);


    }

}


//测试
$producer = new Producer();
$producer->run();
