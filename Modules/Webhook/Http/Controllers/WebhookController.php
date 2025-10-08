<?php

namespace Modules\Webhook\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Subscriptions\Services\SubscriptionService;

class WebhookController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function handle(Request $request)
    {
        // Validação do token
        $token = $request->header('X-Webhook-Token');
        if ($token !== env('WEBHOOK_SECRET')) {
            Log::warning('Webhook token inválido', ['token' => $token]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // Validação dos dados recebidos
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'email' => 'required|email',
            'cpf' => 'required|string|min:11',
            'plan_id' => 'required|integer|exists:plan,id',
        ]);

        if ($validator->fails()) {
            Log::error('Validação do webhook falhou', ['errors' => $validator->errors()]);
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Verificar se usuário já existe
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            $nomeSplit = explode(' ', $data['nome'], 2);
            $firstName = $nomeSplit[0] ?? '';
            $lastName = $nomeSplit[1] ?? '';

            // Criar usuário com senha = CPF
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $data['email'],
                'password' => Hash::make($data['cpf']),
                'status' => 1
            ]);
        }

        // Criar assinatura usando SubscriptionService
        try {
            $this->subscriptionService->createPayment([
                'user_id' => $user->id,
                'plan_id' => $data['plan_id'],
                'payment_date' => now(),
                'payment_status' => 'paid',
                'payment_type' => 'webhook',
                'transaction_id' => 'webhook_' . uniqid(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar assinatura via webhook', [
                'user_id' => $user->id,
                'plan_id' => $data['plan_id'],
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Failed to create subscription'], 500);
        }

        Log::info('Webhook processado com sucesso', ['user_id' => $user->id, 'plan_id' => $data['plan_id']]);

        return response()->json([
            'status' => 'ok',
            'user_id' => $user->id,
            'plan_id' => $data['plan_id']
        ]);
    }
}