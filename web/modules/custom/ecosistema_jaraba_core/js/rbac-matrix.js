(function (Drupal) {
    'use strict';

    /**
     * RBAC Matrix interactivity.
     */
    Drupal.behaviors.rbacMatrix = {
        attach: function (context) {
            // Toggle permission via AJAX
            const toggles = context.querySelectorAll('.rbac-toggle:not(.processed)');
            toggles.forEach(function (toggle) {
                toggle.classList.add('processed');
                toggle.addEventListener('change', function () {
                    const role = this.dataset.role;
                    const permission = this.dataset.permission;
                    const enabled = this.checked;

                    // Disable during request
                    this.disabled = true;

                    fetch('/api/v1/admin/rbac/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ role, permission, enabled }),
                    })
                        .then(response => response.json())
                        .then(data => {
                            this.disabled = false;
                            if (!data.success) {
                                // Revert checkbox on error
                                this.checked = !enabled;
                                console.error('RBAC toggle error:', data.error);
                            }
                        })
                        .catch(error => {
                            this.disabled = false;
                            this.checked = !enabled;
                            console.error('RBAC toggle failed:', error);
                        });
                });
            });

            // Module filter
            const moduleFilter = context.querySelector('#module-filter:not(.processed)');
            if (moduleFilter) {
                moduleFilter.classList.add('processed');
                moduleFilter.addEventListener('change', function () {
                    const selectedModule = this.value;
                    const rows = context.querySelectorAll('.rbac-matrix-row');

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

})(Drupal);
