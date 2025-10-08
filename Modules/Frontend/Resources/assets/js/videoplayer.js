import videojs from 'video.js'
import 'videojs-contrib-ads'
import 'video.js/dist/video-js.css'
import 'videojs-youtube'

const Button = videojs.getComponent('Button');
let currentEpisodeId = null;
let currentEntertainmentId = null;
let currentEntertainmentType = null;

// Create a custom button class

document.addEventListener('DOMContentLoaded', function () {
  const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

  const player = videojs('videoPlayer', {
    techOrder: ['vimeo', 'youtube', 'html5', 'hls', 'embed'],
    autoplay: false,
    controls: true,
    controlBar: {
      subsCapsButton: {
        textTrackSettings: false // Disable "captions settings"
      }
    }
  });

  const contentType = document.querySelector('#videoPlayer').getAttribute('data-content-type');
  const access = document.querySelector('#videoPlayer').getAttribute('data-movie-access');
  const continueWatch = document.querySelector('#videoPlayer').getAttribute('data-continue-watch') === 'true';
  const videotype = document.querySelector('#videoPlayer').getAttribute('data-contentType');
  const contentId = document.querySelector('#videoPlayer').getAttribute('data-contentId');
  if (contentType != 'livetv') {
    player.ready(async function () {
      if (access === 'pay-per-view' && videotype === 'movie' && contentId) {
        // Check if the movie is purchased
        try {
          const response = await fetch(`${baseUrl}/api/check-movie-purchase?movie_id=${contentId}`);
          const data = await response.json();
          if (data.is_purchased) {
            const skipButton = new SkipTrainerButton(player, {
              baseUrl: baseUrl // Pass baseUrl to the button
            });
            player.controlBar.addChild(skipButton, {}, player.controlBar.children().length - 1);
          }
        } catch (error) {
          console.error('Error checking movie purchase:', error);
        }
      } else if (access != 'pay-per-view' && !continueWatch) {
        const skipButton = new SkipTrainerButton(player, {
          baseUrl: baseUrl // Pass baseUrl to the button
        });
        player.controlBar.addChild(skipButton, {}, player.controlBar.children().length - 1);
      }
      const nextButton = new NextEpisodeButton(player);
      player.controlBar.addChild(nextButton, {}, player.controlBar.children().length - 1);
    });
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  let isVideoLoaded = false
  let currentVideoUrl = ''
  let isWatchHistorySaved = false

  async function CheckDeviceType() {
    try {
      const response = await fetch(`${baseUrl}/check-device-type`)
      const data = await response.json()
      return data.isDeviceSupported
    } catch (error) {
      return false
    }
  }

  async function CheckSubscription(planId) {
    try {
      const response = await fetch(`${baseUrl}/check-subscription/${planId}`)
      const data = await response.json()
      return data.isActive
    } catch (error) {
      return false
    }
  }



  async function checkAuthenticationAndDeviceSupport() {
    const isDeviceSupported = await CheckDeviceType()
    return isAuthenticated && isDeviceSupported
  }

  async function loadVideoIfAuthenticated() {


    const accessType = document.querySelector('#videoPlayer').getAttribute('data-movie-access')

    const plan_id = document.querySelector('#videoPlayer').getAttribute('data-plan-id')

    // if (!isAuthenticated) {
    //   return // Exit if not authenticated
    // }

    let canPlay = true
    if (accessType === 'paid') {
      canPlay = await checkAuthenticationAndDeviceSupport()

    }

    if (plan_id && accessType === 'paid') {

      canPlay = await CheckSubscription(plan_id)
    }

    if (canPlay && !isVideoLoaded) {
      const encryptedData = document.querySelector('#videoPlayer').getAttribute('data-encrypted')
      const watchTime = document.querySelector('#videoPlayer').getAttribute('data-watch-time')


      // Hide the big play button if encryptedData is empty
      const bigPlayButton = player.el().querySelector('.vjs-big-play-button');
      if (!encryptedData && bigPlayButton) {
        bigPlayButton.style.display = 'none';
        // Optionally, you could show a message to the user here
        // e.g., player.overlay({ content: 'Video not available.' });
        return; // Stop here if no video data
      }

      let watchTimeInSeconds = 0;
      if (watchTime) {
        const [hours, minutes, seconds] = watchTime.split(':').map(Number);
        watchTimeInSeconds = (hours * 3600) + (minutes * 60) + seconds;
      }
      if (encryptedData) {
        fetch(`${baseUrl}/video/stream/${encodeURIComponent(encryptedData)}`)
          .then((response) => response.json())
          .then((data) => {
            const qualityOptions = data.qualityOptions
            setVideoSource(player, data.platform, data.videoId, data.url, data.mimeType, qualityOptions)
            player.load()
            player.one('loadedmetadata', async function () {
              player.currentTime(watchTimeInSeconds)
              player.muted(true) // Mute the player for autoplay
              try {
                await player.play()
              } catch (error) {
                console.error('Error trying to autoplay:', error)
              }
            })
            isVideoLoaded = true
          })
          .catch((error) => console.error('Error fetching video:', error))
      }
    }
    else {
      $('#DeviceSupport').modal('show')
    }
  }

  loadVideoIfAuthenticated()

  const playButton = document.querySelector('.vjs-big-play-button')
  if (playButton) {
    playButton.addEventListener('click', async function (e) {
      if (!isAuthenticated) {
        e.preventDefault() // Prevent play
        window.location.href = loginUrl // Redirect to login
      } else {
        const canPlay = await checkAuthenticationAndDeviceSupport()
        if (!canPlay) {
          e.preventDefault() // Prevent play if conditions are not met
        }
      }
    })
  }

  const handleWatchButtonClick = async (button, isSeasonWatch = false) => {

    const accessType = button.getAttribute('data-movie-access')
    const qualityOptionsData = button.getAttribute('data-quality-options')
    const qualityOptions = Object.entries(JSON.parse(qualityOptionsData)).map(([label, url]) => ({ label, url }))
    const videoUrl = button.getAttribute('data-video-url')
    currentVideoUrl = videoUrl

    currentEpisodeId = button.getAttribute('data-episode-id');
    currentEntertainmentId = button.getAttribute('data-entertainment-id');
    currentEntertainmentType = button.getAttribute('data-entertainment-type');


    // Get subtitle data from the button and parse it
    const subtitleInfo = JSON.parse(button.getAttribute('data-subtitle-info') || '[]')

    // Hide subtitle button if no subtitles are available
    const subtitleButton = player.controlBar.subsCapsButton
    if (subtitleButton) {
      if (subtitleInfo && subtitleInfo.length > 0) {
        subtitleButton.show()
      } else {
        subtitleButton.hide()
      }
    }

    window.scrollTo({ top: 0, behavior: 'smooth' })

    fetch(`${baseUrl}/api/continuewatch-list`)
      .then((response) => response.json())
      .then(async (data) => {
      
        const entertainmentId = button.getAttribute('data-entertainment-id')
        const entertainmentType = button.getAttribute('data-entertainment-type')
        const matchingVideo = data.data.find((item) => item.entertainment_id === parseInt(entertainmentId) && item.entertainment_type === entertainmentType)
        let lastWatchedTime = 0
        if (matchingVideo && matchingVideo.total_watched_time) {
          lastWatchedTime = timeStringToSeconds(matchingVideo.total_watched_time)
        }

        if (accessType === 'paid') {
          const canPlay = await checkAuthenticationAndDeviceSupport()
          if (!canPlay) {
            player.pause()
            $('#DeviceSupport').modal('show') // Show device support modal if not supported
            return // Stop further execution
          }
        }

        if (accessType === 'free' || accessType === 'pay-per-view') {
          playVideo(videoUrl, qualityOptions, lastWatchedTime, subtitleInfo)
        } else {
          handleSubscription(button, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo)
        }
      })
      .catch((error) => console.error('Error fetching continue watch:', error))

    isWatchHistorySaved = false // Reset flag
  }

  const watchNowButton = document.getElementById('watchNowButton')
  const seasonWatchBtn = document.getElementById('seasonWatchBtn')

  const subtitles = JSON.parse(watchNowButton.getAttribute('data-subtitle-info'));

  if (watchNowButton) {
    watchNowButton.addEventListener('click', async function (e) {
      e.preventDefault()
      if (!isAuthenticated) {
        window.location.href = loginUrl // Redirect to login if not authenticated
        return // Stop further execution
      }
      // const entertainmentType = watchNowButton.getAttribute('data-entertainment-type');
      // if(entertainmentType == 'video'){
      const skipBtn = document.querySelector('.vjs-skip-trainer-button.vjs-control');
      if (skipBtn) {
        skipBtn.style.display = 'none';
      }
      // }
      await handleWatchButtonClick(watchNowButton)
    })
  }

  document.addEventListener('click', async function (e) {
    const button = e.target.closest('.season-watch-btn');

    if (button) {
      e.preventDefault();

      if (!isAuthenticated) {
        window.location.href = loginUrl;
        return;
      }

      // Hide skip button using CSS class selector
      const skipBtn = document.querySelector('.vjs-skip-trainer-button.vjs-control');
      if (skipBtn) {
        skipBtn.style.display = 'none';
      }

      await handleWatchButtonClick(button);
    }
  });

  function playVideo(videoUrl, qualityOptions, lastWatchedTime, subtitleInfo = []) {
    const datatype = watchNowButton?.getAttribute('data-type') || seasonWatchBtn?.getAttribute('data-type')


    if (datatype === 'Local') {
      const videoSource = document.querySelectorAll('#videoSource');

      videoSource.src = videoUrl;

      const videoPlayer = videojs('videoPlayer');
      videoPlayer.src({ type: 'video/mp4', src: videoUrl });

      // Add subtitle tracks if available
      if (subtitleInfo && subtitleInfo.length > 0) {
        // Remove any existing subtitle tracks
        const existingTracks = videoPlayer.textTracks();
        for (let i = existingTracks.length - 1; i >= 0; i--) {
          videoPlayer.removeRemoteTextTrack(existingTracks[i]);
        }

        // Add new subtitle tracks
        subtitleInfo.forEach(subtitle => {
          videoPlayer.addRemoteTextTrack({
            kind: 'subtitles',
            src: subtitle.subtitle_file,
            srclang: subtitle.language_code,
            label: subtitle.language,
            default: subtitle.is_default === 1
          }, false);
        });
      }

      videoPlayer.load();
      videoPlayer.play();
      const existingQualitySelector = document.querySelector('.vjs-quality-selector')
      if (!existingQualitySelector && qualityOptions.length > 0) {
        const qualitySelector = document.createElement('div')
        qualitySelector.classList.add('vjs-quality-selector')

        const qualityDropdown = document.createElement('select')

        qualityOptions.forEach((option) => {
          const qualityOption = document.createElement('option')

          qualityOption.value = option.url.value // Use the URL for the quality option
          qualityOption.innerText = option.label // Display the label (e.g., "360p", "720p")
          qualityOption.setAttribute('data-type', option.url.type);
          qualityDropdown.appendChild(qualityOption)
        })

        qualityDropdown.addEventListener('change', function () {
          const selectedQuality = this.value;
          var videoId = null;
          var platform = null;
          var url = null;

          const dataType = document.querySelector('.vjs-quality-selector select')
            ?.selectedOptions[0]?.getAttribute('data-type');

          const filteredOptions = qualityOptions.filter(option => option.url.type === 'Local' && dataType === option.url.type);
          // Check if a quality option was found and process it
          if (filteredOptions.length > 0) {
            const option = filteredOptions[0]; // Assuming you just want the first match
            const videoSource = document.querySelectorAll('#videoSource'); // Use querySelector for a single element

            if (videoSource) {
              videoSource.src = option.url.value; // Set the local video source

              const videoPlayer = videojs('videoPlayer');
              videoPlayer.src({ type: 'video/mp4', src: option.url.value });

              // Re-add subtitle tracks after quality change
              if (subtitleInfo && subtitleInfo.length > 0) {
                subtitleInfo.forEach(subtitle => {
                  videoPlayer.addRemoteTextTrack({
                    kind: 'subtitles',
                    src: subtitle.subtitle_file,
                    srclang: subtitle.language_code,
                    label: subtitle.language,
                    default: subtitle.is_default === 1
                  }, false);
                });
              }

              videoPlayer.load();
              videoPlayer.play();
            }
          } else {
            // Handle external video platforms
            fetch(`${baseUrl}/video/stream/${encodeURIComponent(selectedQuality)}`)
              .then(response => response.json())
              .then(data => {
                const { videoId, platform } = data;
                if (platform === 'youtube') {
                  player.src({ type: 'video/youtube', src: `https://www.youtube.com/watch?v=${videoId}` });
                } else if (platform === 'vimeo') {
                  player.src({ type: 'video/vimeo', src: `https://vimeo.com/${videoId}` });
                } else if (platform === 'hls') {
                  player.src({ type: 'application/x-mpegURL', src: url });
                }

                // Re-add subtitle tracks after quality change
                if (subtitleInfo && subtitleInfo.length > 0) {
                  subtitleInfo.forEach(subtitle => {
                    player.addRemoteTextTrack({
                      kind: 'subtitles',
                      src: subtitle.subtitle_file,
                      srclang: subtitle.language_code,
                      label: subtitle.language,
                      default: subtitle.is_default === 1
                    }, false);
                  });
                }

                player.load();
                player.play();
              })
              .catch(error => console.error('Error playing video:', error));
          }
        });

        qualitySelector.appendChild(qualityDropdown)
        player.controlBar.el().appendChild(qualitySelector)
      }
    } else {
      fetch(`${baseUrl}/video/stream/${encodeURIComponent(videoUrl)}`)
        .then((response) => response.json())
        .then((data) => {
          setVideoSource(player, data.platform, data.videoId, data.url, data.mimeType, qualityOptions, subtitleInfo)
          player.load()
          player.one('loadedmetadata', async function () {
            const isDeviceSupported = await CheckDeviceType()
            if (isDeviceSupported) {
              player.currentTime(lastWatchedTime)
              if (document.querySelector('#videoPlayer').getAttribute('data-movie-access') === 'free') {
                player.muted(true) // Mute the player for autoplay
                try {
                  await player.play() // Attempt to autoplay
                } catch (error) {
                  console.error('Error trying to autoplay:', error)
                }
              }
            }
          })
        })
        .catch((error) => console.error('Error playing video:', error))
    }
  }

  function handleSubscription(button, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo = []) {
    const planId = button.getAttribute('data-plan-id')
    fetch(`${baseUrl}/check-subscription/${planId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.isActive) {
          playVideo(videoUrl, qualityOptions, lastWatchedTime, subtitleInfo)
        } else {
          // Open the modal to show the user options for selecting or confirming a plan
          $('#DeviceSupport').modal('show')

          // Assuming you have a button inside the modal to proceed with payment
          document.querySelector('#confirmSubscriptionButton').addEventListener('click', function () {
            // Redirect to subscription plan after modal confirmation
            window.location.href = `${baseUrl}/subscription-plan`
          })
        }
      })
      .catch((error) => console.error('Error checking subscription:', error))
  }

  player.on('ended', async function () {
    if (isWatchHistorySaved) return

    const entertainmentId = watchNowButton?.getAttribute('data-entertainment-id') || seasonWatchBtn?.getAttribute('data-entertainment-id')
    const entertainmentType = watchNowButton?.getAttribute('data-entertainment-type') || seasonWatchBtn?.getAttribute('data-entertainment-type')
    const profileId = watchNowButton?.getAttribute('data-profile-id') || seasonWatchBtn?.getAttribute('data-profile-id')

    if (isAuthenticated && entertainmentId && entertainmentType && profileId) {
      // const isDeviceSupported = await CheckDeviceType()
      // if (!isDeviceSupported) {
      //   $('#DeviceSupport').modal('show')
      //   return
      // }

      const watchHistoryData = {
        entertainment_id: entertainmentId,
        entertainment_type: entertainmentType,
        profile_id: profileId
      }

      fetch(`${baseUrl}/api/save-watch-content`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(watchHistoryData)
      })
        .then((response) => response.json())
        .then((data) => {
          isWatchHistorySaved = true
        })
        .catch((error) => console.error('Error saving watch history:', error))
    }
  })

  window.addEventListener('beforeunload', async function () {
    const entertainmentId = watchNowButton?.getAttribute('data-entertainment-id') || seasonWatchBtn?.getAttribute('data-entertainment-id')
    const entertainmentType = watchNowButton?.getAttribute('data-entertainment-type') || seasonWatchBtn?.getAttribute('data-entertainment-type')
    const EpisodeId = watchNowButton?.getAttribute('data-episode-id') || seasonWatchBtn?.getAttribute('data-episode-id')

    if (isAuthenticated && currentVideoUrl && entertainmentId && entertainmentType) {
      const currentTime = player.currentTime()
      const totalWatchedTime = new Date(currentTime * 1000).toISOString().substr(11, 8)

      fetch(`${baseUrl}/api/save-continuewatch`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          entertainment_id: currentEntertainmentId,
          entertainment_type: currentEntertainmentType,
          total_watched_time: totalWatchedTime,
          watched_time: totalWatchedTime,
          episode_id: currentEpisodeId,
          video_url: currentVideoUrl
        })
      })

        .then((response) => response.json())
        .then((data) => {

        })
        .catch((error) => console.error('Error saving continue watching:', error))
    }
  })

  function setVideoSource(player, platform, videoId, url = '', mimeType = '', qualityOptions = [], subtitleInfo = []) {
    // Remove any existing iframe overlays
    const existingIframe = document.querySelector('.vjs-iframe-overlay');
    if (existingIframe) existingIframe.remove();

    if (platform === 'embed' || platform === 'embedded') {
      // Pause the player and hide the native video element
      player.pause();
      player.el().querySelector('video').style.display = 'none';

      // Create an overlay div for the iframe
      const iframeOverlay = document.createElement('div');
      iframeOverlay.className = 'vjs-iframe-overlay';
      iframeOverlay.style.position = 'absolute';
      iframeOverlay.style.top = '0';
      iframeOverlay.style.left = '0';
      iframeOverlay.style.width = '100%';
      iframeOverlay.style.height = '100%';
      iframeOverlay.style.zIndex = '10';
      iframeOverlay.innerHTML = `
        <iframe
          src="${url}"
          width="100%"
          height="100%"
          frameborder="0"
          allowfullscreen
          style="border:0;"
        ></iframe>
      `;

      // Append overlay to the Video.js player container
      player.el().appendChild(iframeOverlay);
    } else {
      // Remove overlay and show native video for other platforms
      const overlay = document.querySelector('.vjs-iframe-overlay');
      if (overlay) overlay.remove();
      const videoEl = player.el().querySelector('video');
      if (videoEl) videoEl.style.display = '';
      if (platform === 'youtube') {
        player.src({ type: 'video/youtube', src: `https://www.youtube.com/watch?v=${videoId}` });
      } else if (platform === 'vimeo') {
        player.src({ type: 'video/vimeo', src: `https://vimeo.com/${videoId}` });
      } else if (platform === 'hls') {
        player.src({ type: 'application/x-mpegURL', src: url });
      } else if (platform === 'local') {
        player.src({ type: mimeType, src: url });
      } else if (platform === 'external') {
        player.src({ type: 'video/mp4', src: videoId });
      }
    }

    // Add subtitle tracks if available
    if (subtitleInfo && subtitleInfo.length > 0) {
      // Remove any existing subtitle tracks
      const existingTracks = player.textTracks();
      for (let i = existingTracks.length - 1; i >= 0; i--) {
        player.removeRemoteTextTrack(existingTracks[i]);
      }

      // Add new subtitle tracks
      subtitleInfo.forEach(subtitle => {
        player.addRemoteTextTrack({
          kind: 'subtitles',
          src: subtitle.subtitle_file,
          srclang: subtitle.language_code,
          label: subtitle.language,
          default: subtitle.is_default === 1
        }, false);
      });
    }

    // Once the player is ready, find and enable the default subtitle track
    player.ready(function () {
      var tracks = this.textTracks();
      for (var i = 0; i < tracks.length; i++) {
        var track = tracks[i];
        if (track.kind === 'subtitles') {
          if (typeof defaultSubtitleLanguage !== 'undefined' && defaultSubtitleLanguage && track.language === defaultSubtitleLanguage) {
            track.mode = 'showing';
          } else {
            track.mode = 'disabled';
          }
        }
      }
    });
    const existingQualitySelector = document.querySelector('.vjs-quality-selector')
    if (!existingQualitySelector && qualityOptions.length > 0) {
      const qualitySelector = document.createElement('div')
      qualitySelector.classList.add('vjs-quality-selector')

      const qualityDropdown = document.createElement('select')

      qualityOptions.forEach((option) => {
        const qualityOption = document.createElement('option')
        qualityOption.value = option.url.value // Use the URL for the quality option
        qualityOption.innerText = option.label // Display the label (e.g., "360p", "720p")
        qualityOption.setAttribute('data-type', option.url.type);
        qualityDropdown.appendChild(qualityOption)
      })

      qualityDropdown.addEventListener('change', function () {
        const selectedQuality = this.value

        var videoId = null
        var platform = null
        var url = null
        qualityOptions.forEach((option) => {
          if (option.url.type === 'Local') {
            const videoSource = document.querySelectorAll('#videoSource');
            videoSource.src = option.url.value;

            const videoPlayer = videojs('videoPlayer');
            videoPlayer.src({ type: 'video/mp4', src: option.url.value });
            videoPlayer.load();
            videoPlayer.play();
          } else {
            fetch(`${baseUrl}/video/stream/${encodeURIComponent(selectedQuality)}`)
              .then((response) => response.json())
              .then((data) => {
                videoId = data.videoId
                platform = data.platform

                if (platform == 'youtube') {
                  player.src({ type: 'video/youtube', src: `https://www.youtube.com/watch?v=${videoId}` })
                } else if (platform === 'vimeo') {
                  player.src({ type: 'video/vimeo', src: `https://vimeo.com/${videoId}` })
                } else if (platform === 'hls') {
                  player.src({ type: 'application/x-mpegURL', src: url })
                }
              })
              .catch((error) => console.error('Error playing video:', error))
            player.load()
            player.play() // Play the selected quality
          }
        })
      })

      qualitySelector.appendChild(qualityDropdown)
      player.controlBar.el().appendChild(qualitySelector)
    }
  }

  class SkipTrainerButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.addClass('vjs-skip-trainer-button');
      // Get video element references
      const videoElement = document.querySelector('#videoPlayer');
      this.movieStartTime = parseFloat(videoElement?.getAttribute('data-movie-start')) || 300;
      this.watchNowButton = document.getElementById('watchNowButton');
      this.seasonWatchBtn = document.getElementById('seasonWatchBtn');
      this.baseUrl = options.baseUrl;
      this.player_ = player;
      // Get entertainment data using data-entertainment-id instead of data-video-url
      this.entertainmentId = this.watchNowButton?.getAttribute('data-entertainment-id') ||
        this.seasonWatchBtn?.getAttribute('data-entertainment-id');
      this.entertainmentType = this.watchNowButton?.getAttribute('data-entertainment-type') ||
        this.seasonWatchBtn?.getAttribute('data-entertainment-type');
      this.episodeId = this.watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id');
      // Add timeupdate listener to control visibility
      this.timeUpdateHandler = this.handleTimeUpdate.bind(this);
      this.player_.on('timeupdate', this.timeUpdateHandler);
      // Initial visibility
      this.trailerSkipped = false; // Add flag to track if trailer was skipped
      this.handleTimeUpdate();
    }

    createEl() {
      return super.createEl('button', {
        innerHTML: 'Pular Trailer',
        className: 'vjs-skip-trainer-button vjs-control'
      });
    }

    handleTimeUpdate() {
      // Show only if currentTime < movieStartTime and trailer not skipped
      if (this.player_.currentTime() < this.movieStartTime && !this.trailerSkipped) {
        this.show();
        // Hide NextEpisodeButton if present
        const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
        if (nextBtn) nextBtn.hide();
      } else {
        this.hide();
        // Show NextEpisodeButton if it should be visible
        const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
        if (nextBtn && typeof nextBtn.updateVisibility === 'function') nextBtn.updateVisibility();
      }
    }

    handleClick() {
      // Get the video URL and quality options from watch now button
      const button = this.watchNowButton || this.seasonWatchBtn;
      if (!button) {
        console.error('Watch button not found');
        return;
      }

      const videoUrl = button.getAttribute('data-video-url');
      const qualityOptionsData = button.getAttribute('data-quality-options');
      const qualityOptions = qualityOptionsData ?
        Object.entries(JSON.parse(qualityOptionsData)).map(([label, url]) => ({ label, url })) : [];
      const accessType = button.getAttribute('data-movie-access');
      const planId = button.getAttribute('data-plan-id');

      // Check episode purchase status if it's pay-per-view
      if (this.episodeId) {
        fetch(`${this.baseUrl}/api/check-episode-purchase?episode_id=${this.episodeId}`)
          .then(response => response.json())
          .then(data => {
            if (!data.is_purchased) {
              // Redirect to purchase page
              window.location.href = `${this.baseUrl}/payment-form/pay-per-view?type=episode&id=${this.episodeId}`;
              return;
            }
          })
          .catch(error => {
            console.error('Error checking episode purchase:', error);
          });
      }

      // Check authentication and device support for paid content
      if (accessType === 'paid') {
        checkAuthenticationAndDeviceSupport().then(canPlay => {
          if (!canPlay) {
            $('#DeviceSupport').modal('show');
            return;
          }

          // If device is supported, proceed with subscription check
          if (planId) {
            CheckSubscription(planId).then(isActive => {
              if (isActive) {
                this._playMovie(videoUrl, qualityOptions);
              } else {
                $('#DeviceSupport').modal('show');
                // Optionally add click handler for confirmSubscriptionButton
                const confirmBtn = document.querySelector('#confirmSubscriptionButton');
                if (confirmBtn) {
                  confirmBtn.addEventListener('click', () => {
                    window.location.href = `${this.baseUrl}/subscription-plan`;
                  }, { once: true });
                }
              }
            }).catch(error => {
              console.error('Error checking subscription:', error);
              // $('#DeviceSupport').modal('show');
            });
          }
        }).catch(error => {
          console.error('Error checking device support:', error);
          // $('#DeviceSupport').modal('show');
        });
      } else {
        // Free or no plan required, play movie
        this._playMovie(videoUrl, qualityOptions);
      }
    }

    _playMovie(videoUrl, qualityOptions) {
      // Get subtitle info from the button
      const button = this.watchNowButton || this.seasonWatchBtn;
      const subtitleInfo = JSON.parse(button.getAttribute('data-subtitle-info') || '[]');
      fetch(`${this.baseUrl}/video/stream/${encodeURIComponent(videoUrl)}`)
        .then((response) => response.json())
        .then((data) => {
          setVideoSource(this.player_, data.platform, data.videoId, data.url, data.mimeType, qualityOptions, subtitleInfo);
          this.player_.load();
          this.player_.one('loadedmetadata', () => {
            this.player_.currentTime(0);
            this.player_.muted(true);
            this.player_.play()
              .then(() => {
                this.trailerSkipped = true; // Set flag so button does not reappear
                this.hide(); // Hide skip button after successful play
              })
              .catch(error => console.error('Error playing movie:', error));
          });
        })
        .catch((error) => console.error('Error loading video:', error));
    }
  }

  // Register the new component
  videojs.registerComponent('SkipTrainerButton', SkipTrainerButton);


  class PreviousEpisodeButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.controlText('Previous Episode');
      this.addClass('vjs-previous-episode-button');

      // Get button references
      this.watchNowButton = document.getElementById('watchNowButton');
      this.seasonWatchBtn = document.getElementById('seasonWatchBtn');

      // Get current episode info
      this.currentEpisodeId = this.watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id');

      // Show button only if it's not the first episode
      this.updateVisibility();
    }

    createEl() {
      return super.createEl('button', {
        innerHTML: '<span class="vjs-icon-previous-item"></span>',
        className: 'vjs-previous-episode-button vjs-control vjs-button'
      });
    }

    updateVisibility() {
      const currentId = parseInt(this.currentEpisodeId);
      if (!this.currentEpisodeId || currentId <= 1) {
        this.hide();
      } else {
        this.show();
      }
    }

    handleClick() {
      // Get current episode button
      const button = this.watchNowButton || this.seasonWatchBtn;
      if (!button) return;

      // Get previous episode data
      const previousEpisodeId = parseInt(this.currentEpisodeId) - 1;
      const previousEpisodeButton = document.querySelector(`[data-episode-id="${previousEpisodeId}"]`);

      if (previousEpisodeButton) {
        // Store current time before switching
        const currentTime = this.player_.currentTime();

        // Update current episode ID before click
        this.currentEpisodeId = previousEpisodeId;

        // Trigger click on previous episode button
        previousEpisodeButton.click();

        // Update visibility after changing episode
        this.updateVisibility();

        // Handle video source change
        this.player_.one('loadedmetadata', () => {
          // Reset playback state
          this.player_.muted(true);
          this.player_.currentTime(0);
          this.player_.play().catch(error => {
            console.error('Error playing previous episode:', error);
          });
        });
      }
    }

    // Update episode ID when source changes
    updateEpisodeId(newId) {
      this.currentEpisodeId = newId;
      this.updateVisibility();
    }
  }

  // Register the component
  videojs.registerComponent('PreviousEpisodeButton', PreviousEpisodeButton);




  class NextEpisodeButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.controlText('Next Episode');
      this.addClass('vjs-next-episode-button');
      this.watchNowButton = document.getElementById('watchNowButton');

      // Get initial episode ID from any season watch button
      const seasonWatchBtn = document.querySelector('.season-watch-btn');
      if (seasonWatchBtn) {
        this.currentEpisodeId = seasonWatchBtn.getAttribute('data-episode-id');
      } else {
        this.currentEpisodeId = this.watchNowButton?.getAttribute('data-episode-id');
      }

      this.totalEpisodes = this.getTotalEpisodes();
      this.player_ = player;
      // Get movieStartTime for trailer logic
      const videoElement = document.querySelector('#videoPlayer');
      this.movieStartTime = parseFloat(videoElement?.getAttribute('data-movie-start')) || 300;
      this.timeUpdateHandler = this.handleTimeUpdate.bind(this);
      this.player_.on('timeupdate', this.timeUpdateHandler);

      // Add click event listener to all season watch buttons
      document.querySelectorAll('.season-watch-btn').forEach(button => {
        button.addEventListener('click', (e) => {
          // Get episode ID from the clicked button
          const episodeId = e.currentTarget.getAttribute('data-episode-id');
          if (episodeId) {
            this.currentEpisodeId = episodeId;
            this.updateVisibility();
          }
        });
      });

      this.removeExistingButtons();
      this.updateVisibility();
      this.removeExistingButtons();
      this.updateVisibility();
    }

    removeExistingButtons() {
      const existingButtons = document.querySelectorAll('.vjs-next-episode-button');
      existingButtons.forEach(button => {
        if (button !== this.el_) {
          button.remove();
        }
      });
    }

    createEl() {
      return super.createEl('button', {
        innerHTML: 'Next Episode',
        className: 'vjs-next-episode-button vjs-control vjs-button p-2'
      });
    }

    getTotalEpisodes() {
      // Use the same filtering logic as in handleClick
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      return episodeIds.length;
    }

    handleTimeUpdate() {
      if (!this.player_) return;

      const currentTime = this.player_.currentTime();
      const duration = this.player_.duration();

      // Calculate 20% threshold
      const showThreshold = duration * 0.2;
      const trailerThreshold = Math.min(this.movieStartTime, duration * 0.3);

      // Hide if in trailer section
      if (currentTime < trailerThreshold) {
        this.hide();
        const skipBtn = this.player_.controlBar.getChild('SkipTrainerButton');
        if (skipBtn) skipBtn.show();
        return;
      }

      const timeLeft = duration - currentTime;

      // Show button when:
      // 1. Time left is less than 20% of total duration
      // 2. There is a next episode
      // 3. It's not the last episode
      if (timeLeft <= showThreshold && this.hasNextEpisode() && !this.isLastEpisode()) {
        this.show();
      } else {
        this.hide();
      }
    }

    isLastEpisode() {
      // Use the filtered episodeIds array
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      const currentIndex = episodeIds.indexOf(this.currentEpisodeId);
      return currentIndex === episodeIds.length - 1;
    }

    hasNextEpisode() {
      // Get all episode IDs in the DOM
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      // If the first and second elements are the same, remove the first one
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      const currentIndex = episodeIds.indexOf(this.currentEpisodeId);
      const nextId = episodeIds[currentIndex + 1];
      // console.log('Current index:', currentIndex, 'Next episode ID:', nextId);
      return !!nextId;
    }

    updateVisibility() {
      if (!this.player_) return;

      // Basic checks
      if (!this.currentEpisodeId || !this.hasNextEpisode() || this.isLastEpisode()) {
        this.hide();
        return;
      }

      const currentTime = this.player_.currentTime();
      const duration = this.player_.duration();

      // Calculate 20% threshold
      const showThreshold = duration * 0.2;

      const timeLeft = duration - currentTime;

      // Show button if time left is less than 20% of total duration
      if (timeLeft <= showThreshold) {
        this.show();
      } else {
        this.hide();
      }
    }

    async handleClick() {
      const button = this.watchNowButton || this.seasonWatchBtn;
      if (!button) return;

      // Get all episode IDs in the DOM and log them
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      // If the first and second elements are the same, remove the first one
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      // console.log('All episode IDs in DOM:', episodeIds);
      const currentIndex = episodeIds.indexOf(this.currentEpisodeId);
      const nextId = episodeIds[currentIndex + 1];


      // Find the corresponding button for nextId
      const filteredButtons = episodeButtons;
      if (episodeButtons.length > 1 && episodeButtons[0].getAttribute('data-episode-id') === episodeButtons[1].getAttribute('data-episode-id')) {
        filteredButtons.shift();
      }
      const nextEpisodeButton = filteredButtons[currentIndex + 1];

      if (nextEpisodeButton) {
        const wasPlaying = !this.player_.paused();
        // Do NOT set this.currentEpisodeId = nextId yet

        // --- Pay-per-view logic start ---
        const accessType = nextEpisodeButton.getAttribute('data-movie-access');
        const episodeId = nextEpisodeButton.getAttribute('data-episode-id');
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        if (accessType === 'pay-per-view') {
          try {
            const response = await fetch(`${baseUrl}/api/check-episode-purchase?episode_id=${episodeId}&t=${Date.now()}`);
            const data = await response.json();
            if (data.is_purchased == false) {
              // Not purchased: redirect to payment form
              window.location.href = `${baseUrl}/payment-form/pay-per-view?type=episode&id=${episodeId}`;
              return; // STOP here, do not continue!
            }
            // else: purchased, continue to play
          } catch (error) {
            console.error('[NextEpisodeButton] Error checking episode purchase:', error);
            return;
          }
        }

        // Only now, after passing the check, update the current episode and play
        this.currentEpisodeId = nextId;
        nextEpisodeButton.click();

        this.player_.one('loadedmetadata', () => {
          this.player_.muted(true);
          this.player_.currentTime(0);
          this.updateVisibility();

          if (wasPlaying) {
            this.player_.play().catch(error => {
              console.error('Error playing next episode:', error);
            });
          }
        });
      } else {
        console.warn('No next episode available');
        this.hide();
      }
    }

    dispose() {
      if (this.player_) {
        this.player_.off('timeupdate', this.timeUpdateHandler);
      }
      super.dispose();
    }
  }
  videojs.registerComponent('NextEpisodeButton', NextEpisodeButton);


  function timeStringToSeconds(timeString) {
    const [hours, minutes, seconds] = timeString.split(':').map(Number)
    return hours * 3600 + minutes * 60 + seconds
  }
})
