<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQService;

class RabbitMQConsumeTestUser extends Command
{
    protected $signature = 'rabbitmq:consume-test-user';
    protected $description = 'Consume test user events from RabbitMQ';

    public function handle(RabbitMQService $rabbitmq)
    {
        $this->info('Starting test user events consumer...');
        
        $callback = function ($msg) {
            try {
                $data = json_decode($msg->body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \JsonException('Invalid JSON message');
                }
                
                if (!isset($data['event']) || !isset($data['data'])) {
                    throw new \InvalidArgumentException('Missing required message fields');
                }
                
                // Handle different types of test events
                switch ($data['event']) {
                    case 'test.user.created':
                        $this->handleTestUserCreated($data['data']);
                        break;
                    case 'test.user.updated':
                        $this->handleTestUserUpdated($data['data']);
                        break;
                    case 'test.user.deleted':
                        $this->handleTestUserDeleted($data['data']);
                        break;
                    default:
                        throw new \InvalidArgumentException("Unknown test event type: {$data['event']}");
                }
                
                $msg->ack();
                \Log::info('Test message processed successfully', ['event' => $data['event']]);
                
            } catch (\Exception $e) {
                \Log::error('Error processing test message', [
                    'error' => $e->getMessage(),
                    'data' => $data ?? null
                ]);
                
                // Handle different types of errors
                if ($e instanceof \JsonException || $e instanceof \InvalidArgumentException) {
                    // Don't requeue for malformed messages
                    $msg->nack(false);
                } else {
                    // Requeue for other errors that might be temporary
                    $msg->nack(true);
                }
                
                $this->error("Error processing test message: {$e->getMessage()}");
            }
        };

        // Set up the consumer
        try {
            $rabbitmq->consumeMessages('test_user_events', $callback);
        } catch (\Exception $e) {
            \Log::error('RabbitMQ test consumer error', ['error' => $e->getMessage()]);
            $this->error("Test consumer error: {$e->getMessage()}");
            return 1;
        }
    }

    private function handleTestUserCreated(array $data): void
    {
        \Log::info('Processing test user creation', ['data' => $data]);
        $this->info('Test user creation event received: ' . json_encode($data));
        
        // Add your test user creation logic here
        // For example:
        // 1. Validate the data
        // 2. Create a test record
        // 3. Send notification
    }

    private function handleTestUserUpdated(array $data): void
    {
        \Log::info('Processing test user update', ['data' => $data]);
        $this->info('Test user update event received: ' . json_encode($data));
        
        // Add your test user update logic here
        // For example:
        // 1. Validate the data
        // 2. Update test record
        // 3. Send notification
    }

    private function handleTestUserDeleted(array $data): void
    {
        \Log::info('Processing test user deletion', ['data' => $data]);
        $this->info('Test user deletion event received: ' . json_encode($data));
        
        // Add your test user deletion logic here
        // For example:
        // 1. Validate the data
        // 2. Delete test record
        // 3. Send notification
    }
}