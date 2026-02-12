/**
 * @file
 * Cliente JavaScript para A/B Testing.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Funcionalidades:
 * - Detectar experimento activo para la página actual
 * - Aplicar variante asignada
 * - Trackear conversiones via beacon
 * - Sincronizar con cookie de sesión
 */
(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Cookie name for A/B testing assignments.
     * @type {string}
     */
    const COOKIE_NAME = 'jaraba_ab_test';

    /**
     * API endpoints.
     * @type {Object}
     */
    const API = {
        trackVisit: '/api/v1/experiments/track-visit',
        trackConversion: '/api/v1/experiments/track-conversion',
    };

    /**
     * Current experiment state.
     * @type {Object}
     */
    let experimentState = {
        experimentId: null,
        variantId: null,
        variantName: null,
        isControl: true,
        contentData: null,
    };

    /**
     * Drupal behavior for A/B Testing.
     */
    Drupal.behaviors.jarabaAbTesting = {
        attach: function (context) {
            // Only run once per page load.
            once('jaraba-ab-testing', 'body', context).forEach(function () {
                initializeExperiment();
            });
        }
    };

    /**
     * Initialize experiment for current page.
     */
    async function initializeExperiment() {
        const pageId = getPageId();
        if (!pageId) {
            return;
        }

        try {
            const response = await fetch(API.trackVisit, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    page_id: pageId,
                }),
            });

            if (!response.ok) {
                console.warn('[A/B Testing] Track visit failed:', response.status);
                return;
            }

            const data = await response.json();

            if (!data.experiment_id || !data.variant_id) {
                // No active experiment for this page.
                return;
            }

            experimentState = {
                experimentId: data.experiment_id,
                variantId: data.variant_id,
                variantName: data.variant_name,
                isControl: data.is_control,
                contentData: data.content_data,
            };

            // Apply variant if not control.
            if (!experimentState.isControl && experimentState.contentData) {
                applyVariantContent(experimentState.contentData);
            }

            // Setup conversion tracking.
            setupConversionTracking();

            console.log('[A/B Testing] Initialized:', experimentState.variantName);
        }
        catch (error) {
            console.warn('[A/B Testing] Error:', error);
        }
    }

    /**
     * Get current page ID from Drupal settings.
     *
     * @return {number|null}
     */
    function getPageId() {
        // Try to get from drupalSettings.
        if (drupalSettings.jarabaPageBuilder && drupalSettings.jarabaPageBuilder.pageId) {
            return parseInt(drupalSettings.jarabaPageBuilder.pageId, 10);
        }

        // Try to get from data attribute.
        const pageElement = document.querySelector('[data-page-content-id]');
        if (pageElement) {
            return parseInt(pageElement.dataset.pageContentId, 10);
        }

        // Try to extract from URL (fallback).
        const match = window.location.pathname.match(/\/page\/(\d+)/);
        if (match) {
            return parseInt(match[1], 10);
        }

        return null;
    }

    /**
     * Apply variant content modifications.
     *
     * @param {Object} contentData - Modified content data from variant.
     */
    function applyVariantContent(contentData) {
        if (!contentData) {
            return;
        }

        // Apply text modifications.
        if (contentData.texts) {
            Object.entries(contentData.texts).forEach(([selector, value]) => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.textContent = value;
                });
            });
        }

        // Apply HTML modifications.
        if (contentData.html) {
            Object.entries(contentData.html).forEach(([selector, value]) => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.innerHTML = value;
                });
            });
        }

        // Apply style modifications.
        if (contentData.styles) {
            Object.entries(contentData.styles).forEach(([selector, styles]) => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    Object.entries(styles).forEach(([property, value]) => {
                        el.style[property] = value;
                    });
                });
            });
        }

        // Apply class modifications.
        if (contentData.classes) {
            if (contentData.classes.add) {
                Object.entries(contentData.classes.add).forEach(([selector, classes]) => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(el => {
                        el.classList.add(...classes.split(' '));
                    });
                });
            }

            if (contentData.classes.remove) {
                Object.entries(contentData.classes.remove).forEach(([selector, classes]) => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(el => {
                        el.classList.remove(...classes.split(' '));
                    });
                });
            }
        }

        // Apply visibility modifications.
        if (contentData.hide) {
            contentData.hide.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.style.display = 'none';
                });
            });
        }

        if (contentData.show) {
            contentData.show.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.style.display = '';
                });
            });
        }

        // Dispatch event for custom handling.
        document.dispatchEvent(new CustomEvent('jaraba:ab-variant-applied', {
            detail: experimentState,
        }));
    }

    /**
     * Setup conversion tracking for the experiment.
     */
    function setupConversionTracking() {
        // Track CTA clicks.
        document.addEventListener('click', function (event) {
            const target = event.target.closest('[data-ab-conversion]');
            if (target) {
                trackConversion('click', target.dataset.abConversion);
            }

            // Track any button/link with class ab-track.
            const trackable = event.target.closest('.ab-track, [data-track-conversion]');
            if (trackable) {
                trackConversion('click', trackable.dataset.trackConversion || 'cta');
            }
        });

        // Track form submissions.
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (form.dataset.abConversion || form.classList.contains('ab-track-form')) {
                trackConversion('form_submit', form.dataset.abConversion || 'form');
            }
        });

        // Track scroll depth.
        let scrollTracked = false;
        window.addEventListener('scroll', function () {
            if (scrollTracked) return;

            const scrollPercentage = (window.scrollY + window.innerHeight) / document.body.scrollHeight * 100;
            if (scrollPercentage >= 75) {
                trackConversion('scroll_depth', '75');
                scrollTracked = true;
            }
        }, { passive: true });
    }

    /**
     * Track a conversion event.
     *
     * @param {string} type - Conversion type.
     * @param {string} target - Target identifier.
     */
    function trackConversion(type, target) {
        if (!experimentState.experimentId || !experimentState.variantId) {
            return;
        }

        const data = JSON.stringify({
            experiment_id: experimentState.experimentId,
            variant_id: experimentState.variantId,
            type: type,
            target: target,
        });

        // Use sendBeacon for reliable tracking.
        if (navigator.sendBeacon) {
            const blob = new Blob([data], { type: 'application/json' });
            navigator.sendBeacon(API.trackConversion, blob);
        }
        else {
            // Fallback to fetch.
            fetch(API.trackConversion, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: data,
                keepalive: true,
            }).catch(() => { });
        }

        console.log('[A/B Testing] Conversion tracked:', type, target);
    }

    /**
     * Get current experiment state (for debugging).
     *
     * @return {Object}
     */
    Drupal.jarabaAbTesting = {
        getState: function () {
            return { ...experimentState };
        },
        trackConversion: trackConversion,
    };

})(Drupal, drupalSettings, once);
