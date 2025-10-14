<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoProcessingWebhookService
{
    /**
     * Send video to processing webhook
     *
     * @param string $type 'entertainment' or 'episode'
     * @param int $id
     * @param string|null $videoUrl
     * @return bool
     */
    public function sendVideoForProcessing(string $type, int $id, ?string $videoUrl = null): bool
    {
        $webhookUrl = config('services.video_processing.webhook_url');
        $bearerToken = config('services.video_processing.bearer_token');

        if (!$webhookUrl || !$bearerToken) {
            Log::warning('VideoProcessingWebhook: Webhook URL or Bearer Token not configured');
            return false;
        }

        if (!$videoUrl) {
            Log::warning('VideoProcessingWebhook: No video URL provided', [
                'type' => $type,
                'id' => $id,
            ]);
            return false;
        }

        $payload = $this->buildPayload($type, $id, $videoUrl);

        Log::info('VideoProcessingWebhook: Sending video for processing', [
            'type' => $type,
            'id' => $id,
            'url' => $webhookUrl,
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $bearerToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('VideoProcessingWebhook: Successfully sent', [
                    'type' => $type,
                    'id' => $id,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error('VideoProcessingWebhook: Failed to send', [
                    'type' => $type,
                    'id' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('VideoProcessingWebhook: Exception occurred', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Build webhook payload
     *
     * @param string $type
     * @param int $id
     * @param string $videoUrl
     * @return array
     */
    protected function buildPayload(string $type, int $id, string $videoUrl): array
    {
        $idField = $type === 'entertainment' ? 'entertainment_id' : 'episode_id';

        return [
            $idField => $id,
            'video_url' => $videoUrl,
        ];
    }

    /**
     * Extract video URL from model
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return string|null
     */
    public function extractVideoUrl($model): ?string
    {
        // Try video_url_input first
        if (!empty($model->video_url_input)) {
            return $model->video_url_input;
        }

        // Try stream content mappings for Episode
        if (method_exists($model, 'EpisodeStreamContentMapping')) {
            $streamContent = $model->EpisodeStreamContentMapping()->first();
            if ($streamContent && !empty($streamContent->url)) {
                return $streamContent->url;
            }
        }

        // Try stream content mappings for Entertainment
        if (method_exists($model, 'entertainmentStreamContentMappings')) {
            $streamContent = $model->entertainmentStreamContentMappings()->first();
            if ($streamContent && !empty($streamContent->url)) {
                return $streamContent->url;
            }
        }

        return null;
    }
}
