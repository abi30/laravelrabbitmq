<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQService;

class PublishTestMessage extends Command
{
    protected $signature = 'rabbitmq:publish-test';
    protected $description = 'Publish a test message to RabbitMQ';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(RabbitMQService $rabbitmq)
    {
        $message = json_encode([
            'event' => 'test_event_nested',
            'data' => ['message' => 'Test message nested at ' . now()]
        ]);

        try {
            // $rabbitmq->publish($message, 'user_events');
            $rabbitmq->publish('test_user_events', [
                'event' => 'test.user.created',
                'data' => [
                    'id' => 1,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'timestamp' => now()->toIso8601String()
                ]
            ]);


            $this->info('Test message published successfully');
        } catch (\Exception $e) {
            $this->error('Failed to publish message: ' . $e->getMessage());
        }
    }
} 