<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Exception;
use Google\Client as GoogleClient;

class SendPushNotificationsController extends Controller
{
    private $firebaseProjectId = 'trumac-53d41';

    /**
     * Send Firebase push notification to a user
     *
     * @param int $userId - User ID to send notification to
     * @param string $title - Notification title
     * @param string $body - Notification body
     * @param string $type - Notification type
     * @param array|null $data - Additional data payload
     * @param int|null $companyId - Company ID
     * @return array
     */
    public function sendPushNotification($userId, $title, $body, $type = 'general', $data = null, $companyId = null)
    {
        try {
            // Get user's FCM token
            $user = User::find($userId);

            if (!$user || !$user->fcm_token) {
                return [
                    'success' => false,
                    'message' => 'FCM token not found for user',
                ];
            }

            // Firebase credentials
            $credentialsFilePath = public_path('trumac-53d41-firebase-adminsdk-fbsvc-dc35bdc95e.json');

            if (!file_exists($credentialsFilePath)) {
                \Log::warning("Firebase credentials file not found");
                return [
                    'success' => false,
                    'message' => 'Firebase credentials file not found',
                ];
            }

            // Authenticate with Google Client
            $client = new GoogleClient();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken();

            if (!isset($token['access_token'])) {
                throw new Exception('Failed to fetch Firebase access token');
            }

            $accessToken = $token['access_token'];
            $fcmToken = $user->fcm_token;

            // Build message payload
            $message = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_merge([
                        'notification_type' => (string) $type,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ], $data ? array_map('strval', $data) : []),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'high_importance_channel',
                        ],
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'alert' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'sound' => 'default',
                                'badge' => 1,
                                'content-available' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            // Send via cURL to FCM HTTP v1 API
            $headers = [
                "Authorization: Bearer $accessToken",
                'Content-Type: application/json',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$this->firebaseProjectId}/messages:send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                \Log::error("FCM Curl Error: {$err}");
                return ['success' => false, 'message' => 'Curl Error: ' . $err];
            }

            $responseData = json_decode($response, true);

            return [
                'success' => true,
                'message' => 'Push notification sent',
                'fcm_response' => $responseData,
            ];

        } catch (\Exception $e) {
            \Log::error('FCM Push Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ];
        }
    }
}
