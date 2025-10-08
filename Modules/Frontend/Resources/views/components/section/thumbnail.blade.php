
<div class="detail-page-banner">
        <div class="video-player">



            @if($type=='Local')

            <video id="videoPlayer" class="video-js vjs-default-skin" controls  width="560"
            height="315"
            autoplay="{{ auth()->check() ? 'true' : 'false' }}"
            muted
            data-setup="{}"
              poster="{{$thumbnail_image}}"
              data-continue-watch="{{ isset($continue_watch) && $continue_watch ? 'true' : 'false' }}"
                data-setup='{"autoplay": {{ auth()->check() ? 'true' : 'false' }}, "muted": true}'>
            <source src="{{ $data }}" type="video/mp4" id="videoSource"
              {{-- @if($subtitle_info && $subtitle_info->isNotEmpty())
              @foreach($subtitle_info->toArray(request()) as $subtitle)
                <track
                    src="{{ $subtitle['subtitle_file'] }}"
                    kind="subtitles"
                    srclang="{{ $subtitle['language_code'] }}"
                    label="{{ $subtitle['language'] }}"
                    @if($subtitle['is_default'] == 1) default @endif
                >
              @endforeach
            @endif --}}
            @if(isset($contentType) && isset($contentId))
                data-contentType ="{{ $contentType }}"
                data-contentId ="{{ $contentId }}"
            @endif
            >
          </video>


            @else
            <!-- Video.js Player -->
            <video
                id="videoPlayer"
                class="video-js vjs-default-skin"
                controls
                width="560"
                height="315"
                autoplay="{{ auth()->check() ? 'true' : 'false' }}"
                muted
                data-watch-time="{{$watched_time??0}}"
                data-movie-access="{{$dataAccess??''}}"
                data-encrypted="{{ $data }}"
                 poster="{{$thumbnail_image}}"
                data-continue-watch="{{ isset($continue_watch) && $continue_watch ? 'true' : 'false' }}"
                data-setup='{"autoplay": {{ auth()->check() ? 'true' : 'false' }}, "muted": true}'
                {{-- @if($subtitle_info && $subtitle_info->isNotEmpty())
                  @foreach($subtitle_info->toArray(request()) as $subtitle)
                  <track
                      src="{{ $subtitle['subtitle_file'] }}"
                      kind="subtitles"
                      srclang="{{ $subtitle['language_code'] }}"
                      label="{{ $subtitle['language'] }}"
                      @if($subtitle['is_default'] == 1) default @endif
                  >
                @endforeach
            @endif --}}
                @if(isset($contentType) && isset($contentId))
                    data-contentType ="{{ $contentType }}"
                    data-contentId ="{{ $contentId }}"
                @endif
                >
            </video>    
            @endif

        </div>
</div>





<!-- Include the custom JS -->
<!-- <script src="https://cdn.jsdelivr.net/npm/videojs-srt@0.1.0/srt.min.js"></script> -->
<script src="{{ asset('js/videoplayer.min.js') }}"></script>
<script>
    var isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
    var loginUrl = "{{ route('login') }}";  // Update with your actual login route
</script>



<style>
  .vjs-texttrack-settings {
    display: none !important;
}
</style>
