/**
 * Rocketship UI JS
 *
 * contains: triggers for functions.
 * Functions themselves are split off and grouped below each behavior
 *
 * Drupal behaviors:
 *
 * Means the JS is loaded when page is first loaded
 * \+ during AJAX requests (for newly added content)
 * use "once" to avoid processing the same element multiple times
 * use the "context" param to limit scope, by default this will return document
 * use the "settings" param to get stuff set via the theme hooks and such.
 *
 *
 * Avoid multiple triggers by using Once
 *
 * EXAMPLE 1:
 *
 * once('my-behavior', '.some-link', context).forEach(function(element) {
 *   $(element).click(function () {
 *     // Code here will only be applied once
 *   });
 * });
 *
 * EXAMPLE 2:
 *
 * once('my-behavior', '.some-element', context).forEach(function (element) {
 *   // The following click-binding will only be applied once
 * });
 */

(function ($, Drupal, window, document) {
  "use strict";

  // set namespace for frontend UI javascript
  if (typeof window.rocketshipUI == 'undefined') {
    window.rocketshipUI = {};
  }

  var self = window.rocketshipUI;

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUILanguageMenu = {
    attach: function (context, settings) {
      var langNav = $('.nav--language-interface');

      if (langNav.length) {
        // Check the theme settings if it is set to 'dropdown'
        // located at: /admin/appearance/settings/theme_machine_name
        if (drupalSettings?.theme_settings?.language_dropdown === true || drupalSettings.theme_settings.language_dropdown === 1) {
          langNav.each(function() {
            self.languageDropdown($(this));
          });
        }
      }
    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: functions
  ///////////////////////////////////////////////////////////////////////

  /**
   * Dropdown menu
   *
   */
  self.languageDropdown = function(nav) {
    var activeLangeHolder = nav.find('.nav__active--language');

    // touch event to open/close
    // includes touch detection
    activeLangeHolder.on('touchstart', function(e) {
      self.touch = true;

      if (nav.hasClass('js-open')) {
        self.navLangClose(nav);
      } else {
        self.navLangOpen(nav);
      }

      e.preventDefault();
    });

    // reset the touch variable afterwards
    activeLangeHolder.on('touchend', function(e) {
      // end
      setTimeout(function() {
        self.touch = false; // reset bc we might be on a device that has mouse as well as touch capability
      }, 500); // time it until after a 'click' would have fired on mobile (>300ms)

      e.preventDefault();
    });

    // open/close on hover
    // if not in touch modus
    nav.on('mouseenter', function(e) {
      // if no touch triggered
      if (!self.touch) {
        self.navLangOpen(nav);
        e.preventDefault();
      }
    });

    // close for normal menu
    // if not megamenu or small screen,
    nav.on('mouseleave', function(e) {
      self.navLangClose(nav);
      e.preventDefault();
    });

    // on window resize, reset the menu to closed state
    window.rocketshipUI.optimizedResize().add(function() {
      self.navLangClose(nav);
    });
  };

  self.navLangOpen = function(target) {
    target.addClass('js-open');
  };

  self.navLangClose = function(target) {
    target.removeClass('js-open');
  };

})(jQuery, Drupal, window, document);
