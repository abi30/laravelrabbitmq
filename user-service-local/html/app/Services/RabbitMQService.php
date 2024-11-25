<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    private $connection;
    private $channel;

    public function __construct()
    {
        try {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host', 'localhost'),
                config('rabbitmq.port', 5672),
                config('rabbitmq.username', 'guest'),
                config('rabbitmq.password', 'guest')
            );
            $this->channel = $this->connection->channel();
        } catch (\Exception $e) {
            Log::error('RabbitMQ Connection Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function publishMessage($queueName, $message)
    {
        try {
            // Declare the queue
            $this->channel->queue_declare($queueName, false, true, false, false);

            // Create a message
            $msg = new AMQPMessage(
                json_encode($message),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            // Publish the message
            $this->channel->basic_publish($msg, '', $queueName);

            Log::info("Message published to queue: $queueName", ['message' => $message]);
        } catch (\Exception $e) {
            Log::error('RabbitMQ Publish Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function consumeMessagesx($queueName, $callback)
    {
        try {
            // Declare the queue
            $this->channel->queue_declare($queueName, false, true, false, false);

            // Set up consumer
            $this->channel->basic_consume(
                $queueName, 
                '',
                false,
                false,
                false,
                false,
                $callback
            );

            // Keep consuming messages
            while ($this->channel->consuming()) {
                $this->channel->wait();
            }
        } catch (\Exception $e) {
            Log::error('RabbitMQ Consume Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function consumeMessages($queueName, $callback)
    {
        try {
            // Declare the queue
            $this->channel->queue_declare($queueName, false, true, false, false);

            // Set QoS
            $this->channel->basic_qos(null, 1, null);

            // Set up consumer
            $this->channel->basic_consume(
                $queueName, 
                '',        // consumer tag
                false,     // no local
                false,     // no ack
                false,     // exclusive
                false,     // no wait
                $callback
            );

            // Keep consuming messages using while(true) instead of consuming()
            while (true) {
                try {
                    $this->channel->wait();
                } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                    // Timeout is normal, continue listening
                    continue;
                } catch (\Exception $e) {
                    Log::error('Error while consuming: ' . $e->getMessage());
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::error('RabbitMQ Consume Error: ' . $e->getMessage());
            throw $e;
        }
    }




    public function __destruct()
    {
        try {
            if ($this->channel) {
                $this->channel->close();
            }
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (\Exception $e) {
            Log::error('RabbitMQ Shutdown Error: ' . $e->getMessage());
        }
    }
}