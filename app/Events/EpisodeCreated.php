<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Episode\Models\Episode;

class EpisodeCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The episode instance
     *
     * @var Episode
     */
    public $episode;

    /**
     * Create a new event instance.
     */
    public function __construct(Episode $episode)
    {
        $this->episode = $episode;
    }
}
