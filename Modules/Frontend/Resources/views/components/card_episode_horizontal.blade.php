<div class="season-image flex-shrink-0">
    <div class="position-relative">
        <img src="{{ isset($episode->poster_url) ? setBaseUrlWithFileName($episode->poster_url) : (isset($episode->poster_tv_url) ? setBaseUrlWithFileName($episode->poster_tv_url) : asset('img/default-thumbnail.jpg')) }}"
             alt="{{ $episode->name }}"
             class="object-fit-cover rounded w-100" style="height: 180px;">

    <button class="season-watch-btn"
            id="seasonWatchBtn_{{ $episode->id }}"
            data-entertainment-id="{{ $show->id }}" 
            data-entertainment-type="tvshow" 
            data-video-url="{{ $episode->encrypted_url ?? '' }}" 
            data-movie-access="{{ $episode->movie_access ?? 'paid' }}" 
            data-plan-id="{{ $episode->plan_id ?? '' }}" 
            data-user-id="{{ auth()->id() }}" 
            data-profile-id="{{ getRequestedProfileId() }}" 
            data-episode-id="{{ $episode->id }}" 
            data-first-episode-id="{{ $show->episodes->first()->id ?? '' }}" 
            data-quality-options="{{ json_encode($episode->quality ?? []) }}" 
            data-subtitle-info="{{ json_encode($episode->subtitles ?? []) }}">
        <span class="d-flex align-items-center justify-content-center gap-2">
            <span><i class="ph-fill ph-play"></i></span>
            Assistir agora
        </span>
    </button>
</div>