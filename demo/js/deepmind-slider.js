/**
 * DeepMind Hero Slider Engine
 * High-performance, zero-dependency carousel engine mirroring Google DeepMind's Embla carousel.
 * Handles centering, peeking, touch/mouse dragging, loop, video playback, and accessibility.
 */

(function () {
  'use strict';

  class DeepMindCarousel {
    constructor(element, options = {}) {
      this.root = element;
      this.viewport = element.querySelector('.embla');
      this.container = element.querySelector('.embla__container');
      this.slides = Array.from(element.querySelectorAll('.w-block-cover'));
      this.prevBtn = element.querySelector('.js-carousel__button--prev');
      this.nextBtn = element.querySelector('.js-carousel__button--next');
      this.announcer = element.querySelector('.sr-only');
      this.caption = element.querySelector('.caption__text');

      if (!this.viewport || !this.container || this.slides.length === 0) {
        return;
      }

      // Options
      const dataOptions = this.parseDataOptions();
      this.options = Object.assign(
        {
          loop: true,
          autoplay: false,
          autoplaySpeed: 6000,
          advanceOnVideoEnd: true,
          speed: 450,
          draggable: true,
          startIndex: 0
        },
        dataOptions,
        options
      );

      this.currentIndex = Math.max(0, Math.min(this.options.startIndex, this.slides.length - 1));
      this.totalSlides = this.slides.length;
      this.isDragging = false;
      this.hasMoved = false;
      this.preventClick = false;
      this.startX = 0;
      this.startY = 0;
      this.currentX = 0;
      this.startTranslate = 0;
      this.currentTranslate = 0;
      this.dragStartTime = 0;
      this.autoplayTimer = null;
      this.wasAutoplaying = false;
      this.isPaused = false;

      this.init();
    }

    parseDataOptions() {
      const opts = {};
      if (this.root.hasAttribute('data-loop')) {
        opts.loop = this.root.getAttribute('data-loop') === 'true';
      }
      if (this.root.hasAttribute('data-autoplay')) {
        opts.autoplay = this.root.getAttribute('data-autoplay') === 'true';
      }
      if (this.root.hasAttribute('data-autoplay-speed')) {
        opts.autoplaySpeed = parseInt(this.root.getAttribute('data-autoplay-speed'), 10) || 6000;
      }
      if (this.root.hasAttribute('data-advance-on-video-end')) {
        opts.advanceOnVideoEnd = this.root.getAttribute('data-advance-on-video-end') !== 'false';
      }
      return opts;
    }

    init() {
      // Event listeners for buttons
      if (this.prevBtn) {
        this.prevBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.prev();
        });
      }

      if (this.nextBtn) {
        this.nextBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.next();
        });
      }

      // Capture and discard ghost clicks resulting from drag gestures
      this.root.addEventListener(
        'click',
        (e) => {
          if (this.preventClick) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
          }
        },
        true // Capturing phase
      );

      // Click on peeked slides to jump directly
      this.slides.forEach((slide, idx) => {
        slide.addEventListener('click', (e) => {
          if (this.preventClick || this.isDragging || this.hasMoved) {
            e.preventDefault();
            e.stopPropagation();
            return;
          }
          if (idx !== this.currentIndex) {
            // Only jump if click was on the peeked edge and not an inner link
            const isInteractive = e.target.closest('a, button, input');
            if (!isInteractive) {
              e.preventDefault();
              this.goTo(idx);
            }
          }
        });
      });

      // Prevent default page jump on dummy links (href="#" or empty)
      this.root.addEventListener('click', (e) => {
        const anchor = e.target.closest('a');
        if (anchor) {
          const href = anchor.getAttribute('href');
          if (href === '#' || href === '') {
            e.preventDefault();
          }
        }
      });

      // Pointer / touch drag listeners
      if (this.options.draggable && this.totalSlides > 1) {
        this.initDragging();
      }

      // Keyboard navigation
      this.root.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
          this.prev();
        } else if (e.key === 'ArrowRight') {
          this.next();
        }
      });

      // Autoplay hover pause
      this.root.addEventListener('mouseenter', () => {
        this.isPaused = true;
      });

      this.root.addEventListener('mouseleave', () => {
        this.isPaused = false;
      });

      // Window resize
      window.addEventListener('resize', this.debounce(() => {
        this.updatePosition(false);
      }, 100));

      // Page visibility: only resume autoplay on instances that were actually playing.
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          this.wasAutoplaying = this.autoplayTimer !== null;
          this.stopAutoplay();
        } else if (this.options.autoplay && this.wasAutoplaying) {
          this.wasAutoplaying = false;
          this.startAutoplay();
        }
      });

      // Render initial state
      this.goTo(this.currentIndex, false);

      // Start autoplay if enabled
      if (this.options.autoplay) {
        this.startAutoplay();
      }
    }

    initDragging() {
      const getCoords = (e) => {
        if (e.touches && e.touches.length > 0) {
          return { x: e.touches[0].clientX, y: e.touches[0].clientY };
        }
        if (e.changedTouches && e.changedTouches.length > 0) {
          return { x: e.changedTouches[0].clientX, y: e.changedTouches[0].clientY };
        }
        return { x: e.clientX, y: e.clientY };
      };

      const cleanupListeners = () => {
        window.removeEventListener('mousemove', onPointerMove);
        window.removeEventListener('mouseup', onPointerUp);
        window.removeEventListener('touchmove', onPointerMove);
        window.removeEventListener('touchend', onPointerUp);
        window.removeEventListener('touchcancel', onPointerUp);
      };

      const onPointerDown = (e) => {
        // Only primary mouse button or touch
        if (e.type === 'mousedown' && e.button !== 0) return;

        const coords = getCoords(e);
        this.startX = coords.x;
        this.startY = coords.y;
        this.currentX = coords.x;
        this.hasMoved = false;
        this.isDragging = false;
        this.dragStartTime = Date.now();

        // Sample current visual translation (supports mid-animation grab)
        const computedTransform = window.getComputedStyle(this.container).transform;
        if (computedTransform && computedTransform !== 'none') {
          try {
            const matrix = new DOMMatrixReadOnly(computedTransform);
            this.currentTranslate = matrix.m41;
          } catch (err) {
            this.currentTranslate = this.calcTranslate(this.currentIndex);
          }
        } else {
          this.currentTranslate = this.calcTranslate(this.currentIndex);
        }
        this.startTranslate = this.currentTranslate;

        window.addEventListener('mousemove', onPointerMove, { passive: false });
        window.addEventListener('mouseup', onPointerUp);
        window.addEventListener('touchmove', onPointerMove, { passive: false });
        window.addEventListener('touchend', onPointerUp);
        window.addEventListener('touchcancel', onPointerUp);
      };

      const onPointerMove = (e) => {
        const coords = getCoords(e);
        const diffX = coords.x - this.startX;
        const diffY = coords.y - this.startY;

        // Check if movement is primarily horizontal
        if (!this.isDragging) {
          if (Math.abs(diffX) > 6 && Math.abs(diffX) > Math.abs(diffY)) {
            this.isDragging = true;
            this.hasMoved = true;
            this.preventClick = true;
            this.viewport.classList.add('is-dragging');
            this.container.style.transition = 'none';
          } else if (Math.abs(diffY) > 8) {
            // Intent is vertical page scrolling; disconnect drag listeners
            cleanupListeners();
            return;
          }
        }

        if (!this.isDragging) return;

        if (e.cancelable) {
          e.preventDefault();
        }

        this.currentX = coords.x;
        let newTranslate = this.startTranslate + diffX;

        // Apply resistance if not loop mode
        if (!this.options.loop) {
          const minTranslate = this.calcTranslate(this.totalSlides - 1);
          const maxTranslate = this.calcTranslate(0);
          if (newTranslate > maxTranslate) {
            newTranslate = maxTranslate + diffX * 0.25;
          } else if (newTranslate < minTranslate) {
            newTranslate = minTranslate + diffX * 0.25;
          }
        }

        this.setTranslate(newTranslate);
      };

      const onPointerUp = (e) => {
        cleanupListeners();

        if (!this.isDragging && !this.hasMoved) {
          return;
        }

        // Keep preventClick active so subsequent click event on the slide is swallowed
        this.preventClick = true;
        setTimeout(() => {
          this.preventClick = false;
          this.isDragging = false;
          this.hasMoved = false;
        }, 120);

        this.viewport.classList.remove('is-dragging');
        this.container.style.transition = `transform ${this.options.speed}ms cubic-bezier(0.22, 1, 0.36, 1)`;

        const coords = getCoords(e);
        if (coords.x !== undefined && !isNaN(coords.x)) {
          this.currentX = coords.x;
        }

        const diffX = this.currentX - this.startX;
        const elapsed = Math.max(1, Date.now() - this.dragStartTime);
        const velocity = Math.abs(diffX) / elapsed;
        const slideWidth = this.slides[this.currentIndex] ? this.slides[this.currentIndex].offsetWidth : 300;
        
        // Sensitive thresholds: 45px or flick velocity > 0.22 px/ms
        const threshold = Math.min(45, slideWidth * 0.08);
        const isFlick = velocity > 0.22 && Math.abs(diffX) > 15;

        if (diffX < -threshold || (diffX < 0 && isFlick)) {
          // Dragged left -> Advance to next slide
          this.next();
        } else if (diffX > threshold || (diffX > 0 && isFlick)) {
          // Dragged right -> Return to previous slide
          this.prev();
        } else {
          // Under threshold -> Snap back to current slide
          this.goTo(this.currentIndex, true);
        }
      };

      this.viewport.addEventListener('mousedown', onPointerDown);
      this.viewport.addEventListener('touchstart', onPointerDown, { passive: true });
    }
    calcTranslate(index) {
      if (!this.slides[index]) return 0;

      const viewportWidth = this.viewport.offsetWidth;
      const targetSlide = this.slides[index];
      const slideWidth = targetSlide.offsetWidth;
      const slideOffsetLeft = targetSlide.offsetLeft;

      // Center the active slide in viewport:
      // (viewportWidth - slideWidth) / 2 is the left margin needed to center the slide.
      // Subtract slideOffsetLeft to bring the slide to that center position.
      const centerOffset = (viewportWidth - slideWidth) / 2;
      return centerOffset - slideOffsetLeft;
    }

    setTranslate(value) {
      this.currentTranslate = value;
      this.container.style.transform = `translate3d(${Math.round(value)}px, 0px, 0px)`;
    }

    goTo(index, animate = true) {
      let targetIndex = index;

      if (this.options.loop) {
        if (targetIndex < 0) {
          targetIndex = this.totalSlides - 1;
        } else if (targetIndex >= this.totalSlides) {
          targetIndex = 0;
        }
      } else {
        targetIndex = Math.max(0, Math.min(targetIndex, this.totalSlides - 1));
      }

      // Jumping from the last slide to the first (or back) would sweep across every slide,
      // so a wrap-around is shown as an instant jump instead of a long animation.
      const isWrap = this.wrapJump === true;
      this.wrapJump = false;

      this.currentIndex = targetIndex;
      this.updateClasses();
      this.updateControls();
      this.updatePosition(animate && !isWrap);
      this.manageVideos();
      this.updateAnnouncer();
    }

    next() {
      this.wrapJump = this.options.loop && this.currentIndex === this.totalSlides - 1;
      this.goTo(this.currentIndex + 1, true);
      this.resetAutoplayTimer();
    }

    prev() {
      this.wrapJump = this.options.loop && this.currentIndex === 0;
      this.goTo(this.currentIndex - 1, true);
      this.resetAutoplayTimer();
    }

    updatePosition(animate = true) {
      const targetTranslate = this.calcTranslate(this.currentIndex);
      if (!animate) {
        this.container.style.transition = 'none';
        this.setTranslate(targetTranslate);
        // Force reflow
        this.container.offsetHeight;
        this.container.style.transition = `transform ${this.options.speed}ms cubic-bezier(0.22, 1, 0.36, 1)`;
      } else {
        this.container.style.transition = `transform ${this.options.speed}ms cubic-bezier(0.22, 1, 0.36, 1)`;
        this.setTranslate(targetTranslate);
      }
    }

    updateClasses() {
      this.slides.forEach((slide, idx) => {
        if (idx === this.currentIndex) {
          slide.classList.add('active-slide', 'is-in-view');
          slide.classList.remove('embla__slide--peeked');
          slide.setAttribute('aria-hidden', 'false');
        } else {
          slide.classList.remove('active-slide');
          slide.classList.add('embla__slide--peeked');
          slide.setAttribute('aria-hidden', 'true');
        }
      });
    }

    updateControls() {
      if (!this.options.loop) {
        if (this.prevBtn) {
          this.prevBtn.disabled = this.currentIndex === 0;
        }
        if (this.nextBtn) {
          this.nextBtn.disabled = this.currentIndex === this.totalSlides - 1;
        }
      } else {
        if (this.prevBtn) this.prevBtn.disabled = false;
        if (this.nextBtn) this.nextBtn.disabled = false;
      }
    }

    /**
     * Compute buffered ratio of an HTML5 video (0.0 to 1.0)
     */
    getBufferedRatio(video) {
      if (!video || !video.duration || isNaN(video.duration) || video.duration <= 0) {
        return 0;
      }
      try {
        const buffered = video.buffered;
        let total = 0;
        for (let i = 0; i < buffered.length; i++) {
          total += (buffered.end(i) - buffered.start(i));
        }
        return total / video.duration;
      } catch (err) {
        return 0;
      }
    }

    manageVideos() {
      const activeSlide = this.slides[this.currentIndex];
      const activeVideo = activeSlide ? activeSlide.querySelector('video') : null;

      this.slides.forEach((slide, idx) => {
        const video = slide.querySelector('video');
        const poster = slide.querySelector('.media-video-poster');

        if (!video) return;

        if (idx === this.currentIndex) {
          // If auto-advance on video end is enabled, disable native looping so 'ended' event fires
          if (this.options.advanceOnVideoEnd) {
            video.loop = false;
            video.removeAttribute('loop');
          } else {
            video.loop = true;
          }

          // Attach 'ended' listener once per video element
          if (!video._hasEndedListener) {
            video._hasEndedListener = true;
            video.addEventListener('ended', () => {
              if (this.options.advanceOnVideoEnd && this.slides[this.currentIndex] === slide && !this.isDragging) {
                this.next();
              }
            });
          }

          // Attach error listener fallback
          if (!video._hasErrorListener) {
            video._hasErrorListener = true;
            video.addEventListener('error', () => {
              if (this.options.autoplay && this.slides[this.currentIndex] === slide) {
                this.startAutoplay();
              }
            });
          }

          // Crossfade function: triggers video opacity 0 -> 1 and poster 1 -> 0
          // Video begins playback immediately as crossfade animation starts
          const triggerCrossfadeAndPlay = () => {
            if (video._isCrossfading || video._hasCrossfaded) return;
            video._isCrossfading = true;
            video._hasCrossfaded = true;

            // Ensure muted and configured loop
            video.muted = true;
            if (!this.options.advanceOnVideoEnd) {
              video.loop = true;
            }

            // Immediately start playing
            const playPromise = video.play();
            if (playPromise !== undefined) {
              playPromise.catch(() => {
                if (this.options.autoplay) {
                  this.startAutoplay();
                }
              });
            }

            // Initiate CSS crossfade animation
            video.classList.add('is-ready');
            if (poster) {
              poster.classList.add('is-hidden');
            }
          };

          // Check if video has reached > 50% loaded buffer (or readyState 4)
          const checkBuffer = () => {
            if (video._hasCrossfaded) return;
            const ratio = this.getBufferedRatio(video);
            // When > 50% buffer loaded, or browser has full playable data
            if (ratio >= 0.50 || video.readyState >= 4) {
              triggerCrossfadeAndPlay();
            }
          };

          // Attach loading buffer listeners once per video element
          if (!video._hasBufferTracking) {
            video._hasBufferTracking = true;
            video.addEventListener('progress', checkBuffer);
            video.addEventListener('canplay', checkBuffer);
            video.addEventListener('canplaythrough', checkBuffer);
            video.addEventListener('loadeddata', checkBuffer);
          }

          // If this video has already crossfaded (e.g. revisited slide), play immediately
          if (video._hasCrossfaded) {
            video.classList.add('is-ready');
            if (poster) poster.classList.add('is-hidden');
            video.muted = true;
            if (!this.options.advanceOnVideoEnd) {
              video.loop = true;
            }
            video.play().catch(() => {});
          } else {
            // Check immediately in case video is already cached or fast-loaded
            checkBuffer();

            // Periodic buffer polling fallback in case browser progress events are throttled
            if (video._bufferInterval) clearInterval(video._bufferInterval);
            video._bufferInterval = setInterval(() => {
              if (video._hasCrossfaded || this.slides[this.currentIndex] !== slide) {
                clearInterval(video._bufferInterval);
                video._bufferInterval = null;
                return;
              }
              checkBuffer();
            }, 100);
          }
        } else {
          // Pause inactive slide video
          if (video._bufferInterval) {
            clearInterval(video._bufferInterval);
            video._bufferInterval = null;
          }
          video.pause();
        }
      });

      // Synchronize Autoplay Timer:
      // If the active slide has a video and advanceOnVideoEnd is enabled,
      // pause generic autoplay timer so it does not interrupt the video before it finishes.
      // If the active slide has NO video (e.g. image slide) and autoplay is enabled,
      // resume the standard autoplay timer.
      if (activeVideo && this.options.advanceOnVideoEnd) {
        this.stopAutoplay();
      } else if (this.options.autoplay) {
        this.startAutoplay();
      }
    }

    updateAnnouncer() {
      if (this.announcer) {
        this.announcer.textContent = `Slide ${this.currentIndex + 1} of ${this.totalSlides}`;
      }
    }

    startAutoplay() {
      this.stopAutoplay();
      this.autoplayTimer = setInterval(() => {
        // Without loop, autoplay stops on the last slide instead of getting stuck there.
        if (!this.options.loop && this.currentIndex >= this.totalSlides - 1) {
          this.stopAutoplay();
          return;
        }
        if (!this.isPaused && !this.isDragging) {
          this.next();
        }
      }, this.options.autoplaySpeed);
    }

    stopAutoplay() {
      if (this.autoplayTimer) {
        clearInterval(this.autoplayTimer);
        this.autoplayTimer = null;
      }
    }

    resetAutoplayTimer() {
      if (this.options.autoplay) {
        this.startAutoplay();
      }
    }

    debounce(func, wait) {
      let timeout;
      return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
      };
    }
  }

  // Auto-initialize all .js-carousel on DOMContentLoaded
  function initAllCarousels() {
    const carousels = document.querySelectorAll('.js-carousel');
    carousels.forEach((el) => {
      if (!el.deepMindCarouselInstance) {
        el.deepMindCarouselInstance = new DeepMindCarousel(el);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllCarousels);
  } else {
    initAllCarousels();
  }

  // Expose constructor to global for programmatic usage
  window.DeepMindCarousel = DeepMindCarousel;
})();
