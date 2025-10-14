<?php

namespace App\Listeners;

use App\Services\VideoProcessingWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendEpisodeToProcessing implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var VideoProcessingWebhookService
     */
    protected $webhookService;

    /**
     * Create the event listener.
     */
    public function __construct(VideoProcessingWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $episode = $event->episode ?? $event->model ?? null;

        if (!$episode) {
            Log::warning('SendEpisodeToProcessing: No episode found in event');
            return;
        }

        // Extract video URL
        $videoUrl = $this->webhookService->extractVideoUrl($episode);

        if (!$videoUrl) {
            Log::info('SendEpisodeToProcessing: No video URL found, skipping', [
                'episode_id' => $episode->id,
            ]);
            return;
        }

        // Send to processing webhook
        $this->webhookService->sendVideoForProcessing(
            'episode',
            $episode->id,
            $videoUrl
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('SendEpisodeToProcessing: Listener failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
