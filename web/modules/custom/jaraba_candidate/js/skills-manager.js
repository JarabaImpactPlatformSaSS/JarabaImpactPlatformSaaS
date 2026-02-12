/**
 * @file
 * Skills Manager - Interactive UI for managing candidate skills.
 */

(function (Drupal) {
    'use strict';

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
                    fetch('/api/v1/profile/skills', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
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
                                // Reload page to show new skill
                                window.location.reload();
                            } else {
                                throw new Error('Failed to add skill');
                            }
                        })
                        .catch(function (error) {
                            console.error('Error adding skill:', error);
                            btn.disabled = false;
                            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> ' + skillName;
                            alert(Drupal.t('Error adding skill. Please try again.'));
                        });
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
                    fetch('/api/v1/profile/skills/' + skillEntityId, {
                        method: 'DELETE',
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
                                throw new Error('Failed to remove skill');
                            }
                        })
                        .catch(function (error) {
                            console.error('Error removing skill:', error);
                            if (card) card.style.opacity = '1';
                            alert(Drupal.t('Error removing skill. Please try again.'));
                        });
                });
            });
        }
    };

})(Drupal);
