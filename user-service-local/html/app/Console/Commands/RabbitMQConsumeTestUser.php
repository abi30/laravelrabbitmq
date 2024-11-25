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
            $this->info('Received message: ' . $msg->body);
            
            try {
                // First JSON decode to get the string
                $rawData = json_decode($msg->body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \JsonException('Invalid JSON message');
                }
                
                // Second JSON decode if the data is still a JSON string
                $data = is_string($rawData) ? json_decode($rawData, true) : $rawData;
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \JsonException('Invalid JSON message structure');
                }
                
                $this->info('Decoded message data: ' . json_encode($data));
                
                // Check for test_event specifically
                if ($data['event'] === 'test_event') {
                    $this->info('Processing test_event');
                    $this->handleTestUserCreated($data['data']);
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                    return;
                }
                
                if (!isset($data['event']) || !isset($data['data'])) {
                    throw new \InvalidArgumentException('Missing required message fields');
                }
                
                switch ($data['event']) {
                    case 'users_events':
                        $this->info('Processing users_events event');
                        $this->handleTestUserCreated($data['data']);
                        break;
                    default:
                        throw new \InvalidArgumentException("Unknown test event type: {$data['event']}");
                }
                
                // Acknowledge the message
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                $this->info('Message acknowledged successfully');
                \Log::info('Test message processed successfully', ['event' => $data['event']]);
                
            } catch (\Exception $e) {
                $this->error('Error processing message: ' . $e->getMessage());
                
                // Handle different types of errors
                if ($e instanceof \JsonException || $e instanceof \InvalidArgumentException) {
                    // Reject the message without requeuing
                    $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
                } else {
                    // Reject and requeue for other errors
                    $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
                }
                
                $this->error("Error processing test message: {$e->getMessage()}");
            }
        };

        try {
            $this->info('Setting up consumer for queue: user_events');
            $rabbitmq->consumeMessages('user_events', $callback);
        } catch (\Exception $e) {
            $this->error("Consumer error: {$e->getMessage()}");
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