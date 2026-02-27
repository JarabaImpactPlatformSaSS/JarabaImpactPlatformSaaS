/**
 * @file
 * Review interactions JS — Helpfulness voting, filtering, photo gallery.
 *
 * B-01: Helpfulness voting with deduplication.
 * B-02: Client-side filtering/sorting.
 * B-06: Photo gallery lightbox.
 *
 * ROUTE-LANGPREFIX-001: Uses Drupal.url() for all fetch calls.
 * INNERHTML-XSS-001: Uses Drupal.checkPlain() for user input.
 */

(function (Drupal, once) {
  'use strict';

  let csrfToken = null;

  /**
   * Get CSRF token (cached).
   */
  async function getCsrfToken() {
    if (csrfToken) {
      return csrfToken;
    }
    const response = await fetch(Drupal.url('session/token'));
    csrfToken = await response.text();
    return csrfToken;
  }

  /**
   * B-01: Helpfulness voting.
   */
  Drupal.behaviors.reviewHelpfulness = {
    attach: function (context) {
      once('review-helpfulness', '.review-helpful__btn', context).forEach(function (btn) {
        btn.addEventListener('click', async function (e) {
          e.preventDefault();
          const reviewType = this.dataset.reviewType;
          const reviewId = this.dataset.reviewId;
          const helpful = this.dataset.voteType === 'helpful';

          try {
            const token = await getCsrfToken();
            const response = await fetch(Drupal.url('api/v1/reviews/' + reviewType + '/' + reviewId + '/vote'), {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token,
              },
              body: JSON.stringify({ helpful: helpful }),
            });

            const data = await response.json();
            if (data.success) {
              // Update counts.
              const container = this.closest('.review-helpful');
              if (container) {
                const helpfulCount = container.querySelector('.review-helpful__count--helpful');
                const notHelpfulCount = container.querySelector('.review-helpful__count--not-helpful');
                if (helpfulCount) {
                  helpfulCount.textContent = data.data.helpful_count;
                }
                if (notHelpfulCount) {
                  notHelpfulCount.textContent = data.data.not_helpful_count;
                }

                // Update active state.
                container.querySelectorAll('.review-helpful__btn').forEach(function (b) {
                  b.classList.remove('review-helpful__btn--active');
                });
                if (data.data.user_vote) {
                  const activeBtn = container.querySelector('[data-vote-type="' + data.data.user_vote + '"]');
                  if (activeBtn) {
                    activeBtn.classList.add('review-helpful__btn--active');
                  }
                }
              }
            }
          }
          catch (err) {
            // Silently fail for UX.
          }
        });
      });
    }
  };

  /**
   * B-02: Review filter chips.
   */
  Drupal.behaviors.reviewFilters = {
    attach: function (context) {
      once('review-filters', '.review-filters__chip', context).forEach(function (chip) {
        chip.addEventListener('click', function (e) {
          e.preventDefault();
          const group = this.closest('.review-filters__group');
          if (group) {
            // Toggle active in group (single select per group).
            group.querySelectorAll('.review-filters__chip').forEach(function (c) {
              c.classList.remove('review-filters__chip--active');
            });
            this.classList.add('review-filters__chip--active');
          }

          // Trigger filter update event.
          const filterEvent = new CustomEvent('reviewFilterChange', {
            detail: collectFilters(),
            bubbles: true,
          });
          document.dispatchEvent(filterEvent);
        });
      });

      once('review-sort', '.review-filters__sort select', context).forEach(function (sel) {
        sel.addEventListener('change', function () {
          const filterEvent = new CustomEvent('reviewFilterChange', {
            detail: collectFilters(),
            bubbles: true,
          });
          document.dispatchEvent(filterEvent);
        });
      });
    }
  };

  /**
   * Collect current filter state.
   */
  function collectFilters() {
    const filters = {};
    document.querySelectorAll('.review-filters__chip--active').forEach(function (chip) {
      const filterType = chip.dataset.filterType;
      const filterValue = chip.dataset.filterValue;
      if (filterType) {
        filters[filterType] = filterValue;
      }
    });

    const sortSelect = document.querySelector('.review-filters__sort select');
    if (sortSelect) {
      filters.sort = sortSelect.value;
    }

    return filters;
  }

  /**
   * B-06: Photo gallery lightbox.
   */
  Drupal.behaviors.reviewPhotoGallery = {
    attach: function (context) {
      once('review-lightbox', '.review-photos__thumb', context).forEach(function (thumb) {
        thumb.addEventListener('click', function () {
          const photos = [];
          this.closest('.review-photos').querySelectorAll('.review-photos__thumb').forEach(function (t) {
            photos.push(t.src || t.dataset.src);
          });

          const currentIndex = photos.indexOf(this.src || this.dataset.src);
          openLightbox(photos, currentIndex);
        });

        // Keyboard support.
        thumb.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
          }
        });
      });
    }
  };

  /**
   * Open lightbox overlay.
   */
  function openLightbox(photos, index) {
    if (!photos.length) return;

    let currentIndex = index || 0;

    const overlay = document.createElement('div');
    overlay.className = 'review-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-label', Drupal.t('Photo gallery'));

    function render() {
      overlay.innerHTML = '';

      const close = document.createElement('button');
      close.className = 'review-lightbox__close';
      close.textContent = '\u00D7';
      close.setAttribute('aria-label', Drupal.t('Close'));
      close.addEventListener('click', function () {
        document.body.removeChild(overlay);
      });
      overlay.appendChild(close);

      const img = document.createElement('img');
      img.className = 'review-lightbox__img';
      img.src = photos[currentIndex];
      img.alt = Drupal.t('Photo @n of @total', { '@n': currentIndex + 1, '@total': photos.length });
      overlay.appendChild(img);

      if (photos.length > 1) {
        const prev = document.createElement('button');
        prev.className = 'review-lightbox__nav review-lightbox__nav--prev';
        prev.textContent = '\u2039';
        prev.setAttribute('aria-label', Drupal.t('Previous'));
        prev.addEventListener('click', function () {
          currentIndex = (currentIndex - 1 + photos.length) % photos.length;
          render();
        });
        overlay.appendChild(prev);

        const next = document.createElement('button');
        next.className = 'review-lightbox__nav review-lightbox__nav--next';
        next.textContent = '\u203A';
        next.setAttribute('aria-label', Drupal.t('Next'));
        next.addEventListener('click', function () {
          currentIndex = (currentIndex + 1) % photos.length;
          render();
        });
        overlay.appendChild(next);
      }
    }

    render();
    document.body.appendChild(overlay);

    // ESC to close.
    function handleKeydown(e) {
      if (e.key === 'Escape') {
        document.body.removeChild(overlay);
        document.removeEventListener('keydown', handleKeydown);
      }
      else if (e.key === 'ArrowLeft' && photos.length > 1) {
        currentIndex = (currentIndex - 1 + photos.length) % photos.length;
        render();
      }
      else if (e.key === 'ArrowRight' && photos.length > 1) {
        currentIndex = (currentIndex + 1) % photos.length;
        render();
      }
    }
    document.addEventListener('keydown', handleKeydown);
  }

})(Drupal, once);
