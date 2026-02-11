/**
 * @file
 * Dark mode toggle functionality.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * State management for dark mode.
   */
  const darkModeState = {
    STORAGE_KEY: 'jaraba-dark-mode',
    CLASS_NAME: 'dark-mode',
    
    isEnabled() {
      const stored = localStorage.getItem(this.STORAGE_KEY);
      if (stored !== null) {
        return stored === 'true';
      }
      // Check system preference
      return window.matchMedia('(prefers-color-scheme: dark)').matches;
    },
    
    setEnabled(enabled) {
      localStorage.setItem(this.STORAGE_KEY, enabled.toString());
      this.apply(enabled);
    },
    
    apply(enabled) {
      document.documentElement.classList.toggle(this.CLASS_NAME, enabled);
      document.body.classList.toggle(this.CLASS_NAME, enabled);
      
      // Dispatch custom event
      document.dispatchEvent(new CustomEvent('darkModeChange', {
        detail: { enabled }
      }));
    }
  };

  /**
   * Initialize dark mode toggle.
   */
  Drupal.behaviors.darkModeToggle = {
    attach: function (context, settings) {
      // Apply initial state
      if (context === document) {
        darkModeState.apply(darkModeState.isEnabled());
      }

      // Find toggle buttons
      const toggles = context.querySelectorAll('[data-dark-mode-toggle]');
      toggles.forEach(function (toggle) {
        if (toggle.processed) return;
        toggle.processed = true;

        toggle.addEventListener('click', function (e) {
          e.preventDefault();
          const newState = !darkModeState.isEnabled();
          darkModeState.setEnabled(newState);
          updateToggleUI(toggle, newState);
        });

        // Set initial UI state
        updateToggleUI(toggle, darkModeState.isEnabled());
      });

      // Listen for system preference changes
      if (context === document) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
          if (localStorage.getItem(darkModeState.STORAGE_KEY) === null) {
            darkModeState.apply(e.matches);
          }
        });
      }
    }
  };

  /**
   * Update toggle button UI.
   */
  function updateToggleUI(toggle, isDark) {
    const sunIcon = toggle.querySelector('.icon-sun');
    const moonIcon = toggle.querySelector('.icon-moon');
    
    if (sunIcon && moonIcon) {
      sunIcon.style.display = isDark ? 'none' : 'block';
      moonIcon.style.display = isDark ? 'block' : 'none';
    }
    
    toggle.setAttribute('aria-pressed', isDark.toString());
    toggle.setAttribute('aria-label', isDark ? Drupal.t('Switch to light mode') : Drupal.t('Switch to dark mode'));
  }

  // Expose globally
  Drupal.darkMode = darkModeState;

})(Drupal, drupalSettings);
