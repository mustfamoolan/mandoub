<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send FCM Push Notification using Google FCM HTTP v1 API.
     */
    public static function sendNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty($fcmToken)) {
            return false;
        }

        $jsonPath = base_path('mandoub-19c4f-firebase-adminsdk-fbsvc-80a9ee2bdd.json');
        if (!file_exists($jsonPath)) {
            Log::error("FCM Error: Service account JSON file not found at {$jsonPath}");
            return false;
        }

        $accessToken = self::getAccessToken($jsonPath);
        if (!$accessToken) {
            Log::error("FCM Error: Failed to generate Google OAuth2 Access Token.");
            return false;
        }

        $serviceAccount = json_decode(file_get_contents($jsonPath), true);
        $projectId = $serviceAccount['project_id'] ?? 'mandoub-19c4f';

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // Convert data values to strings as required by FCM v1
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string)$key] = (string)$value;
        }
        $stringData['title'] = $title;
        $stringData['body'] = $body;

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'merchant_status_channel',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info("FCM v1 Notification sent successfully to token: {$fcmToken}");
                return true;
            } else {
                Log::error("FCM v1 Notification failed: " . $response->body());
                return false;
            }
        } catch (\Throwable $e) {
            Log::error("FCM Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate or fetch cached Google OAuth2 Access Token from Service Account.
     */
    private static function getAccessToken(string $jsonPath): ?string
    {
        return Cache::remember('fcm_google_access_token', 3000, function () use ($jsonPath) {
            $serviceAccount = json_decode(file_get_contents($jsonPath), true);
            $privateKey = $serviceAccount['private_key'] ?? null;
            $clientEmail = $serviceAccount['client_email'] ?? null;

            if (!$privateKey || !$clientEmail) {
                return null;
            }

            $now = time();
            $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = self::base64UrlEncode(json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]));

            $signatureInput = "{$header}.{$claims}";
            $signature = '';

            $signed = openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
            if (!$signed) {
                return null;
            }

            $jwt = "{$signatureInput}." . self::base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            return null;
        });
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
