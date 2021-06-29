<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;


/**
 * 消息的return机制
 *
 * 生产者通过指定一个exchange 和 routingkey  把消息送达到某个队列中去，
 * 然后消费者监听队列，进行消费处理。
 * 但是在某些情况下，如果我们在发送消息时，当前的exchange 不存在或者指定的routingkey路由不到，
 * 这个时候如果要监听这种不可达的消息，
 * 就要使用 return
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

        $this->exchangeName = 'test_use_return_ex';
        $this->routingKey = 'test_use_return_key';

        //创建交换机 不绑定任何队列
        $this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT);

        // 注册return callback
        $this->rabbit->getChannel()->set_return_listener(array($this,'returnCallback'));

    }

    /**
     * return机制回调 return 用于处理一些不可路由的消息！
     * @param AMQPMessage $message
     */
    public function returnCallback($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $message){
        echo " ---replyCode--- {$replyCode}" . PHP_EOL;
        echo " ---replyText--- {$replyText}" . PHP_EOL;
        echo " ---exchange--- {$exchange}" . PHP_EOL;
        echo " ---routingKey--- {$routingKey}" . PHP_EOL;
        echo " ---message body--- {$message->body}" . PHP_EOL;
    }

    /**
     * 启动
     */
    public function run(){

        //发送消息
        $msg = 'a message from return producer';
        $msg = new AMQPMessage($msg);

        //第四个参数 mandatory 一定要设置true ，否则这些路由不到的消息就会被broker端自动删除，只有设置成true后监听器会接受到路由不可达的消息 然后后续处理
        $this->rabbit->getChannel()->basic_publish($msg,$this->exchangeName,$this->routingKey,true);

        //channel要wait 才可看到结果
        $this->rabbit->getChannel()->wait();

    }

}


//测试
$producer = new Producer();
$producer->run();
