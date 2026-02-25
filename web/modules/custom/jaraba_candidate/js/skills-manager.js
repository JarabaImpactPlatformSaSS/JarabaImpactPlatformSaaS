/**
 * @file
 * Skills Manager - Interactive UI for managing candidate skills.
 */

(function (Drupal) {
    'use strict';

    // Cache CSRF token for reuse across requests.
    var csrfTokenPromise = null;
    function getCsrfToken() {
        if (!csrfTokenPromise) {
            csrfTokenPromise = fetch('/session/token')
                .then(function (response) { return response.text(); });
        }
        return csrfTokenPromise;
    }

    Drupal.behaviors.skillsManager = {
        attach: function (context) {
            // Accordion toggle
            const categoryHeaders = context.querySelectorAll('.category-header');
            categoryHeaders.forEach(function (header) {
                if (header.dataset.initialized) return;
                header.dataset.initialized = 'true';

                header.addEventListener('click', function () {
                    const expanded = this.getAttribute('aria-expanded') === 'true';
                    this.setAttribute('aria-expanded', !expanded);

                    const content = this.nextElementSibling;
                    if (content) {
                        content.hidden = expanded;
                    }
                });
            });

            // Add skill buttons
            const addButtons = context.querySelectorAll('.btn-add-skill');
            addButtons.forEach(function (btn) {
                if (btn.dataset.initialized) return;
                btn.dataset.initialized = 'true';

                btn.addEventListener('click', function () {
                    const skillId = this.dataset.skillId;
                    const skillName = this.dataset.skillName;

                    // Disable button during request
                    this.disabled = true;
                    this.textContent = Drupal.t('Adding...');

                    // AJAX request to add skill
                    getCsrfToken().then(function (token) {
                    fetch('/api/v1/profile/skills', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': token,
                        },
                        body: JSON.stringify({
                            skill_id: skillId,
                            level: 'intermediate',
                            years_experience: 0
                        }),
                        credentials: 'same-origin'
                    })
                        .then(function (response) {
                            if (response.ok) {
                                return response.json();
                            }
                            throw new Error(Drupal.t('Failed to add skill'));
                        })
                        .then(function (data) {
                            // Mark add button as added (no page reload).
                            btn.textContent = Drupal.t('Añadido');
                            btn.classList.add('btn-add-skill--added');

                            // Insert new skill card into the grid.
                            var grid = document.querySelector('.skills-grid');
                            if (!grid) {
                                // Create grid if empty state was showing.
                                var emptyState = document.querySelector('.current-skills .empty-state');
                                if (emptyState) {
                                    grid = document.createElement('div');
                                    grid.className = 'skills-grid';
                                    emptyState.parentNode.replaceChild(grid, emptyState);
                                }
                            }
                            if (grid) {
                                var card = document.createElement('div');
                                card.className = 'skill-card';
                                card.setAttribute('data-skill-id', data.id || '');
                                card.innerHTML = '<div class="skill-info">'
                                    + '<span class="skill-name">' + Drupal.checkPlain(skillName) + '</span>'
                                    + '<span class="skill-level skill-level--intermediate">' + Drupal.checkPlain(Drupal.t('Intermedio')) + '</span>'
                                    + '</div>'
                                    + '<button type="button" class="btn-remove-skill" data-candidate-skill-id="' + Drupal.checkPlain(String(data.id || '')) + '" title="' + Drupal.checkPlain(Drupal.t('Eliminar')) + '">'
                                    + '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>'
                                    + '</button>';
                                grid.appendChild(card);
                                // Attach behaviors on the new card so remove button works.
                                Drupal.attachBehaviors(card);
                            }

                            // Update badge count.
                            var badge = document.querySelector('.current-skills .badge');
                            if (badge) {
                                var count = parseInt(badge.textContent, 10) + 1;
                                badge.textContent = count;
                            }
                        })
                        .catch(function (error) {
                            console.error('Error adding skill:', error);
                            btn.disabled = false;
                            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> ' + Drupal.checkPlain(skillName);
                            alert(Drupal.t('Error adding skill. Please try again.'));
                        });
                    }); // getCsrfToken
                });
            });

            // Remove skill buttons
            const removeButtons = context.querySelectorAll('.btn-remove-skill');
            removeButtons.forEach(function (btn) {
                if (btn.dataset.initialized) return;
                btn.dataset.initialized = 'true';

                btn.addEventListener('click', function () {
                    const skillEntityId = this.dataset.candidateSkillId;
                    const card = this.closest('.skill-card');

                    if (!confirm(Drupal.t('Are you sure you want to remove this skill?'))) {
                        return;
                    }

                    // Visual feedback
                    if (card) card.style.opacity = '0.5';

                    // AJAX request to remove skill
                    getCsrfToken().then(function (token) {
                    fetch('/api/v1/profile/skills/' + skillEntityId, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-Token': token },
                        credentials: 'same-origin'
                    })
                        .then(function (response) {
                            if (response.ok) {
                                if (card) card.remove();
                                // Update badge count
                                const badge = document.querySelector('.current-skills .badge');
                                if (badge) {
                                    const count = parseInt(badge.textContent) - 1;
                                    badge.textContent = count;
                                }
                            } else {
                                throw new Error(Drupal.t('Failed to remove skill'));
                            }
                        })
                        .catch(function (error) {
                            console.error('Error removing skill:', error);
                            if (card) card.style.opacity = '1';
                            alert(Drupal.t('Error removing skill. Please try again.'));
                        });
                    }); // getCsrfToken
                });
            });
        }
    };

})(Drupal);
