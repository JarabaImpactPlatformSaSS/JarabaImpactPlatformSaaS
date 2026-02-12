/**
 * @file
 * Command Palette — Global Cmd+K overlay for admin routes.
 *
 * Features:
 * - Cmd+K / Ctrl+K to toggle palette.
 * - Fuzzy search via /api/v1/admin/search?q={query}.
 * - Built-in go commands: G→T (Tenants), G→U (Users), G→F (Finance).
 * - Alert shortcut: A, Impersonate: I, Help: ?
 * - Arrow keys + Enter navigation.
 *
 * F6 — Doc 181.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  var COMMANDS = [
    { label: Drupal.t('Ir a Tenants'), icon: '🏢', shortcut: 'G T', url: '/admin/structure/group', group: 'navigation' },
    { label: Drupal.t('Ir a Usuarios'), icon: '👥', shortcut: 'G U', url: '/admin/people', group: 'navigation' },
    { label: Drupal.t('Ir a Finanzas'), icon: '💰', shortcut: 'G F', url: '/admin/finops', group: 'navigation' },
    { label: Drupal.t('Health Monitor'), icon: '📊', shortcut: '', url: '/admin/health', group: 'navigation' },
    { label: Drupal.t('Analytics'), icon: '📈', shortcut: '', url: '/admin/jaraba/analytics', group: 'navigation' },
    { label: Drupal.t('Alertas'), icon: '🔔', shortcut: 'A', url: '/admin/config/system/alert-rules', group: 'navigation' },
    { label: Drupal.t('Compliance'), icon: '🛡️', shortcut: '', url: '/admin/seguridad', group: 'navigation' },
    { label: Drupal.t('Email'), icon: '📧', shortcut: '', url: '/admin/jaraba/email', group: 'navigation' },
    { label: Drupal.t('Admin Center'), icon: '🏠', shortcut: '', url: '/admin/jaraba/center', group: 'navigation' },
    { label: Drupal.t('Customer Success'), icon: '💚', shortcut: '', url: '/admin/structure/customer-success', group: 'navigation' },
    { label: Drupal.t('RBAC Matrix'), icon: '🔐', shortcut: '', url: '/admin/people/rbac-matrix', group: 'navigation' },
  ];

  Drupal.behaviors.adminCommandPalette = {
    attach: function (context) {
      once('admin-command-palette', 'body', context).forEach(function () {
        new CommandPalette();
      });
    }
  };

  function CommandPalette() {
    this.isOpen = false;
    this.activeIndex = -1;
    this.items = [];
    this.searchTimeout = null;
    this.goPrefix = false;

    this.config = drupalSettings.adminCommandPalette || {};
    this.searchUrl = this.config.searchUrl || '/api/v1/admin/search';

    this.createDom();
    this.bindGlobalKeys();
  }

  /**
   * Create the palette DOM structure.
   */
  CommandPalette.prototype.createDom = function () {
    this.overlay = document.createElement('div');
    this.overlay.className = 'command-palette-overlay';
    this.overlay.setAttribute('role', 'dialog');
    this.overlay.setAttribute('aria-label', Drupal.t('Command Palette'));

    this.overlay.innerHTML =
      '<div class="command-palette">' +
        '<div class="command-palette__input-wrapper">' +
          '<span class="command-palette__search-icon">🔍</span>' +
          '<input type="text" class="command-palette__input" placeholder="' + Drupal.t('Escribe un comando o busca...') + '" autocomplete="off" />' +
          '<span class="command-palette__close-hint">ESC</span>' +
        '</div>' +
        '<div class="command-palette__results"></div>' +
        '<div class="command-palette__footer">' +
          '<span class="command-palette__footer-hint"><kbd>↑↓</kbd> ' + Drupal.t('navegar') + '</span>' +
          '<span class="command-palette__footer-hint"><kbd>↵</kbd> ' + Drupal.t('abrir') + '</span>' +
          '<span class="command-palette__footer-hint"><kbd>esc</kbd> ' + Drupal.t('cerrar') + '</span>' +
        '</div>' +
      '</div>';

    document.body.appendChild(this.overlay);

    this.input = this.overlay.querySelector('.command-palette__input');
    this.results = this.overlay.querySelector('.command-palette__results');

    this.bindPaletteEvents();
  };

  /**
   * Bind palette-internal events.
   */
  CommandPalette.prototype.bindPaletteEvents = function () {
    var self = this;

    // Close on overlay click.
    this.overlay.addEventListener('click', function (e) {
      if (e.target === self.overlay) {
        self.close();
      }
    });

    // Input keydown for navigation.
    this.input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        self.close();
        e.preventDefault();
        return;
      }
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        self.moveSelection(1);
        return;
      }
      if (e.key === 'ArrowUp') {
        e.preventDefault();
        self.moveSelection(-1);
        return;
      }
      if (e.key === 'Enter') {
        e.preventDefault();
        self.executeSelected();
        return;
      }
    });

    // Input for search.
    this.input.addEventListener('input', function () {
      clearTimeout(self.searchTimeout);
      self.searchTimeout = setTimeout(function () {
        self.onQueryChange(self.input.value.trim());
      }, 200);
    });
  };

  /**
   * Bind global keyboard shortcuts.
   */
  CommandPalette.prototype.bindGlobalKeys = function () {
    var self = this;
    var gTimer = null;

    document.addEventListener('keydown', function (e) {
      // Ignore when focused on inputs (except our palette).
      var tag = e.target.tagName;
      var isInput = (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable);

      // Cmd+K / Ctrl+K — toggle palette.
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        self.toggle();
        return;
      }

      // Skip other shortcuts when in an input field.
      if (isInput) return;

      // G prefix sequences: G → T, G → U, G → F.
      if (e.key === 'g' || e.key === 'G') {
        if (!self.goPrefix) {
          self.goPrefix = true;
          clearTimeout(gTimer);
          gTimer = setTimeout(function () {
            self.goPrefix = false;
          }, 800);
          return;
        }
      }

      if (self.goPrefix) {
        self.goPrefix = false;
        clearTimeout(gTimer);

        switch (e.key.toLowerCase()) {
          case 't':
            e.preventDefault();
            window.location.href = '/admin/structure/group';
            return;
          case 'u':
            e.preventDefault();
            window.location.href = '/admin/people';
            return;
          case 'f':
            e.preventDefault();
            window.location.href = '/admin/finops';
            return;
        }
      }

      // Single key shortcuts.
      if (e.key === 'a' || e.key === 'A') {
        window.location.href = '/admin/config/system/alert-rules';
        return;
      }

      if (e.key === '?') {
        e.preventDefault();
        self.open();
        self.input.value = '';
        self.showHelp();
        return;
      }
    });
  };

  /**
   * Toggle palette open/close.
   */
  CommandPalette.prototype.toggle = function () {
    if (this.isOpen) {
      this.close();
    } else {
      this.open();
    }
  };

  /**
   * Open the palette.
   */
  CommandPalette.prototype.open = function () {
    this.isOpen = true;
    this.overlay.classList.add('command-palette-overlay--active');
    this.input.value = '';
    this.activeIndex = -1;
    this.showDefaultCommands();
    this.input.focus();
  };

  /**
   * Close the palette.
   */
  CommandPalette.prototype.close = function () {
    this.isOpen = false;
    this.overlay.classList.remove('command-palette-overlay--active');
    this.input.value = '';
    this.results.innerHTML = '';
  };

  /**
   * Show default commands list.
   */
  CommandPalette.prototype.showDefaultCommands = function () {
    this.items = COMMANDS.slice();
    this.renderItems(this.items, Drupal.t('Comandos'));
  };

  /**
   * Show help (all commands with shortcuts).
   */
  CommandPalette.prototype.showHelp = function () {
    var helpItems = COMMANDS.filter(function (c) { return c.shortcut; });
    this.items = helpItems;
    this.renderItems(this.items, Drupal.t('Atajos de teclado'));
  };

  /**
   * Query changed — filter commands or search API.
   */
  CommandPalette.prototype.onQueryChange = function (query) {
    if (!query) {
      this.showDefaultCommands();
      return;
    }

    var lower = query.toLowerCase();

    // Filter built-in commands first.
    var filtered = COMMANDS.filter(function (cmd) {
      return cmd.label.toLowerCase().indexOf(lower) !== -1;
    });

    if (filtered.length > 0 || query.length < 2) {
      this.items = filtered;
      this.renderItems(this.items, Drupal.t('Comandos'));
      return;
    }

    // Search API for tenants/users.
    this.searchApi(query);
  };

  /**
   * Call the search API.
   */
  CommandPalette.prototype.searchApi = function (query) {
    var self = this;

    this.results.innerHTML = '<div class="command-palette__loading">' + Drupal.t('Buscando...') + '</div>';

    fetch(this.searchUrl + '?q=' + encodeURIComponent(query), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success && data.data && data.data.length > 0) {
          self.items = data.data.map(function (item) {
            return {
              label: item.label,
              icon: item.type === 'tenant' ? '🏢' : '👤',
              url: item.url,
              type: item.type,
              shortcut: '',
              group: 'search',
            };
          });
          self.renderItems(self.items, Drupal.t('Resultados'));
        } else {
          self.items = [];
          self.results.innerHTML = '<div class="command-palette__empty">' + Drupal.t('Sin resultados para') + ' "' + self.escapeHtml(query) + '"</div>';
        }
      })
      .catch(function () {
        self.results.innerHTML = '<div class="command-palette__empty">' + Drupal.t('Error de conexion.') + '</div>';
      });
  };

  /**
   * Render items list.
   */
  CommandPalette.prototype.renderItems = function (items, groupLabel) {
    this.activeIndex = -1;

    if (items.length === 0) {
      this.results.innerHTML = '<div class="command-palette__empty">' + Drupal.t('Sin resultados.') + '</div>';
      return;
    }

    var html = '<div class="command-palette__group-label">' + this.escapeHtml(groupLabel) + '</div>';

    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      html += '<div class="command-palette__item" data-index="' + i + '">';
      html += '<span class="command-palette__item-icon">' + item.icon + '</span>';
      html += '<span class="command-palette__item-label">' + this.escapeHtml(item.label) + '</span>';
      if (item.type) {
        html += '<span class="command-palette__item-type">' + this.escapeHtml(item.type) + '</span>';
      }
      if (item.shortcut) {
        html += '<span class="command-palette__item-shortcut">' + this.escapeHtml(item.shortcut) + '</span>';
      }
      html += '</div>';
    }

    this.results.innerHTML = html;

    // Bind click events.
    var self = this;
    this.results.querySelectorAll('.command-palette__item').forEach(function (el) {
      el.addEventListener('click', function () {
        var idx = parseInt(el.getAttribute('data-index'), 10);
        self.activeIndex = idx;
        self.executeSelected();
      });

      el.addEventListener('mouseenter', function () {
        var idx = parseInt(el.getAttribute('data-index'), 10);
        self.setActiveIndex(idx);
      });
    });
  };

  /**
   * Move selection up/down.
   */
  CommandPalette.prototype.moveSelection = function (delta) {
    var count = this.items.length;
    if (count === 0) return;

    var newIndex = this.activeIndex + delta;
    if (newIndex < 0) newIndex = count - 1;
    if (newIndex >= count) newIndex = 0;

    this.setActiveIndex(newIndex);
  };

  /**
   * Set active index and update UI.
   */
  CommandPalette.prototype.setActiveIndex = function (index) {
    this.activeIndex = index;

    var all = this.results.querySelectorAll('.command-palette__item');
    all.forEach(function (el) {
      el.classList.remove('command-palette__item--active');
    });

    if (all[index]) {
      all[index].classList.add('command-palette__item--active');
      all[index].scrollIntoView({ block: 'nearest' });
    }
  };

  /**
   * Execute the currently selected item.
   */
  CommandPalette.prototype.executeSelected = function () {
    if (this.activeIndex < 0 || this.activeIndex >= this.items.length) {
      // If nothing selected, select first.
      if (this.items.length > 0) {
        this.activeIndex = 0;
      } else {
        return;
      }
    }

    var item = this.items[this.activeIndex];
    if (item && item.url) {
      this.close();
      window.location.href = item.url;
    }
  };

  /**
   * Escape HTML for safe rendering.
   */
  CommandPalette.prototype.escapeHtml = function (text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
  };

})(Drupal, drupalSettings, once);
