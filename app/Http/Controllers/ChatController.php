<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Handle chat messages from the AI widget
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        try {
            // Validação dos dados recebidos
            $validated = $request->validate([
                'user_id' => 'required',
                'content_id' => 'required',
                'content_type' => 'required|string',
                'message' => 'required|string|max:1000'
            ]);

            // URL do webhook do n8n
            $n8nWebhookUrl = env('N8N_CHAT_WEBHOOK_URL');

            // Verifica se a URL do n8n está configurada
            if (empty($n8nWebhookUrl)) {
                Log::warning('N8N webhook URL not configured');
                return response()->json([
                    'error' => 'Assistente de IA não configurado. Entre em contato com o suporte.',
                    'success' => false
                ], 503);
            }

            // Dados para enviar ao n8n
            $dataToN8n = [
                'user_id' => $validated['user_id'],
                'content_id' => $validated['content_id'],
                'content_type' => $validated['content_type'],
                'message' => $validated['message'],
                'timestamp' => now()->toIso8601String()
            ];

            // Chamada para o n8n usando HTTP Client do Laravel
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($n8nWebhookUrl, $dataToN8n);

            // Verifica se a requisição foi bem-sucedida
            if ($response->successful()) {
                $n8nResponse = $response->json();

                // Valida se a resposta contém o campo 'message'
                if (!isset($n8nResponse['message']) || empty($n8nResponse['message'])) {
                    Log::error('N8N Invalid Response', ['status' => $response->status()]);

                    return response()->json([
                        'error' => 'Resposta inválida do assistente de IA.',
                        'success' => false
                    ], 500);
                }

                return response()->json([
                    'message' => $n8nResponse['message'],
                    'success' => true
                ]);
            } else {
                Log::error('N8N Webhook Error', ['status' => $response->status()]);

                return response()->json([
                    'error' => 'Desculpe, não consegui processar sua mensagem no momento. Tente novamente.',
                    'success' => false
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Dados inválidos enviados.',
                'errors' => $e->errors(),
                'success' => false
            ], 422);

        } catch (\Exception $e) {
            Log::error('Chat AI Error', ['message' => $e->getMessage()]);

            return response()->json([
                'error' => 'Erro ao processar mensagem. Tente novamente.',
                'success' => false
            ], 500);
        }
    }
}
