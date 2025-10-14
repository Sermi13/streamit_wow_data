<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Entertainment\Models\Entertainment;

class EntertainmentCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The entertainment instance
     *
     * @var Entertainment
     */
    public $entertainment;

    /**
     * Create a new event instance.
     */
    public function __construct(Entertainment $entertainment)
    {
        $this->entertainment = $entertainment;
    }
}
