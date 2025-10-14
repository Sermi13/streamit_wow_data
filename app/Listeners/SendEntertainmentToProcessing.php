<?php

namespace App\Listeners;

use App\Services\VideoProcessingWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendEntertainmentToProcessing implements ShouldQueue
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
        $entertainment = $event->entertainment ?? $event->model ?? null;

        if (!$entertainment) {
            Log::warning('SendEntertainmentToProcessing: No entertainment found in event');
            return;
        }

        // Extract video URL
        $videoUrl = $this->webhookService->extractVideoUrl($entertainment);

        if (!$videoUrl) {
            Log::info('SendEntertainmentToProcessing: No video URL found, skipping', [
                'entertainment_id' => $entertainment->id,
            ]);
            return;
        }

        // Send to processing webhook
        $this->webhookService->sendVideoForProcessing(
            'entertainment',
            $entertainment->id,
            $videoUrl
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('SendEntertainmentToProcessing: Listener failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
