/**
 * @file
 * Admin Center Layout — Sidebar toggle + search trigger.
 *
 * Features:
 * - Toggle sidebar collapsed/expanded (persisted in localStorage).
 * - Search button triggers Command Palette (Cmd+K).
 * - Mobile: sidebar overlay open/close.
 *
 * F6 — Doc 181 / Spec f104.
 */
(function (Drupal, once) {
  'use strict';

  var STORAGE_KEY = 'adminCenterSidebarCollapsed';

  Drupal.behaviors.adminCenterLayout = {
    attach: function (context) {
      once('admin-center-layout', '.admin-center-shell', context).forEach(function (shell) {
        new AdminCenterLayout(shell);
      });
    }
  };

  /**
   * AdminCenterLayout constructor.
   *
   * @param {HTMLElement} shell - The .admin-center-shell element.
   */
  function AdminCenterLayout(shell) {
    this.shell = shell;

    // Restore collapsed state from localStorage.
    var isCollapsed = localStorage.getItem(STORAGE_KEY) === 'true';
    if (isCollapsed) {
      this.shell.classList.add('admin-center-shell--sidebar-collapsed');
    }

    this.bindToggle();
    this.bindSearchTriggers();
    this.bindMobileOverlay();
  }

  /**
   * Bind sidebar toggle button.
   */
  AdminCenterLayout.prototype.bindToggle = function () {
    var self = this;
    var toggleBtns = this.shell.querySelectorAll('[data-admin-center-toggle]');

    toggleBtns.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        self.toggleSidebar();
      });
    });
  };

  /**
   * Toggle sidebar collapsed state.
   */
  AdminCenterLayout.prototype.toggleSidebar = function () {
    var isCollapsed = this.shell.classList.toggle('admin-center-shell--sidebar-collapsed');
    localStorage.setItem(STORAGE_KEY, isCollapsed ? 'true' : 'false');

    // On mobile, toggle sidebar-open instead.
    if (window.innerWidth <= 1024) {
      this.shell.classList.toggle('admin-center-shell--sidebar-open');
      this.shell.classList.remove('admin-center-shell--sidebar-collapsed');
    }
  };

  /**
   * Bind search buttons to trigger Command Palette.
   */
  AdminCenterLayout.prototype.bindSearchTriggers = function () {
    var searchBtns = this.shell.querySelectorAll('[data-admin-center-search]');

    searchBtns.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        // Dispatch synthetic Cmd+K to trigger the Command Palette.
        var event = new KeyboardEvent('keydown', {
          key: 'k',
          code: 'KeyK',
          ctrlKey: true,
          bubbles: true,
          cancelable: true,
        });
        document.dispatchEvent(event);
      });
    });
  };

  /**
   * Mobile overlay — close sidebar on outside click.
   */
  AdminCenterLayout.prototype.bindMobileOverlay = function () {
    var self = this;

    this.shell.querySelector('.admin-center-main').addEventListener('click', function () {
      if (window.innerWidth <= 1024 && self.shell.classList.contains('admin-center-shell--sidebar-open')) {
        self.shell.classList.remove('admin-center-shell--sidebar-open');
      }
    });
  };

})(Drupal, once);
