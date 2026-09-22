<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send SMS using a provider
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function send($phone, $message)
    {
        try {
            // Format phone number (remove leading 0 and add country code if needed)
            // Saudi Arabia country code (966)
            if (substr($phone, 0, 2) === '05') {
                $phone = '966' . substr($phone, 1);
            }

            // استخدام قناة التسجيل الخاصة بـ SMS
            Log::channel('sms')->info('Attempting to send SMS to: ' . $phone . ', message: ' . $message);

            // استخدام خدمة فورجوالي لإرسال الرسائل
            return $this->sendVia4jawaly($phone, $message);
            
        } catch (\Exception $e) {
            Log::channel('sms')->error('SMS sending failed: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Send SMS via 4jawaly
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    private function sendVia4jawaly($phone, $message)
    {
        $client = new Client();
        
        try {
            // استخدام الإعدادات من ملف التكوين
            $baseUrl = config('services.forjawaly.base_url', 'https://api-sms.4jawaly.com/api/v1/');
            $endpoint = $baseUrl . 'account/area/sms/send';
            
            Log::channel('sms')->info("4jawaly request: endpoint={$endpoint}, phone={$phone}");
            
            // قم بتسجيل التكوين للتأكد من أنه صحيح
            Log::channel('sms')->info("4jawaly config: " . json_encode([
                'key' => config('services.forjawaly.key') ? 'set' : 'not set',
                'secret' => config('services.forjawaly.secret') ? 'set' : 'not set',
                'sender' => config('services.forjawaly.sender'),
                'base_url' => $baseUrl
            ]));
            
            $response = $client->post($endpoint, [
                'json' => [
                    'api_key' => config('services.forjawaly.key'),
                    'api_secret' => config('services.forjawaly.secret'),
                    'sender' => config('services.forjawaly.sender'),
                    'message' => $message,
                    'recipients' => [$phone],
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            Log::channel('sms')->info('4jawaly API response: ' . json_encode($result));
            
            if (isset($result['code']) && $result['code'] == 200) {
                return true;
            }
            
            // تسجيل تفاصيل الخطأ في حالة فشل الإرسال
            if ($result) {
                Log::channel('sms')->error('4jawaly API error response: ' . json_encode($result));
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::channel('sms')->error('4jawaly API error: ' . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return false;
        }
    }
} 