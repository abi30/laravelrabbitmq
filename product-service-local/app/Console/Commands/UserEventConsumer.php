<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class UserEventConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consume:user-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume user creation events from RabbitMQ';

    /**
     * Execute the console command.
     */
    public function handle(RabbitMQService $rabbitMQ)
    {
        $this->info('Starting to consume user events...');

        try {
            $rabbitMQ->consumeMessages('user_created', function($message) {
                $userData = json_decode($message->body, true);
                
                // Log the received user data
                Log::info('Received user creation event', $userData);
                
                try {
                    // Your logic to process user creation event
                    $this->processUserEvent($userData);
                    
                    // Acknowledge the message
                    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                    
                    $this->info("Processed user event for user ID: " . ($userData['user_id'] ?? 'Unknown'));
                } catch (\Exception $e) {
                    // Log processing errors
                    Log::error('Error processing user event', [
                        'error' => $e->getMessage(),
                        'userData' => $userData
                    ]);
                    
                    // Reject the message or handle error scenario
                    $message->delivery_info['channel']->basic_reject(
                        $message->delivery_info['delivery_tag'], 
                        true // requeue the message
                    );
                }
            });
        } catch (\Exception $e) {
            $this->error('Error consuming messages: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Process the user event
     * 
     * @param array $userData
     */
    protected function processUserEvent(array $userData)
    {
        // Implement your user event processing logic
        // For example, create a default product profile
    }
}