<?php

namespace App\Brokers\RabbitMQ\Service;

use App\Brokers\RabbitMQ\Connector;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Queue
{
    private AMQPStreamConnection $connection;

    private AMQPChannel $channel;

    private string $queue;

    private string $data;

    public function __construct()
    {
        $this->connection = (new Connector)->openChannel();
        $this->channel = $this->connection->channel();
    }

    public function queue($queue): Queue
    {
        $this->queue = $queue;

        return $this;
    }

    public function setData($data): Queue
    {
        $this->data = is_array($data)
            ? json_encode($data)
            : $data;

        return $this;
    }

    public function dispatch(): void
    {
        $this->channel->queue_declare($this->queue,
            false,
            true,
            false,
            false);

        $msg = new AMQPMessage($this->data);
        $this->channel->basic_publish($msg, '', $this->queue);
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
