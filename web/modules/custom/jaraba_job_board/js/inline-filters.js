/**
 * @file
 * Inline filters for employer dashboard jobs list.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.employerDashboardFilters = {
        attach: function (context) {
            once('inline-filters', '#jobsInlineFilters', context).forEach(function (filtersContainer) {
                const searchInput = document.getElementById('jobSearchInput');
                const jobsList = document.getElementById('jobsList');
                const chips = filtersContainer.querySelectorAll('.inline-chip');
                let currentFilter = 'all';

                if (!searchInput || !jobsList) return;

                // Handle search input
                searchInput.addEventListener('input', debounce(function () {
                    filterJobs();
                }, 200));

                // Handle chip clicks
                chips.forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        // Update active state
                        chips.forEach(c => c.classList.remove('inline-chip--active'));
                        this.classList.add('inline-chip--active');
                        currentFilter = this.dataset.filter;
                        filterJobs();
                    });
                });

                function filterJobs() {
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    const items = jobsList.querySelectorAll('.job-item');
                    let visibleCount = 0;

                    items.forEach(function (item) {
                        const title = item.querySelector('h4')?.textContent.toLowerCase() || '';
                        const status = getItemStatus(item);

                        const matchesSearch = !searchTerm || title.includes(searchTerm);
                        const matchesFilter = currentFilter === 'all' || status === currentFilter;

                        if (matchesSearch && matchesFilter) {
                            item.style.display = '';
                            item.style.animation = 'fadeIn 0.2s ease';
                            visibleCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    // Show empty message if no results
                    updateEmptyMessage(visibleCount === 0 && items.length > 0);
                }

                function getItemStatus(item) {
                    const classList = item.classList;
                    if (classList.contains('job-item--published')) return 'published';
                    if (classList.contains('job-item--draft')) return 'draft';
                    if (classList.contains('job-item--paused')) return 'paused';
                    if (classList.contains('job-item--closed')) return 'closed';
                    return 'unknown';
                }

                function updateEmptyMessage(show) {
                    let emptyMsg = jobsList.parentElement.querySelector('.inline-empty-state');

                    if (show && !emptyMsg) {
                        emptyMsg = document.createElement('div');
                        emptyMsg.className = 'inline-empty-state';
                        emptyMsg.innerHTML = '<p>' + Drupal.t('No se encontraron ofertas con estos filtros.') + '</p>';
                        jobsList.parentElement.insertBefore(emptyMsg, jobsList.nextSibling);
                    } else if (!show && emptyMsg) {
                        emptyMsg.remove();
                    }
                }

                function debounce(func, wait) {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };
                }
            });
        }
    };

})(Drupal, once);
