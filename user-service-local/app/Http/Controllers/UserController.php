<?php
namespace App\Http\Controllers;

use App\Services\RabbitMQService;

class UserController extends Controller
{
    public function createUser(Request $request)
    {
        // Create user logic
        $user = User::create($request->all());

        // Publish user creation event
        $rabbitMQ = new RabbitMQService();
        $rabbitMQ->publishMessage('user_created', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return response()->json($user, 201);
    }
}