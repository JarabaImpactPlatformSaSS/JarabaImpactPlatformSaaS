/**
 * @file
 * Service Worker registration and cache strategy management.
 *
 * Implements a multi-strategy caching approach:
 * - cache-first: Static assets (CSS, JS, fonts, images).
 * - network-first: API endpoints with cache fallback.
 * - stale-while-revalidate: Pages served from cache then updated.
 *
 * This file registers the service worker; the actual SW logic
 * runs in the service worker scope (sw.js).
 */

(function (Drupal, drupalSettings) {
  'use strict';

  var CACHE_VERSION = 'jaraba-pwa-v1';
  var STATIC_CACHE = CACHE_VERSION + '-static';
  var DYNAMIC_CACHE = CACHE_VERSION + '-dynamic';
  var API_CACHE = CACHE_VERSION + '-api';

  var OFFLINE_PAGES = [
    '/',
    '/user/login',
    '/dashboard',
    '/offline'
  ];

  /**
   * Registers the service worker if supported.
   */
  function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
      return;
    }

    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .then(function (registration) {
          Drupal.jarabaPwa = Drupal.jarabaPwa || {};
          Drupal.jarabaPwa.registration = registration;

          registration.addEventListener('updatefound', function () {
            var newWorker = registration.installing;
            if (newWorker) {
              newWorker.addEventListener('statechange', function () {
                if (newWorker.state === 'activated') {
                  if (navigator.serviceWorker.controller) {
                    Drupal.jarabaPwa.onUpdate && Drupal.jarabaPwa.onUpdate();
                  }
                }
              });
            }
          });
        })
        .catch(function (error) {
          if (typeof console !== 'undefined') {
            console.error('[Jaraba PWA] Service Worker registration failed:', error);
          }
        });
    });
  }

  /**
   * Determines the cache strategy for a given URL.
   *
   * @param {string} url - The request URL.
   * @return {string} The cache strategy name.
   */
  function getStrategyForUrl(url) {
    var pathname = new URL(url, window.location.origin).pathname;

    // Static assets: cache-first.
    if (/\.(?:css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|avif|ico)$/.test(pathname)) {
      return 'cache-first';
    }

    // API endpoints: network-first.
    if (/^\/api\/v1\//.test(pathname)) {
      return 'network-first';
    }

    // Admin pages: network-only.
    if (/^\/admin\//.test(pathname)) {
      return 'network-only';
    }

    // All other pages: stale-while-revalidate.
    return 'stale-while-revalidate';
  }

  /**
   * Pre-caches essential pages for offline use.
   *
   * @param {Cache} cache - The Cache API instance.
   * @return {Promise} Resolves when all pages are cached.
   */
  function precacheOfflinePages(cache) {
    return cache.addAll(OFFLINE_PAGES);
  }

  // Expose utilities for the actual service worker file.
  Drupal.jarabaPwa = Drupal.jarabaPwa || {};
  Drupal.jarabaPwa.CACHE_VERSION = CACHE_VERSION;
  Drupal.jarabaPwa.STATIC_CACHE = STATIC_CACHE;
  Drupal.jarabaPwa.DYNAMIC_CACHE = DYNAMIC_CACHE;
  Drupal.jarabaPwa.API_CACHE = API_CACHE;
  Drupal.jarabaPwa.OFFLINE_PAGES = OFFLINE_PAGES;
  Drupal.jarabaPwa.getStrategyForUrl = getStrategyForUrl;
  Drupal.jarabaPwa.precacheOfflinePages = precacheOfflinePages;

  // Register on load.
  registerServiceWorker();

})(Drupal, drupalSettings);
