/**
 * Status javascript events
 */

(function (Drupal, window, document) {
  "use strict";

  // set namespace for frontend UI javascript
  if (typeof window.rocketshipUI == 'undefined') {
    window.rocketshipUI = {};
  }

  var self = window.rocketshipUI;

  ///////////////////////////////////////////////////////////////////////
  // Behavior for status messages: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUIStatus = {
    attach: function () {
      const message = document.querySelectorAll('.messages--drupal');
      self.drupalMessages(message);
    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for status messages: functions
  ///////////////////////////////////////////////////////////////////////

  /**
   * Click away Drupal messages.
   */
  self.drupalMessages = function (message) {
    once('status', message).forEach((messageElement) => {
      const myMessage = messageElement;

      messageElement.querySelector('.js-close').addEventListener('click', (e) => {
        messageElement.classList.add('js-closing');
        e.preventDefault();
      });

      messageElement.addEventListener('transitionend', () => {
        if (messageElement.classList.contains('js-closing')) {
          messageElement.classList.add('js-closed');
        }
      });
    });
  };

})(Drupal, window, document);
