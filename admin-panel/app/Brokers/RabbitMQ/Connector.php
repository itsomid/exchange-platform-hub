<?php

namespace App\Brokers\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class Connector
{
    public function openChannel()
    {
        $connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.host'),
            config('queue.connections.rabbitmq.port'),
            config('queue.connections.rabbitmq.login'),
            config('queue.connections.rabbitmq.password')
        );

        return $connection;
    }
}
