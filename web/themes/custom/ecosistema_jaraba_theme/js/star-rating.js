/**
 * @file
 * Star Rating Input — Interactive star selection widget.
 *
 * Trigger: Elements with [data-star-rating-input] attribute.
 * Accessible: keyboard navigation, ARIA attributes, reduced motion.
 *
 * REV-PHASE5: Widget interactivo de estrellas.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.jarabaStarRating = {
    attach: function (context) {
      var containers = context.querySelectorAll('[data-star-rating-input]:not(.star-rating-processed)');

      containers.forEach(function (container) {
        container.classList.add('star-rating-processed');

        var hiddenInput = container.querySelector('input[type="hidden"]');
        if (!hiddenInput) {
          hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = container.getAttribute('data-field-name') || 'rating';
          hiddenInput.value = '0';
          container.appendChild(hiddenInput);
        }

        var currentRating = parseInt(hiddenInput.value, 10) || 0;
        var starsContainer = document.createElement('div');
        starsContainer.className = 'star-rating-input__stars';
        starsContainer.setAttribute('role', 'radiogroup');
        starsContainer.setAttribute('aria-label', Drupal.t('Seleccionar valoracion'));

        var ratingText = document.createElement('span');
        ratingText.className = 'star-rating-input__text';

        var stars = [];

        for (var i = 1; i <= 5; i++) {
          var star = document.createElement('button');
          star.type = 'button';
          star.className = 'star-rating-input__star';
          star.setAttribute('data-value', i);
          star.setAttribute('role', 'radio');
          star.setAttribute('aria-checked', i <= currentRating ? 'true' : 'false');
          star.setAttribute('aria-label', Drupal.t('@count estrellas', { '@count': i }));
          star.setAttribute('tabindex', i === 1 ? '0' : '-1');
          star.textContent = i <= currentRating ? '\u2605' : '\u2606';
          stars.push(star);
          starsContainer.appendChild(star);
        }

        container.insertBefore(starsContainer, container.firstChild);
        container.appendChild(ratingText);

        function updateStars() {
          stars.forEach(function (star, index) {
            var value = index + 1;
            var isActive = value <= currentRating;
            star.textContent = isActive ? '\u2605' : '\u2606';
            star.setAttribute('aria-checked', isActive ? 'true' : 'false');
            star.classList.toggle('star-rating-input__star--active', isActive);
          });

          hiddenInput.value = currentRating.toString();

          if (currentRating > 0) {
            ratingText.textContent = Drupal.t('@count de 5', { '@count': currentRating });
          } else {
            ratingText.textContent = Drupal.t('Sin valorar');
          }
        }

        // Click handler.
        starsContainer.addEventListener('click', function (e) {
          var target = e.target.closest('.star-rating-input__star');
          if (target) {
            currentRating = parseInt(target.getAttribute('data-value'), 10);
            updateStars();

            // Focus management.
            target.focus();
            stars.forEach(function (s) { s.setAttribute('tabindex', '-1'); });
            target.setAttribute('tabindex', '0');
          }
        });

        // Keyboard handler.
        starsContainer.addEventListener('keydown', function (e) {
          var target = e.target.closest('.star-rating-input__star');
          if (!target) return;

          var currentIndex = stars.indexOf(target);
          var newIndex = currentIndex;

          if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            e.preventDefault();
            newIndex = Math.min(currentIndex + 1, 4);
          } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            e.preventDefault();
            newIndex = Math.max(currentIndex - 1, 0);
          } else if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            currentRating = parseInt(target.getAttribute('data-value'), 10);
            updateStars();
            return;
          }

          if (newIndex !== currentIndex) {
            stars.forEach(function (s) { s.setAttribute('tabindex', '-1'); });
            stars[newIndex].setAttribute('tabindex', '0');
            stars[newIndex].focus();

            currentRating = newIndex + 1;
            updateStars();
          }
        });

        // Initial state.
        updateStars();
      });
    }
  };

})(Drupal);
