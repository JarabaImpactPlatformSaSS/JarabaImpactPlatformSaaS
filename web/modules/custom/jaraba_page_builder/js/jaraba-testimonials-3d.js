/**
 * @file
 * Jaraba Testimonials 3D Carousel interactivity.
 *
 * Implements the coverflow 3D carousel navigation for the Testimonials 3D
 * premium block. Follows the Dual Architecture pattern:
 * - This file: Drupal.behaviors for public pages
 * - GrapesJS script function: inline in grapesjs-jaraba-blocks.js
 *
 * Features:
 * - Coverflow 3D effect with CSS transforms
 * - Prev/Next navigation with smooth transitions
 * - Autoplay with configurable speed
 * - Pause on hover
 * - Keyboard navigation (Left/Right arrows)
 * - ARIA live region for screen readers
 * - Touch swipe support for mobile
 *
 * @see docs/arquitectura/2026-02-05_especificacion_grapesjs_saas.md §4.4
 * @see https://grapesjs.com/docs/modules/Components-js.html
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Calculate 3D transform properties for a card at given offset from center.
   *
   * @param {number} offset - Position relative to active card (0 = center).
   * @param {number} totalCards - Total number of cards in the carousel.
   * @return {Object} Transform properties: { transform, opacity, zIndex, pointerEvents }.
   */
  function getCardTransform(offset, totalCards) {
    var absOffset = Math.abs(offset);

    if (absOffset === 0) {
      // Center card — prominent
      return {
        transform: 'translateZ(40px) scale(1.05)',
        opacity: '1',
        zIndex: totalCards + 1,
        pointerEvents: 'auto',
      };
    }

    // Side cards — rotated in 3D
    var direction = offset > 0 ? -1 : 1;
    var translateX = direction * 40;
    var rotateY = direction * -25;
    var scale = Math.max(0.85 - (absOffset - 1) * 0.1, 0.6);
    var opacity = Math.max(0.6 - (absOffset - 1) * 0.2, 0.15);

    return {
      transform: 'translateX(' + translateX + 'px) rotateY(' + rotateY + 'deg) scale(' + scale + ')',
      opacity: String(opacity),
      zIndex: totalCards - absOffset,
      pointerEvents: 'none',
    };
  }

  /**
   * Initialize a single Testimonials 3D carousel instance.
   *
   * @param {HTMLElement} section - The .jaraba-testimonials-3d container element.
   */
  function initTestimonials3D(section) {
    var carousel = section.querySelector('.jaraba-testimonials-3d__carousel');
    var cards = section.querySelectorAll('.jaraba-testimonial-3d');
    var prevBtn = section.querySelector('.jaraba-testimonials-3d__prev');
    var nextBtn = section.querySelector('.jaraba-testimonials-3d__next');

    if (!carousel || cards.length === 0) {
      return;
    }

    // Read configuration from data attributes
    var autoplay = section.dataset.autoplay === 'true';
    var autoplaySpeed = parseInt(section.dataset.autoplaySpeed, 10) || 5000;
    var totalCards = cards.length;

    var currentIndex = 0;
    var autoplayInterval = null;
    var touchStartX = 0;
    var touchEndX = 0;

    // Set ARIA attributes on carousel for accessibility
    carousel.setAttribute('role', 'region');
    carousel.setAttribute('aria-roledescription', 'carousel');
    carousel.setAttribute('aria-label',
      section.querySelector('.jaraba-block__title')
        ? section.querySelector('.jaraba-block__title').textContent.trim()
        : 'Testimonios'
    );

    // Create live region for screen reader announcements
    var liveRegion = document.createElement('div');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.className = 'sr-only';
    liveRegion.style.cssText = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;';
    section.appendChild(liveRegion);

    // Set ARIA on each card
    cards.forEach(function (card, i) {
      card.setAttribute('role', 'group');
      card.setAttribute('aria-roledescription', 'slide');
      card.setAttribute('aria-label', 'Testimonio ' + (i + 1) + ' de ' + totalCards);
    });

    /**
     * Navigate to a specific card index.
     *
     * @param {number} index - Target card index (wraps around).
     */
    function goToCard(index) {
      // Wrap around
      if (index < 0) {
        index = totalCards - 1;
      }
      if (index >= totalCards) {
        index = 0;
      }

      currentIndex = index;

      // Apply 3D transforms to each card
      cards.forEach(function (card, i) {
        var offset = i - currentIndex;
        var props = getCardTransform(offset, totalCards);

        card.style.transform = props.transform;
        card.style.opacity = props.opacity;
        card.style.zIndex = props.zIndex;
        card.style.pointerEvents = props.pointerEvents;

        // ARIA: mark active card
        card.setAttribute('aria-hidden', offset !== 0 ? 'true' : 'false');
      });

      // Announce change to screen readers
      var activeCard = cards[currentIndex];
      var authorEl = activeCard.querySelector('.jaraba-testimonial-3d__author');
      if (authorEl && liveRegion) {
        liveRegion.textContent = 'Testimonio de ' + authorEl.textContent + ', ' + (currentIndex + 1) + ' de ' + totalCards;
      }
    }

    /** Navigate to next card. */
    function nextCard() {
      goToCard(currentIndex + 1);
    }

    /** Navigate to previous card. */
    function prevCard() {
      goToCard(currentIndex - 1);
    }

    /** Start autoplay rotation. */
    function startAutoplay() {
      if (autoplay && !autoplayInterval) {
        autoplayInterval = setInterval(nextCard, autoplaySpeed);
      }
    }

    /** Stop autoplay rotation. */
    function stopAutoplay() {
      if (autoplayInterval) {
        clearInterval(autoplayInterval);
        autoplayInterval = null;
      }
    }

    // --- Event Listeners ---

    // Prev/Next buttons
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        stopAutoplay();
        prevCard();
        startAutoplay();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        stopAutoplay();
        nextCard();
        startAutoplay();
      });
    }

    // Keyboard navigation
    section.setAttribute('tabindex', '0');
    section.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft' || e.key === 'Left') {
        e.preventDefault();
        stopAutoplay();
        prevCard();
        startAutoplay();
      } else if (e.key === 'ArrowRight' || e.key === 'Right') {
        e.preventDefault();
        stopAutoplay();
        nextCard();
        startAutoplay();
      }
    });

    // Pause autoplay on hover
    section.addEventListener('mouseenter', stopAutoplay);
    section.addEventListener('mouseleave', startAutoplay);

    // Touch swipe support
    carousel.addEventListener('touchstart', function (e) {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    carousel.addEventListener('touchend', function (e) {
      touchEndX = e.changedTouches[0].screenX;
      var diffX = touchStartX - touchEndX;

      if (Math.abs(diffX) > 50) {
        stopAutoplay();
        if (diffX > 0) {
          nextCard();
        } else {
          prevCard();
        }
        startAutoplay();
      }
    }, { passive: true });

    // --- Initialize ---
    goToCard(0);
    startAutoplay();
  }

  /**
   * Initialize all Testimonials 3D carousels in the given context.
   *
   * @param {Element} context - The DOM context to search within.
   */
  function initAllTestimonials3D(context) {
    once('testimonials3d', '.jaraba-testimonials-3d', context).forEach(initTestimonials3D);
  }

  // Drupal behavior for public pages (Dual Architecture - public side)
  Drupal.behaviors.jarabaTestimonials3D = {
    attach: function (context) {
      initAllTestimonials3D(context);
    },
  };

  // Initialize immediately for non-Drupal contexts (GrapesJS iframe)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initAllTestimonials3D(document);
    });
  } else {
    initAllTestimonials3D(document);
  }

  // Expose for GrapesJS to call in iframe
  window.jarabaInitTestimonials3D = initAllTestimonials3D;

})(Drupal, once);
