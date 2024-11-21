<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQService;

class RabbitMQPublishTest extends Command
{
    protected $signature = 'rabbitmq:publish-test';
    protected $description = 'Publish a test message to RabbitMQ';

    public function handle(RabbitMQService $rabbitmq)
    {
        $message = [
            'event' => 'test_event',
            'data' => [
                'message' => 'Test message at rakib' . date('Y-m-d H:i:s')
            ]
        ];


        $message2 = [
            'event' => 'test.user.created',
            'data' => [
                'id' => 1,
                'name' => 'Test User',
                'email' => 'test@example.com',
                'timestamp' => now()->toIso8601String()
            ]
        ];


        try {
            $rabbitmq->publishMessage('user_events', $message);
            $this->info('Test message published successfully');
        } catch (\Exception $e) {
            $this->error('Failed to publish message: ' . $e->getMessage());
        }
    }
} 