<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RabbitMQService
{
    /**
     * Publish JSON event to RabbitMQ Dosen Cloud.
     */
    public function publishEvent(string $routingKey, array $payload): bool
    {
        $host = env('RABBITMQ_HOST', 'iae-sso.virtualfri.id');
        $port = (int) env('RABBITMQ_PORT', 5672);
        $user = env('RABBITMQ_USER', 'guest');
        $password = env('RABBITMQ_PASSWORD', 'guest');
        $exchange = 'iae.central.exchange';

        try {
            // 1. Establish connection to RabbitMQ
            $connection = new AMQPStreamConnection(
                $host,
                $port,
                $user,
                $password,
                '/',
                false,
                'AMQPLAIN',
                null,
                'en_US',
                5.0,
                5.0
            );
            $channel = $connection->channel();

            // 2. Declare exchange as topic (dosen't central exchange)
            $channel->exchange_declare($exchange, 'topic', false, true, false);

            // 3. Construct JSON message
            $msgBody = json_encode($payload);
            $msg = new AMQPMessage($msgBody, [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);

            // 4. Publish message with routing key
            $channel->basic_publish($msg, $exchange, $routingKey);

            // 5. Clean up
            $channel->close();
            $connection->close();

            Log::info("RabbitMQ: Event [{$routingKey}] published successfully via AMQP.");
            return true;

        } catch (\Exception $e) {
            Log::warning("RabbitMQ AMQP Publish Failed: " . $e->getMessage() . ". Attempting HTTP fallback...");
            
            // Fallback: Publish via SSO HTTP API endpoint
            return $this->publishEventViaHttp($routingKey, $payload);
        }
    }

    /**
     * Fallback HTTP API Publish to iae.central.exchange.
     */
    private function publishEventViaHttp(string $routingKey, array $payload): bool
    {
        Log::info("RabbitMQ Fallback: Sending event via SSO Cloud HTTP API.");
        
        try {
            $apiKey = env('SSO_API_KEY', 'KEY-MHS-314');
            
            // Get SSO Token
            $tokenResponse = Http::timeout(10)->retry(1, 500)->post('https://iae-sso.virtualfri.id/api/v1/auth/token', [
                'api_key' => $apiKey
            ]);
            
            if ($tokenResponse->failed()) {
                Log::error("RabbitMQ Fallback SSO token retrieval failed: " . $tokenResponse->body());
                return false;
            }

            $token = $tokenResponse->json('token') ?? $tokenResponse->json('data.token');

            if (!$token) {
                Log::error("RabbitMQ Fallback: Token not found in response.");
                return false;
            }

            // Publish message via REST API
            $response = Http::timeout(15)->retry(1, 500)->withToken($token)->post('https://iae-sso.virtualfri.id/api/v1/messages/publish', [
                'exchange' => 'iae.central.exchange',
                'routing_key' => $routingKey,
                'payload' => $payload
            ]);

            if ($response->successful()) {
                Log::info("RabbitMQ: Event [{$routingKey}] published successfully via HTTP API.");
                return true;
            }

            Log::error("RabbitMQ Fallback HTTP publish request failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("RabbitMQ Fallback HTTP Exception: " . $e->getMessage());
            return false;
        }
    }
}
