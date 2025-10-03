<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;

class AIChatController extends Controller
{
    public function chat(Request $request)
    {
        $userMessage = $request->input('message');

        $client = new Client();
        try {
            $response = $client->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'X-goog-api-key' => env('GEMINI_API_KEY'), // API key header olarak gönderiliyor
                    ],
                    'json' => [
                        'contents' => [
                            ['parts' => [['text' => $userMessage]]]
                        ]
                    ]
                ]
            );

            $result = json_decode($response->getBody(), true);
            $aiReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? "❌ Yanıt alınamadı.";

            return response()->json([
                'reply' => $aiReply
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'reply' => "❌ AI servisine bağlanırken hata oluştu.",
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
