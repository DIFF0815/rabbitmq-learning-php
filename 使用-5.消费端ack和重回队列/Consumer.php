<?php

require_once '../../vendor/autoload.php';
include_once '../RabbitMQ.php';

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use \PhpAmqpLib\Wire\AMQPTable;
use RabbitMQ\RabbitMQ;


/**
 * 消费端ack和重回队列
 **/


/**
 * Class Producer
 * 这里用路由模型做例子 ，其他模型类似
 */

class Consumer{

    private $rabbit;

    private $exchangeName;
    private $queueName;
    private $routingKey;

    public function __construct(){

        $this->rabbit = new RabbitMQ;

        $this->exchangeName = 'test_use_ack_ex';
        $this->queueName = 'test_use_ack_queue';
        $this->routingKey = 'test_use_ack_key';

        //创建交换机
        $this->rabbit->createExchange($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);
        $this->rabbit->createQueue($this->queueName, false, true);
        $this->rabbit->bindQueue($this->queueName, $this->exchangeName, $this->routingKey);

    }


    /**
     * 模拟数据处理函数
     * @param $message
     */
    public function dealData($message){

        echo '----------------------'.PHP_EOL;
        $header =  $message->get('application_headers')->getNativeData();
        //var_dump($header);

        if($header['num']%2){

            echo "---{$header['num']}-----ack" . PHP_EOL;
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        }else{

            //模拟重回队列或丢弃
            //第一种 不ack 保留在queue里边，
            //第二种 nack 然后根据错误类型 决定是重回队列 还是抛弃改消息.
            // 1. requeue true 这条消息重新放回队列重新消费 ，
            // 2. requeue false 抛弃这条消息
            //$requeue = false;
            $requeue = true;

            if($requeue){
                //模拟重试五次后不再重试
                $retry_nums = $header['retry_nums'];

                $nums = $retry_nums+1;

                //逻辑：把该消息写进一个延时队列（该队列消息过期后再写回本队列,每次都会累计一个标记数）
                //---- 如果跑不对 看下是不是 脚本启动的顺序不对：先启动ProducerRequeue.php  再启动Consumer.php  最后启动Producer.php
                //执行结果
                //----------------------
                //---1-----ack
                //----------------------
                //---2-----requeue = true nack and retry_nums=0 <=5
                //----------------------
                //---3-----ack
                //----------------------
                //---4-----requeue = true nack and retry_nums=0 <=5
                //----------------------
                //---2-----requeue = true nack and retry_nums=1 <=5
                //----------------------
                //---4-----requeue = true nack and retry_nums=1 <=5
                //----------------------
                //---2-----requeue = true nack and retry_nums=2 <=5
                //----------------------
                //---4-----requeue = true nack and retry_nums=2 <=5
                //----------------------
                //---2-----requeue = true nack and retry_nums=3 <=5
                //----------------------
                //---4-----requeue = true nack and retry_nums=3 <=5
                //----------------------
                //---2-----requeue = true nack and retry_nums=4 <=5
                //----------------------
                //---4-----requeue = true nack and retry_nums=4 <=5
                //----------------------
                //---2-----requeue = true nack and retry_nums=5 <=5
                //----------------------
                //---4-----requeue = true nack and retry_nums=5 <=5
                //----------------------
                //---2-----requeue = true nack and retry_nums over 5 times  and  it will be set requeue = false
                //----------------------
                //---4-----requeue = true nack and retry_nums over 5 times  and  it will be set requeue = false
                $deadHeaderObj = new AMQPTable([
                    'num'=>$header['num'],
                    'retry_nums'=>$nums,
                ]);
                $deadMessage = new AMQPMessage($message->body,[
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'application_headers' => $deadHeaderObj
                ]);

                if($retry_nums<=5){
                    echo "---{$header['num']}-----requeue = true nack and retry_nums={$retry_nums} <=5 " . PHP_EOL;
                    $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag']);

                    //把消息写进延时重试队列中
                    $this->rabbit->getChannel()->basic_publish($deadMessage,'test_use_ack_requeue_ex','test_use_ack_requeue_key');


                }else{
                    //正常业务中应该是重试5次后把该消息记录在错误记录中后面人工补偿
                    echo "---{$header['num']}-----requeue = true nack and retry_nums over 5 times  and  it will be set requeue = false" . PHP_EOL;
                    $requeue = false;
                    $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag'], false, $requeue);
                }

            }else{

                echo "---{$header['num']}-----requeue = false nack" . PHP_EOL;

                $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag'], false, $requeue);

            }


        }


    }

    /**
     * 启动
     * @throws ErrorException
     */
    public function run(){

        //接收消息 并设置处理函数处理
        $this->rabbit->consumeMessage($this->queueName,array($this,'dealData'));

        //等待
        while ($this->rabbit->getChannel()->is_consuming()){
            $this->rabbit->getChannel()->wait();
        }

    }
}


//测试
$consumer = new Consumer();
$consumer->run();



