<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;

class VideoChunk extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'video_chunks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'entertainment_id',
        'episode_id',
        'text_content',
        'speaker',
        'embedding',
        'start_time_ms',
        'end_time_ms',
        'start_time_formatted',
        'end_time_formatted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'embedding' => 'array',
        'start_time_ms' => 'integer',
        'end_time_ms' => 'integer',
    ];

    /**
     * Get the entertainment that owns the video chunk.
     */
    public function entertainment()
    {
        return $this->belongsTo(Entertainment::class, 'entertainment_id');
    }

    /**
     * Get the episode that owns the video chunk.
     */
    public function episode()
    {
        return $this->belongsTo(Episode::class, 'episode_id');
    }

    /**
     * Scope a query to only include chunks for a specific entertainment.
     */
    public function scopeForEntertainment($query, $entertainmentId)
    {
        return $query->where('entertainment_id', $entertainmentId);
    }

    /**
     * Scope a query to only include chunks for a specific episode.
     */
    public function scopeForEpisode($query, $episodeId)
    {
        return $query->where('episode_id', $episodeId);
    }
}
