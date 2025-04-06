/**
 * Rocketship UI JS
 *
 * contains: triggers for functions
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
 * once('js-once-my-behavior', '.some-link', context).forEach(function(element) {
 *   $(element).click(function () {
 *     // Code here will only be applied once
 *   });
 * });
 *
 * EXAMPLE 2:
 *
 * once('js-once-my-behavior', '.some-element', context).forEach(function (element) {
 *   // The following click-binding will only be applied once
 * });
 */

(function ($, Drupal, window, document) {

  "use strict";

  // set namespace for frontend UI javascript
  if (typeof window.rocketshipUI == 'undefined') { window.rocketshipUI = {}; }

  var self = window.rocketshipUI;

  ///////////////////////////////////////////////////////////////////////
  // Cache variables available across the namespace
  ///////////////////////////////////////////////////////////////////////


  ///////////////////////////////////////////////////////////////////////
  // Behavior for Focus: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUI_cbFocus = {
    attach: function (context, settings) {

      let focus = $('.block--type-cb-focus', context);

      // NOT CURRENTLY IN USE
      // // make cards clickable: based on button element, if only has 1
      // if (focus.length && typeof window.rocketshipUI !== 'undefined' && typeof window.rocketshipUI.cardLink !== 'undefined' ) {
      //   var buttons = focus.find('.button');
      //   if (typeof buttons !== 'undefined' && buttons.length === 1) {
      //     window.rocketshipUI.cardLink(focus, '.button');
      //   }
      // }

    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Focus: functions
  ///////////////////////////////////////////////////////////////////////


})(jQuery, Drupal, window, document);
