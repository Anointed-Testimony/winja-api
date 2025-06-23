<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected $secretKey;
    protected $publicKey;
    protected $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->publicKey = config('services.paystack.public_key');
    }

    public function initializeTransaction(array $data)
    {
        try {
            \Log::info('Initializing Paystack transaction:', [
                'data' => $data,
                'secret_key' => substr($this->secretKey, 0, 10) . '...',
            ]);

            $response = Http::withToken($this->secretKey)
                ->post($this->baseUrl . '/transaction/initialize', [
                    'amount' => $data['amount'] * 100, // Convert to kobo
                    'email' => $data['email'],
                    'currency' => 'NGN',
                    'reference' => $data['reference'],
                    'callback_url' => $data['callback_url'],
                    'metadata' => [
                        'type' => $data['type'],
                        'user_id' => $data['user_id'],
                    ],
                ]);

            \Log::info('Paystack API response:', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('Paystack initialization failed:', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to initialize payment: ' . $response->body());
        } catch (\Exception $e) {
            \Log::error('Paystack service error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function verifyTransaction($reference)
    {
        try {
            \Log::info('Verifying Paystack transaction:', ['reference' => $reference]);
            
            $response = Http::withToken($this->secretKey)
                ->get($this->baseUrl . '/transaction/verify/' . $reference);

            \Log::info('Paystack verification response:', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Paystack verification failed: ' . $response->body());
            throw new \Exception('Failed to verify payment');
        } catch (\Exception $e) {
            Log::error('Paystack service error: ' . $e->getMessage());
            throw $e;
        }
    }
} 