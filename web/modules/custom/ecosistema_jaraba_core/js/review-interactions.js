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
   * B-02b: Fetch filtered reviews from API and re-render.
   *
   * Listens for 'reviewFilterChange' events dispatched by filter chips
   * and fetches from /api/v1/reviews/{type}/list with query params.
   */
  Drupal.behaviors.reviewFilterFetch = {
    attach: function (context) {
      once('review-filter-fetch', '[data-reviews-page]', context).forEach(function (container) {
        var reviewType = container.dataset.reviewType;
        var targetId = container.dataset.targetId;
        var listEl = container.querySelector('.reviews-page__list');

        if (!reviewType || !listEl) {
          return;
        }

        document.addEventListener('reviewFilterChange', function (e) {
          var filters = e.detail || {};
          var params = new URLSearchParams();

          if (targetId) {
            params.set('target_id', targetId);
          }
          if (filters.stars) {
            params.set('stars', filters.stars);
          }
          if (filters.verified) {
            params.set('verified', '1');
          }
          if (filters.has_photos) {
            params.set('has_photos', '1');
          }
          if (filters.sort) {
            params.set('sort', filters.sort);
          }
          params.set('limit', '10');

          listEl.classList.add('reviews-page__list--loading');

          fetch(Drupal.url('api/v1/reviews/' + reviewType + '/list') + '?' + params.toString())
            .then(function (response) { return response.json(); })
            .then(function (result) {
              listEl.classList.remove('reviews-page__list--loading');
              if (!result.success || !result.data) {
                return;
              }
              renderFilteredReviews(listEl, result.data);
            })
            .catch(function () {
              listEl.classList.remove('reviews-page__list--loading');
            });
        });
      });
    }
  };

  /**
   * Render filtered reviews into the list container.
   */
  function renderFilteredReviews(container, reviews) {
    if (reviews.length === 0) {
      container.innerHTML = '<div class="reviews-page__empty"><p>' +
        Drupal.t('No hay resenas que coincidan con los filtros.') + '</p></div>';
      return;
    }

    var html = '';
    reviews.forEach(function (r) {
      html += '<div role="listitem">';
      html += '<article class="review-card" data-review-id="' + r.id + '">';

      // Header.
      html += '<header class="review-card__header">';
      html += '<div class="review-card__author">';
      html += '<span class="review-card__avatar review-card__avatar--placeholder" aria-hidden="true">';
      html += Drupal.checkPlain((r.author || 'A').charAt(0).toUpperCase());
      html += '</span>';
      html += '<div>';
      html += '<span class="review-card__author-name">' + Drupal.checkPlain(r.author || '') + '</span>';
      if (r.created) {
        var d = new Date(r.created * 1000);
        var dateStr = d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        html += '<time class="review-card__date">' + dateStr + '</time>';
      }
      html += '</div></div>';

      if (r.verified_purchase) {
        html += '<span class="review-card__badge review-card__badge--verified">' + Drupal.t('Compra verificada') + '</span>';
      }
      html += '</header>';

      // Rating stars.
      if (r.rating > 0) {
        html += '<div class="star-rating star-rating--sm">';
        for (var s = 1; s <= 5; s++) {
          html += s <= r.rating ? '★' : '☆';
        }
        html += '</div>';
      }

      // Title.
      if (r.title) {
        html += '<h4 class="review-card__title">' + Drupal.checkPlain(r.title) + '</h4>';
      }

      // Body.
      if (r.body) {
        html += '<div class="review-card__body">' + Drupal.checkPlain(r.body) + '</div>';
      }

      // Photos.
      if (r.photos && r.photos.length > 0) {
        html += '<div class="review-card__photos">';
        r.photos.forEach(function (photo) {
          html += '<img class="review-card__photo review-photos__thumb" src="' + Drupal.checkPlain(photo) + '" alt="' + Drupal.t('Foto de la resena') + '" loading="lazy" width="120" height="120">';
        });
        html += '</div>';
      }

      // Footer.
      html += '<footer class="review-card__footer">';
      html += '<button type="button" class="review-helpful__btn" data-review-type="' + Drupal.checkPlain(document.querySelector('[data-reviews-page]').dataset.reviewType || '') + '" data-review-id="' + r.id + '" data-vote-type="helpful">';
      html += '<span aria-hidden="true">👍</span> ' + Drupal.t('Util') + ' (' + (r.helpful_count || 0) + ')';
      html += '</button>';
      html += '</footer>';

      // Owner response.
      if (r.response) {
        html += '<div class="review-card__response">';
        html += '<p class="review-card__response-body">' + Drupal.checkPlain(r.response) + '</p>';
        html += '</div>';
      }

      html += '</article></div>';
    });

    container.innerHTML = html;

    // Re-attach Drupal behaviors for the new elements.
    Drupal.attachBehaviors(container);
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
