<?php

namespace Modules\Entertainment\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\Entertainment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Watchlist Controller
 *
 * Permite que administradores gerenciem a watchlist de qualquer usuário
 * Requer token de admin para autenticação
 */
class AdminWatchlistController extends Controller
{
    /**
     * Adicionar item à watchlist de um usuário específico
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToWatchlist(Request $request)
    {
        // Validação
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'entertainment_id' => 'required|integer|exists:entertainments,id',
            'type' => 'required|string|in:entertainment,episode,video',
            'profile_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->input('user_id');
            $entertainmentId = $request->input('entertainment_id');
            $type = $request->input('type');

            // Verificar se o usuário existe
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Verificar se o entertainment existe
            $entertainment = Entertainment::find($entertainmentId);
            if (!$entertainment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Entertainment not found'
                ], 404);
            }

            // Definir profile_id
            $profileId = $request->input('profile_id');
            if (!$profileId) {
                $profileId = getCurrentProfile($userId, $request);
            }

            // Mapear tipos: entertainment -> movie, episode -> tvshow, video -> video
            $dbType = match($type) {
                'entertainment' => 'movie',
                'episode' => 'tvshow',
                'video' => 'video',
                default => null
            };

            // Verificar se o item já existe na watchlist
            $existingEntry = Watchlist::where('entertainment_id', $entertainmentId)
                ->where('user_id', $userId)
                ->where('profile_id', $profileId)
                ->where('type', $dbType)
                ->first();

            if ($existingEntry) {
                return response()->json([
                    'status' => false,
                    'message' => 'Item already exists in watchlist',
                    'data' => [
                        'watchlist_id' => $existingEntry->id,
                        'user_id' => $userId,
                        'entertainment_id' => $entertainmentId,
                        'type' => $type
                    ]
                ], 409);
            }

            // Criar novo item na watchlist
            $watchlistEntry = Watchlist::create([
                'user_id' => $userId,
                'entertainment_id' => $entertainmentId,
                'type' => $dbType,
                'profile_id' => $profileId
            ]);

            // Limpar cache
            $cacheKey = $entertainment->type === 'movie'
                ? 'movie_' . $entertainmentId . '_' . $profileId
                : 'tvshow_' . $entertainmentId . '_' . $profileId;
            Cache::forget($cacheKey);

            Log::info('Admin added item to watchlist', [
                'user_id' => $userId,
                'entertainment_id' => $entertainmentId,
                'admin_user' => auth()->user() ? auth()->user()->email : 'system'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Item added to watchlist successfully',
                'data' => [
                    'watchlist_id' => $watchlistEntry->id,
                    'user_id' => $userId,
                    'entertainment_id' => $entertainmentId,
                    'type' => $type
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Admin watchlist add error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error adding to watchlist: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover item da watchlist de um usuário específico
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFromWatchlist(Request $request)
    {
        // Validação
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'entertainment_id' => 'required|integer',
            'type' => 'required|string|in:entertainment,episode,video',
            'profile_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->input('user_id');
            $entertainmentId = $request->input('entertainment_id');
            $type = $request->input('type');

            // Mapear tipos: entertainment -> movie, episode -> tvshow, video -> video
            $dbType = match($type) {
                'entertainment' => 'movie',
                'episode' => 'tvshow',
                'video' => 'video',
                default => null
            };

            // Definir profile_id
            $profileId = $request->input('profile_id');
            if (!$profileId) {
                $profileId = getCurrentProfile($userId, $request);
            }

            // Verificar se existe
            $watchlistExists = Watchlist::where('entertainment_id', $entertainmentId)
                ->where('user_id', $userId)
                ->where('profile_id', $profileId)
                ->where('type', $dbType)
                ->exists();

            if (!$watchlistExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Item not found in watchlist'
                ], 404);
            }

            // Deletar
            Watchlist::where('entertainment_id', $entertainmentId)
                ->where('user_id', $userId)
                ->where('profile_id', $profileId)
                ->where('type', $dbType)
                ->forceDelete();

            // Limpar cache
            Cache::flush();

            Log::info('Admin removed item from watchlist', [
                'user_id' => $userId,
                'entertainment_id' => $entertainmentId,
                'admin_user' => auth()->user() ? auth()->user()->email : 'system'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Item removed from watchlist successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Admin watchlist remove error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error removing from watchlist: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar watchlist de um usuário específico
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserWatchlist(Request $request)
    {
        // Validação
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'profile_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->input('user_id');
            $perPage = $request->input('per_page', 10);

            // Definir profile_id
            $profileId = $request->input('profile_id');
            if (!$profileId) {
                $profileId = getCurrentProfile($userId, $request);
            }

            // Buscar watchlist
            $watchList = Watchlist::where('user_id', $userId)
                ->where('profile_id', $profileId)
                ->whereHas('entertainment', function ($query) {
                    $query->where('status', 1);
                })
                ->with(['entertainment'])
                ->orderBy('updated_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Watchlist retrieved successfully',
                'data' => $watchList->items(),
                'pagination' => [
                    'total' => $watchList->total(),
                    'per_page' => $watchList->perPage(),
                    'current_page' => $watchList->currentPage(),
                    'last_page' => $watchList->lastPage(),
                    'from' => $watchList->firstItem(),
                    'to' => $watchList->lastItem()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Admin get watchlist error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving watchlist: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpar toda a watchlist de um usuário
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearUserWatchlist(Request $request)
    {
        // Validação
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'profile_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->input('user_id');

            // Definir profile_id
            $profileId = $request->input('profile_id');
            if (!$profileId) {
                $profileId = getCurrentProfile($userId, $request);
            }

            // Contar itens antes de deletar
            $count = Watchlist::where('user_id', $userId)
                ->where('profile_id', $profileId)
                ->count();

            if ($count === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Watchlist is already empty'
                ], 404);
            }

            // Deletar todos os itens
            Watchlist::where('user_id', $userId)
                ->where('profile_id', $profileId)
                ->forceDelete();

            // Limpar cache
            Cache::flush();

            Log::info('Admin cleared user watchlist', [
                'user_id' => $userId,
                'items_deleted' => $count,
                'admin_user' => auth()->user() ? auth()->user()->email : 'system'
            ]);

            return response()->json([
                'status' => true,
                'message' => "Watchlist cleared successfully. {$count} item(s) removed."
            ], 200);

        } catch (\Exception $e) {
            Log::error('Admin clear watchlist error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error clearing watchlist: ' . $e->getMessage()
            ], 500);
        }
    }
}
