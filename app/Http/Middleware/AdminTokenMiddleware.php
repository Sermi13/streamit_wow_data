<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para validar tokens de administrador
 *
 * Este middleware verifica se o token fornecido é um token de admin válido.
 * Tokens admin são definidos no arquivo .env
 */
class AdminTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Obter token do header Authorization
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Admin token required'
            ], 401);
        }

        // Obter tokens admin permitidos do .env
        // Suporta múltiplos tokens separados por vírgula
        $adminTokens = config('app.admin_api_tokens');

        if (!$adminTokens) {
            Log::error('ADMIN_API_TOKENS not configured in .env');
            return response()->json([
                'status' => false,
                'message' => 'Admin API not configured'
            ], 500);
        }

        // Converter para array se for string
        if (is_string($adminTokens)) {
            $adminTokens = array_map('trim', explode(',', $adminTokens));
        }

        // Verificar se o token está na lista de tokens admin
        if (!in_array($token, $adminTokens, true)) {
            Log::warning('Invalid admin token attempt', [
                'ip' => $request->ip(),
                'token' => substr($token, 0, 10) . '...'
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Invalid admin token'
            ], 403);
        }

        // Token válido - permitir acesso
        Log::info('Admin API access', [
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        return $next($request);
    }
}
