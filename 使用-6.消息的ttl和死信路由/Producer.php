<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use \PhpAmqpLib\Wire\AMQPTable;
use RabbitMQ\RabbitMQ;


/**
 * 消息的TTL是time to live 的简称，顾名思义指的是消息的存活时间。
 *
 * Dead Letter Exchanges。一个消息在满足如下条件下，会进死信路由，记住这里是路由而不是队列，一个路由可以对应很多队列。
 * ①. 一个消息被Consumer拒收了，并且reject方法的参数里requeue是false。也就是说不会被再次放在队列里，被其他消费者使用。
 * ②. 上面的消息的TTL到了，消息过期了。
 * ③. 队列的长度限制满了。排在前面的消息会被丢弃或者扔到死信路由上。
 *
 * Dead Letter Exchange其实就是一种普通的exchange，和创建其他exchange没有两样。
 * (延迟队列)只是在某一个设置Dead Letter Exchange的队列中有消息过期了，会自动触发消息的转发，
 * 发送到Dead Letter Exchange中去。
 **/


/**
 * Class Producer
 * 这里用路由模型做例子 ，其他模型类似
 */
class Producer
{

    private $rabbit;

    private $exchangeName;
    private $queueName;
    private $routingKey;

    public function __construct()
    {

        $this->rabbit = new RabbitMQ;

        $this->exchangeName = 'test_use_ttl_ex';
        $this->queueName = 'test_use_ttl_queue';
        $this->routingKey = 'test_use_ttl_key';

        //测试以下两种情况时 需要把管理后台 test_use_ttl_ex test_use_ttl_queue  test_use_dead_ex test_use_dead_key1 test_use_dead_key2 删除

        //第一种 死信指定了交换机和路由key
        // 只有该队列下的该路由key消费者能收到死信
        //创建交换机
        /*$this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);
        $this->rabbit->createQueue($this->queueName, false, true,false,false,false,
            new AMQPTable([
                    "x-message-ttl" => 10000, //过期时间 单位毫秒, 10秒钟
                    "x-dead-letter-exchange" => "test_use_dead_ex", // 过期后 发送到的死信队列
                    "x-dead-letter-routing-key" => 'test_use_dead_key1', // 过期后 发送到的死信队列 routing_key
                    //       "x-max-length-bytes" // 最大字节数
                    //        "x-max-length" => 10, //容量个数
                    //        "x-expires" => 16000,// 自动过期时间
                    //        "x-max-priority"  // 权重
                ]
            )
        );
        $this->rabbit->bindQueue($this->queueName, $this->exchangeName, $this->routingKey);


        //声明 死信队列、交换机 并绑定
        $this->rabbit->createExchange('test_use_dead_ex', AMQPExchangeType::TOPIC, false, true, false);
        $this->rabbit->createQueue('test_use_dead_queue1', false, true,false);
        $this->rabbit->createQueue('test_use_dead_queue2', false, true,false);
        $this->rabbit->bindQueue('test_use_dead_queue1', 'test_use_dead_ex','test_use_dead_key1'); //test_use_dead_key1
        $this->rabbit->bindQueue('test_use_dead_queue2', 'test_use_dead_ex','test_use_dead_key2'); //test_use_dead_key2 */

        //第二种 只指定了死信交换机
        //创建交换机
        $this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);
        $this->rabbit->createQueue($this->queueName, false, true,false,false,false,
            new AMQPTable([
                    "x-message-ttl" => 10000, //过期时间 单位毫秒, 10秒钟
                    "x-dead-letter-exchange" => "test_use_dead_ex", // 过期后 发送到的死信队列
                    //"x-dead-letter-routing-key" => 'test_use_dead_key1', // 过期后 发送到的死信队列 routing_key
                    //       "x-max-length-bytes" // 最大字节数
                    //        "x-max-length" => 10, //容量个数
                    //        "x-expires" => 16000,// 自动过期时间
                    //        "x-max-priority"  // 权重
                ]
            )
        );
        $this->rabbit->bindQueue($this->queueName, $this->exchangeName, $this->routingKey);


        //声明 死信队列、交换机 并绑定
        $this->rabbit->createExchange('test_use_dead_ex', AMQPExchangeType::TOPIC, false, true, false);
        $this->rabbit->createQueue('test_use_dead_queue1', false, true,false);
        $this->rabbit->createQueue('test_use_dead_queue2', false, true,false);
        $this->rabbit->bindQueue('test_use_dead_queue1', 'test_use_dead_ex','#'); //test_use_dead_key1
        $this->rabbit->bindQueue('test_use_dead_queue2', 'test_use_dead_ex','#'); //test_use_dead_key2



    }

    /**
     * 启动
     */
    public function run(){

        for($i=1;$i<=10;$i++){

            echo $i.PHP_EOL;

            //为消息添加消息头
            $msg = "this a test message " . $i ;

            //第二种 创建消息时直接指定
            $message = new AMQPMessage($msg,[
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);

            //
            $this->rabbit->getChannel()->basic_publish($message,$this->exchangeName,$this->routingKey);

        }

    }

}

$producer = new Producer();
$producer->run();




