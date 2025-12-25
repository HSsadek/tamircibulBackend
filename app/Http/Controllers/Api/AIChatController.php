<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;

class AIChatController extends Controller
{
    public function chat(Request $request)
    {
        try {
            $userMessage = $request->input('message');
            
            if (!$userMessage) {
                return response()->json([
                    'reply' => "❌ Mesaj boş olamaz."
                ], 400);
            }

            $apiKey = env('GEMINI_API_KEY');
            if (!$apiKey) {
                return response()->json([
                    'reply' => "❌ API anahtarı bulunamadı."
                ], 500);
            }

            $client = new Client([
                'timeout' => 30,
                'connect_timeout' => 10
            ]);
            
            $response = $client->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => "Sen TamirciBul platformunun AI asistanısın. Kullanıcılara tamir ve hizmet konularında yardımcı ol. Kısa ve net Türkçe cevaplar ver. Kullanıcı sorusu: " . $userMessage
                                    ]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'maxOutputTokens' => 300,
                        ]
                    ]
                ]
            );

            $result = json_decode($response->getBody(), true);
            $aiReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Yanıt alınamadı.";

            return response()->json([
                'reply' => $aiReply
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Gemini API Error: ' . $e->getMessage());
            
            return response()->json([
                'reply' => "❌ AI servisinde hata: " . $e->getMessage()
            ], 500);
        }
    }
}
