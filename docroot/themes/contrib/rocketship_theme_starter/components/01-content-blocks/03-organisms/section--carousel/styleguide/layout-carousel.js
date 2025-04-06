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
  // Cache variables available across the namespace
  ///////////////////////////////////////////////////////////////////////


  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.RocketshipCoreAdminCarousel = {

    attach: function (context, settings) {

      if ($('#layout-builder-content-preview').length > 0) {
        return;
      }

      once('admin-layout-carousel', '.layout--carousel').forEach(function (carouselElement) {
        var slider = $(carouselElement).find('.layout__content__row--carousel');

        if (typeof drupalSettings.rocketshipUI_layout_carousel !== 'undefined') {

          once('admin-layout-carousel-slide', slider.find('.block-layout_builder')).forEach(function (slideElement) {
            var slide = $(slideElement);
            if (typeof slide.closest('.slide') === 'undefined' || slide.closest('.slide').length === 0) {
              slide.wrap('<div class="slide"></div>');
            }
          });

          // Init slick
          slider.slick({
            slide: '.slide',
            infinite: true,
            rows: 0,
            speed: 300,
            slidesToShow: (typeof drupalSettings.rocketshipUI_layout_carousel.slidesToShow_large_screen !== 'undefined') ? drupalSettings.rocketshipUI_layout_carousel.slidesToShow_large_screen : 5,
            slidesToScroll: 1,
            adaptiveHeight: true,
            prevArrow: '<span class="slick-prev">' + Drupal.t("Previous") + '</span>',
            nextArrow: '<span class="slick-next">' + Drupal.t("Next") + '</button>',
            responsive: [
              {
                breakpoint: 1200,
                settings: {
                  slidesToShow: (typeof drupalSettings.rocketshipUI_layout_carousel.slidesToShow_large_screen !== 'undefined') ? drupalSettings.rocketshipUI_layout_carousel.slidesToShow_large_screen : 5,
                }
              },
              {
                breakpoint: 940,
                settings: {
                  slidesToShow: (typeof drupalSettings.rocketshipUI_layout_carousel.slidesToShow_medium_screen !== 'undefined') ? drupalSettings.rocketshipUI_layout_carousel.slidesToShow_medium_screen : 4,
                }
              },
              {
                breakpoint: 768,
                settings: {
                  slidesToShow: (typeof drupalSettings.rocketshipUI_layout_carousel.slidesToShow_tablet !== 'undefined') ? drupalSettings.rocketshipUI_layout_carousel.slidesToShow_tablet : 3,
                }
              },
              {
                breakpoint: 600,
                settings: {
                  slidesToShow: (typeof drupalSettings.rocketshipUI_layout_carousel.slidesToShow_xl_phone !== 'undefined') ? drupalSettings.rocketshipUI_layout_carousel.slidesToShow_xl_phone : 2,
                }
              },
              {
                breakpoint: 480,
                settings: {
                  slidesToShow: (typeof drupalSettings.rocketshipUI_layout_carousel.slidesToShow_phone !== 'undefined') ? drupalSettings.rocketshipUI_layout_carousel.slidesToShow_phone : 1,
                }
              },
            ]
          });

          // Enable autoplay if needed
          if (drupalSettings.rocketshipUI_layout_carousel.autoplay) {
            setTimeout(function () {
              slider.slick('slickPlay');
            }, 5000);
          }
        }
      });

    }
  };

})(jQuery, Drupal, window, document);
