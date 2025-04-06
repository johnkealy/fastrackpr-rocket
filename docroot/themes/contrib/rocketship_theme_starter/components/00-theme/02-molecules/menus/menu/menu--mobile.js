/**
 * Rocketship UI JS
 *
 * contains: triggers for functions
 * Functions themselves are split off and grouped below each behavior
 *
 * Drupal behavior's:
 *
 * Means the JS is loaded when page is first loaded
 * \+ during AJAX requests (for newly added content).
 * Use "once" to avoid processing the same element multiple times.
 * Use the "context" param to limit scope, by default this will return document.
 * Use the "settings" param to get stuff set via the theme hooks and such.
 *
 *
 * Avoid multiple triggers by using once:
 *
 * EXAMPLE 1:
 *
 * once('my-behavior', '.some-element', context).forEach((element) => {
 *   // The following click-binding will only be applied once
 * });
 */

(function (Drupal, window, document) {
  "use strict";

  // Set namespace for frontend UI javascript.
  if (typeof window.rocketshipUI == 'undefined') {
    window.rocketshipUI = {};
  }

  ///////////////////////////////////////////////////////////////////////
  // Cache variables available across the namespace.
  ///////////////////////////////////////////////////////////////////////

  var self = window.rocketshipUI;

  ///////////////////////////////////////////////////////////////////////
  // Behavior for menu mobile: triggers.
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUIMobileMenu = {
    attach: function (context, settings) {
      var navWrapper = document.querySelector('.wrapper--headers', context),
        hamburger = navWrapper.querySelector('.navigation__toggle-expand', context);

      // Mobile menu.
      if (hamburger && navWrapper) {
        self.mobileMenu(hamburger, navWrapper);
        if (document.body.scrollWidth < 1200) {
        }
        else {
          self.desktopMenu(hamburger, navWrapper);
        }
      }
    }
  };

  self.desktopMenu = () => {
    window.scrollBy(0, -1);
    document.body.classList.remove('js-scrolling-down');
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for menu mobile: functions.
  ///////////////////////////////////////////////////////////////////////

  /**
   * Mobile menu functionality.
   */
  self.mobileMenu = function(trigger, wrapper) {
    const toggleClass = 'js-open';

    once('mobileMenu', trigger).forEach((triggerElement) => {
      triggerElement.addEventListener('click', (e) => {
        e.preventDefault();

        if (document.body.scrollWidth >= 1200) {
          return self.desktopMenu();
        }

        // Add classes (css handles the animation & open/close).
        if (wrapper.classList.contains(toggleClass)) {
          closeNavigation();
        }
        else {
          openNavigation();
        }
      });

      triggerElement.addEventListener('keydown', (e) => {
        const key = e.key;
        let flag = false;

        switch(key) {
          case ' ':
          case 'Enter':
            openNavigation();
            setFocusToFirstMenuitem();
            flag = true;
            break;
          case 'Esc':
          case 'Escape':
            closeNavigation();
            trigger.focus();
            flag = true;
            break;
        }

        if (flag) {
          e.stopPropagation();
          e.preventDefault();
        }
      });
    });

    // Remove the close class when switching to bigger screen
    // we don't need it there.
    window.addEventListener('resize', () => {
      if (wrapper.classList.contains(toggleClass)) {
        closeNavigation();
      }
    });

    // Close menu when pressing escape.
    wrapper.addEventListener('keydown', (e) => {
      const key = e.key;
      let flag = false;

      switch(key) {
        case 'Esc':
        case 'Escape':
          if (wrapper.classList.contains(toggleClass)) {
            closeNavigation();
            trigger.focus();
            flag = true;
            break;
          }
      }

      if (flag) {
        e.stopPropagation();
        e.preventDefault();
      }
    });

    // Close menu if focus is outside of wrapper.
    wrapper.addEventListener('focusout', (e) => {
      // e.relatedTarget can return null of clicked on en element that does not have a focus, eg the background.
      if (e.relatedTarget !== null && !wrapper.contains(e.relatedTarget)) {
        if (wrapper.classList.contains(toggleClass)) {
          closeNavigation();
        }
      }
    }, true);

    function openNavigation () {
      wrapper.classList.add(toggleClass);
      document.body.classList.add('mobile-nav--open');
      trigger.setAttribute('aria-expanded', 'true');
      trigger.querySelector('.navigation__toggle-expand__group--open').setAttribute('aria-hidden', 'true');
      trigger.querySelector('.navigation__toggle-expand__group--closed').setAttribute('aria-hidden', 'false');
    }

    function closeNavigation () {
      wrapper.classList.remove(toggleClass);
      document.body.classList.remove('mobile-nav--open');
      trigger.setAttribute('aria-expanded', 'false');
      trigger.querySelector('.navigation__toggle-expand__group--open').setAttribute('aria-hidden', 'false');
      trigger.querySelector('.navigation__toggle-expand__group--closed').setAttribute('aria-hidden', 'true');

      wrapper.querySelectorAll(`.${toggleClass}`).forEach((item) => {
        item.classList.remove(toggleClass);
      });
      wrapper.querySelectorAll('.expand-sub').forEach((item) => {
        item.setAttribute('aria-expanded', 'false');
      });

    }

    function setFocusToFirstMenuitem() {
      wrapper.querySelector('.menu__link').focus();
    }
  };
})(Drupal, window, document);
