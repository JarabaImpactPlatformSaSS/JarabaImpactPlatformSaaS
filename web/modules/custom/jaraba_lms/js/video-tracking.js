/**
 * @file
 * Video tracking for LMS lessons
 * 
 * Tracks video progress and sends xAPI statements for completion tracking.
 * Supports YouTube, Vimeo, and native HTML5 video.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Video tracking behavior.
   */
  Drupal.behaviors.lmsVideoTracking = {
    attach: function (context, settings) {
      // Initialize YouTube players
      once('video-tracking-youtube', '.video-wrapper--youtube', context).forEach(function (wrapper) {
        Drupal.lmsVideoTracking.initYouTube(wrapper);
      });

      // Initialize native HTML5 video
      once('video-tracking-native', '.video-wrapper--native video', context).forEach(function (video) {
        Drupal.lmsVideoTracking.initNative(video);
      });
    }
  };

  /**
   * Video tracking namespace.
   */
  Drupal.lmsVideoTracking = Drupal.lmsVideoTracking || {
    players: {},
    progress: {},

    /**
     * Initialize YouTube player tracking.
     */
    initYouTube: function (wrapper) {
      var self = this;
      var lessonId = wrapper.dataset.lessonId;
      var videoId = wrapper.dataset.videoId;
      var threshold = parseInt(wrapper.dataset.completionThreshold) || 90;
      var xapiEndpoint = wrapper.dataset.xapiEndpoint || '/api/v1/xapi/statements';

      // Initialize progress tracking
      this.progress[lessonId] = {
        started: false,
        completed: false,
        maxWatched: 0,
        threshold: threshold,
        endpoint: xapiEndpoint
      };

      // Load YouTube IFrame API if not loaded
      if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
        var tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        // Queue initialization
        window.onYouTubeIframeAPIReady = function () {
          self.createYouTubePlayer(wrapper, lessonId);
        };
      } else {
        this.createYouTubePlayer(wrapper, lessonId);
      }
    },

    /**
     * Create YouTube player instance.
     */
    createYouTubePlayer: function (wrapper, lessonId) {
      var self = this;
      var iframe = wrapper.querySelector('iframe');
      
      if (!iframe) return;

      var playerId = iframe.id;

      this.players[lessonId] = new YT.Player(playerId, {
        events: {
          'onStateChange': function (event) {
            self.onYouTubeStateChange(event, lessonId);
          },
          'onReady': function (event) {
            // Start tracking interval
            setInterval(function () {
              self.trackYouTubeProgress(lessonId);
            }, 5000);
          }
        }
      });
    },

    /**
     * Handle YouTube state changes.
     */
    onYouTubeStateChange: function (event, lessonId) {
      var progress = this.progress[lessonId];

      switch (event.data) {
        case YT.PlayerState.PLAYING:
          if (!progress.started) {
            progress.started = true;
            this.sendXapiStatement(lessonId, 'played');
          }
          break;

        case YT.PlayerState.PAUSED:
          this.sendXapiStatement(lessonId, 'paused');
          break;

        case YT.PlayerState.ENDED:
          this.checkCompletion(lessonId, 100);
          break;
      }
    },

    /**
     * Track YouTube progress periodically.
     */
    trackYouTubeProgress: function (lessonId) {
      var player = this.players[lessonId];
      var progress = this.progress[lessonId];

      if (!player || typeof player.getCurrentTime !== 'function') return;

      var currentTime = player.getCurrentTime();
      var duration = player.getDuration();

      if (duration > 0) {
        var percent = Math.round((currentTime / duration) * 100);
        
        if (percent > progress.maxWatched) {
          progress.maxWatched = percent;
          this.updateProgressUI(lessonId, percent);
          this.checkCompletion(lessonId, percent);
        }
      }
    },

    /**
     * Initialize native HTML5 video tracking.
     */
    initNative: function (video) {
      var self = this;
      var wrapper = video.closest('.video-wrapper--native');
      var lessonId = wrapper.dataset.lessonId;

      this.progress[lessonId] = {
        started: false,
        completed: false,
        maxWatched: 0,
        threshold: 90
      };

      video.addEventListener('play', function () {
        if (!self.progress[lessonId].started) {
          self.progress[lessonId].started = true;
          self.sendXapiStatement(lessonId, 'played');
        }
      });

      video.addEventListener('pause', function () {
        self.sendXapiStatement(lessonId, 'paused');
      });

      video.addEventListener('timeupdate', function () {
        var percent = Math.round((video.currentTime / video.duration) * 100);
        if (percent > self.progress[lessonId].maxWatched) {
          self.progress[lessonId].maxWatched = percent;
          self.updateProgressUI(lessonId, percent);
          self.checkCompletion(lessonId, percent);
        }
      });

      video.addEventListener('ended', function () {
        self.checkCompletion(lessonId, 100);
      });
    },

    /**
     * Check if completion threshold is met.
     */
    checkCompletion: function (lessonId, percent) {
      var progress = this.progress[lessonId];

      if (!progress.completed && percent >= progress.threshold) {
        progress.completed = true;
        this.sendXapiStatement(lessonId, 'completed');
        this.markLessonComplete(lessonId);
      }
    },

    /**
     * Update progress UI.
     */
    updateProgressUI: function (lessonId, percent) {
      var progressEl = document.getElementById('lesson-progress-' + lessonId);
      if (!progressEl) return;

      var fill = progressEl.querySelector('.progress-bar__fill');
      var label = progressEl.querySelector('.progress-bar__label');

      if (fill) {
        fill.style.width = percent + '%';
      }

      if (label) {
        if (percent >= 100) {
          label.textContent = Drupal.t('Completed');
          progressEl.closest('.lesson').classList.add('lesson--completed');
        } else {
          label.textContent = percent + '% ' + Drupal.t('watched');
        }
      }
    },

    /**
     * Send xAPI statement.
     */
    sendXapiStatement: function (lessonId, verb) {
      var progress = this.progress[lessonId];
      
      // Prepare statement
      var statement = {
        lesson_id: lessonId,
        verb: verb,
        timestamp: new Date().toISOString(),
        progress: progress.maxWatched
      };

      // Send to xAPI endpoint
      fetch(progress.endpoint || '/api/v1/xapi/statements', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(statement)
      }).catch(function (error) {
        console.warn('xAPI statement failed:', error);
      });
    },

    /**
     * Mark lesson as complete in backend.
     */
    markLessonComplete: function (lessonId) {
      fetch('/api/v1/lms/lessons/' + lessonId + '/complete', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).then(function (response) {
        if (response.ok) {
          // Dispatch custom event
          document.dispatchEvent(new CustomEvent('lessonCompleted', {
            detail: { lessonId: lessonId }
          }));
        }
      }).catch(function (error) {
        console.warn('Mark complete failed:', error);
      });
    }
  };

})(Drupal, once);
