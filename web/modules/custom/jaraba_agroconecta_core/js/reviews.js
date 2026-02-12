/**
 * @file
 * Reviews Widget - AgroConecta
 *
 * Componente frontend para reseñas:
 * - Widget de estrellas (display + interactivo)
 * - Formulario de envío de reseñas
 * - Listado de reseñas con paginación
 * - Respuesta del productor
 */
(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Genera HTML de estrellas según rating.
     */
    function renderStars(rating, interactive) {
        var html = '<div class="agro-stars' + (interactive ? ' agro-stars--interactive' : '') + '" data-rating="' + rating + '">';
        for (var i = 1; i <= 5; i++) {
            var cls = i <= rating ? 'agro-stars__star--filled' : 'agro-stars__star--empty';
            html += '<span class="agro-stars__star ' + cls + '" data-value="' + i + '">★</span>';
        }
        html += '</div>';
        return html;
    }

    /**
     * Genera HTML del badge de compra verificada.
     */
    function verifiedBadge(verified) {
        if (!verified) return '';
        return '<span class="agro-review__verified">' + Drupal.t('Compra verificada') + '</span>';
    }

    /**
     * Renderiza una reseña individual.
     */
    function renderReview(review) {
        var html = '<div class="agro-review">';
        html += '<div class="agro-review__header">';
        html += '<div class="agro-review__author">' + Drupal.checkPlain(review.author.name) + '</div>';
        html += renderStars(review.rating, false);
        html += verifiedBadge(review.verified_purchase);
        html += '<time class="agro-review__date">' + new Date(review.created).toLocaleDateString('es-ES') + '</time>';
        html += '</div>';

        if (review.title) {
            html += '<h4 class="agro-review__title">' + Drupal.checkPlain(review.title) + '</h4>';
        }
        html += '<p class="agro-review__body">' + Drupal.checkPlain(review.body) + '</p>';

        // Respuesta del productor.
        if (review.response) {
            html += '<div class="agro-review__response">';
            html += '<strong class="agro-review__response-label">' + Drupal.t('Respuesta del productor') + '</strong>';
            html += '<p>' + Drupal.checkPlain(review.response.body) + '</p>';
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    /**
     * Renderiza las estadísticas de rating.
     */
    function renderRatingStats(container, stats) {
        var html = '<div class="agro-rating-summary">';
        html += '<div class="agro-rating-summary__score">';
        html += '<span class="agro-rating-summary__average">' + stats.average.toFixed(1) + '</span>';
        html += renderStars(Math.round(stats.average), false);
        html += '<span class="agro-rating-summary__count">' + stats.count + ' ' + Drupal.t('reseñas') + '</span>';
        html += '</div>';
        html += '<div class="agro-rating-summary__bars">';

        for (var star = 5; star >= 1; star--) {
            var count = stats.distribution[star] || 0;
            var pct = stats.count > 0 ? Math.round((count / stats.count) * 100) : 0;
            html += '<div class="agro-rating-bar">';
            html += '<span class="agro-rating-bar__label">' + star + '★</span>';
            html += '<div class="agro-rating-bar__track">';
            html += '<div class="agro-rating-bar__fill" style="width: ' + pct + '%"></div>';
            html += '</div>';
            html += '<span class="agro-rating-bar__count">' + count + '</span>';
            html += '</div>';
        }

        html += '</div></div>';
        container.innerHTML = html;
    }

    Drupal.behaviors.agroconectaReviews = {
        attach: function (context) {

            // === Star Rating Widgets (display) ===
            var starWidgets = once('star-rating', '[data-agro-rating]', context);
            starWidgets.forEach(function (el) {
                var rating = parseInt(el.dataset.agroRating, 10) || 0;
                el.innerHTML = renderStars(rating, false);
            });

            // === Rating Stats Containers ===
            var statsContainers = once('rating-stats', '[data-agro-stats]', context);
            statsContainers.forEach(function (container) {
                var targetType = container.dataset.agroTargetType;
                var targetId = container.dataset.agroTargetId;
                if (!targetType || !targetId) return;

                fetch('/api/v1/agro/reviews/stats?target_type=' + targetType + '&target_id=' + targetId)
                    .then(function (r) { return r.json(); })
                    .then(function (resp) {
                        if (resp.data) {
                            renderRatingStats(container, resp.data);
                        }
                    })
                    .catch(function () {
                        container.innerHTML = '<p class="agro-reviews__error">' + Drupal.t('Error al cargar estadísticas.') + '</p>';
                    });
            });

            // === Reviews List ===
            var reviewLists = once('review-list', '[data-agro-reviews]', context);
            reviewLists.forEach(function (container) {
                var targetType = container.dataset.agroTargetType;
                var targetId = container.dataset.agroTargetId;
                var limit = parseInt(container.dataset.agroLimit, 10) || 10;
                if (!targetType || !targetId) return;

                container.innerHTML = '<div class="agro-reviews__loading"><span class="agro-spinner"></span> ' + Drupal.t('Cargando reseñas...') + '</div>';

                fetch('/api/v1/agro/reviews?target_type=' + targetType + '&target_id=' + targetId + '&limit=' + limit)
                    .then(function (r) { return r.json(); })
                    .then(function (resp) {
                        if (!resp.data || resp.data.length === 0) {
                            container.innerHTML = '<p class="agro-reviews__empty">' + Drupal.t('Aún no hay reseñas. ¡Sé el primero en opinar!') + '</p>';
                            return;
                        }
                        var html = '';
                        resp.data.forEach(function (review) {
                            html += renderReview(review);
                        });

                        // Paginación.
                        if (resp.meta && resp.meta.total > resp.data.length) {
                            html += '<div class="agro-reviews__pagination">';
                            html += '<span>' + Drupal.t('Mostrando @shown de @total', {
                                '@shown': resp.data.length,
                                '@total': resp.meta.total,
                            }) + '</span>';
                            html += '</div>';
                        }
                        container.innerHTML = html;
                    })
                    .catch(function () {
                        container.innerHTML = '<p class="agro-reviews__error">' + Drupal.t('Error al cargar reseñas.') + '</p>';
                    });
            });

            // === Review Submit Form ===
            var reviewForms = once('review-form', '[data-agro-review-form]', context);
            reviewForms.forEach(function (form) {
                var selectedRating = 0;
                var starsContainer = form.querySelector('.agro-stars--interactive');

                if (starsContainer) {
                    var stars = starsContainer.querySelectorAll('.agro-stars__star');
                    stars.forEach(function (star) {
                        // Hover.
                        star.addEventListener('mouseenter', function () {
                            var val = parseInt(this.dataset.value, 10);
                            stars.forEach(function (s) {
                                var sv = parseInt(s.dataset.value, 10);
                                s.classList.toggle('agro-stars__star--filled', sv <= val);
                                s.classList.toggle('agro-stars__star--empty', sv > val);
                            });
                        });

                        // Click.
                        star.addEventListener('click', function () {
                            selectedRating = parseInt(this.dataset.value, 10);
                            form.querySelector('[name="rating"]').value = selectedRating;
                        });
                    });

                    // Mouse leave: restore.
                    starsContainer.addEventListener('mouseleave', function () {
                        stars.forEach(function (s) {
                            var sv = parseInt(s.dataset.value, 10);
                            s.classList.toggle('agro-stars__star--filled', sv <= selectedRating);
                            s.classList.toggle('agro-stars__star--empty', sv > selectedRating);
                        });
                    });
                }

                // Submit.
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var btn = form.querySelector('[type="submit"]');
                    var feedback = form.querySelector('.agro-review-form__feedback');

                    if (selectedRating === 0) {
                        if (feedback) feedback.textContent = Drupal.t('Por favor, selecciona una puntuación.');
                        return;
                    }

                    btn.disabled = true;
                    btn.textContent = Drupal.t('Enviando...');

                    var payload = {
                        type: form.dataset.agroType || 'product',
                        target_entity_type: form.dataset.agroTargetType,
                        target_entity_id: parseInt(form.dataset.agroTargetId, 10),
                        rating: selectedRating,
                        title: (form.querySelector('[name="title"]') || {}).value || '',
                        body: form.querySelector('[name="body"]').value,
                    };

                    fetch('/api/v1/agro/reviews', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.success) {
                                form.innerHTML = '<div class="agro-review-form__success">'
                                    + '<span class="agro-review-form__icon" aria-hidden="true"></span>'
                                    + '<p>' + Drupal.t('¡Gracias por tu reseña! Será visible después de moderación.') + '</p>'
                                    + '</div>';
                            } else {
                                if (feedback) feedback.textContent = data.error || Drupal.t('Error al enviar la reseña.');
                                btn.disabled = false;
                                btn.textContent = Drupal.t('Enviar reseña');
                            }
                        })
                        .catch(function () {
                            if (feedback) feedback.textContent = Drupal.t('Error de conexión.');
                            btn.disabled = false;
                            btn.textContent = Drupal.t('Enviar reseña');
                        });
                });
            });

            // === Animaciones de entrada ===
            var reviewCards = once('review-animate', '.agro-review, .agro-rating-summary', context);
            reviewCards.forEach(function (card, index) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(10px)';
                setTimeout(function () {
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
        }
    };

})(Drupal, drupalSettings, once);
