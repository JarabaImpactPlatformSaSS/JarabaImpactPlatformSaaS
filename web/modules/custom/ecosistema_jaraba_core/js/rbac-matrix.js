(function (Drupal, drupalSettings) {
    'use strict';

    /**
     * CSRF token cache (CSRF-JS-CACHE-001).
     */
    var csrfToken = null;

    function getCsrfToken() {
        if (csrfToken) {
            return Promise.resolve(csrfToken);
        }
        var tokenUrl = (drupalSettings.path && drupalSettings.path.baseUrl || '/') + 'session/token';
        return fetch(tokenUrl)
            .then(function (response) { return response.text(); })
            .then(function (token) {
                csrfToken = token;
                return token;
            });
    }

    /**
     * RBAC Matrix interactivity.
     */
    Drupal.behaviors.rbacMatrix = {
        attach: function (context) {
            var toggleUrl = drupalSettings.rbacMatrix && drupalSettings.rbacMatrix.toggleUrl || '';

            // Toggle permission via AJAX
            var toggles = context.querySelectorAll('.rbac-toggle:not(.processed)');
            toggles.forEach(function (toggle) {
                toggle.classList.add('processed');
                toggle.addEventListener('change', function () {
                    var checkbox = this;
                    var role = checkbox.dataset.role;
                    var permission = checkbox.dataset.permission;
                    var enabled = checkbox.checked;

                    // Disable during request
                    checkbox.disabled = true;

                    getCsrfToken().then(function (token) {
                        return fetch(toggleUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Drupal-Token': token,
                            },
                            body: JSON.stringify({ role: role, permission: permission, enabled: enabled }),
                        });
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            checkbox.disabled = false;
                            if (!data.success) {
                                // Revert checkbox on error
                                checkbox.checked = !enabled;
                                console.error('RBAC toggle error:', data.error);
                            }
                        })
                        .catch(function (error) {
                            checkbox.disabled = false;
                            checkbox.checked = !enabled;
                            console.error('RBAC toggle failed:', error);
                        });
                });
            });

            // Module filter
            var moduleFilter = context.querySelector('#module-filter:not(.processed)');
            if (moduleFilter) {
                moduleFilter.classList.add('processed');
                moduleFilter.addEventListener('change', function () {
                    var selectedModule = this.value;
                    var rows = context.querySelectorAll('.rbac-matrix-row');

                    rows.forEach(function (row) {
                        if (!selectedModule || row.dataset.module === selectedModule) {
                            row.classList.remove('hidden');
                        } else {
                            row.classList.add('hidden');
                        }
                    });
                });
            }
        }
    };

})(Drupal, drupalSettings);
