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

  // set up an array to save listeners for masonry grid for each instance
  self.magicGrids = [];
  self.magicGridsLoaded = false;
  self.screen = self.screen || '';

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Photo Gallery: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUI_cbPhotoGallery = {
    attach: function (context, settings) {

      var block = $('.block--type-cb-photo-gallery'),
        loadMoreParagraph = $('.block.has--load-more'),
        blockMasonry = $('.block--view-mode-photo-gallery-masonry');

      // check for masonry layout
      // only fire on non-phone screens
      if (block.length) {
        self.masonryCheck(block, context);
      }

      // add load more functionality
      if (block.length) self.loadMoreFieldItems(block, context);
    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Photo Gallery: functions
  ///////////////////////////////////////////////////////////////////////

  self.masonryCheck = function(block, context) {
    if (self.screen !== 'xs') {
      self.masonry(block, context);
    }

    window.rocketshipUI.optimizedResize().add(function() {

      if (self.screen === 'xs') {
        self.masonryReset(block, context);
      } else {
        self.masonry(block, context);
      }
    });
  };

  /**
   * Make a horizontal Masonry layout
   *
   * Uses a library 'Magic Grid', accessed via a CDN
   * https://github.com/e-oj/Magic-Grid
   *
   * @param block
   * @param loadMore
   * @param context
   */
  self.masonry = function(block, context) {

    var total = block.length;

    // check all photogallery blocks to see if they are in masonry view mode
    once('js-photo-gallery-masonryCheck', block).forEach((element, i) => {

      var block = $(element),
        id = 'p-photogallery-' + i;

      block.attr('id', id);

      self.magicGrids[i] = null;

      // set and save the listener in an array
      if (block.hasClass('block--view-mode-photo-gallery-masonry')) {

        if (block.hasClass('is-reset')) {
          block.removeClass('is-reset');
        }

        // wait for images to load

        if ('loading' in HTMLImageElement.prototype) {

          self.triggerMagicGrid(i);

        } else {
          self.imgLoaded($('.media--view-mode-paragraph-p009-image-masonry'), function() {
            self.triggerMagicGrid(i);
          });
        }

      } else {
        self.magicGrids[i] = null;
      }
    });

  };

  self.triggerMagicGrid = function(i) {
    self.magicGrids[i] = new MagicGrid({
      container: '#p-photogallery-' + i + ' .field--name-field-cb-media-unlimited .field__items',
      animate: true,
      gutter: 0,
      static: true
    });

    if (typeof self.magicGrids[i] !== 'undefined' && self.magicGrids[i] !== null) {
      self.magicGrids[i].listen();
    }

  };

  /**
   * Remove any masonry inline styling,
   * since the lib doesn't have a destory function
   * TO DO: either replace the JS solution or fork it to do some mods,
   * like adding a destroy() method
   *
   * @param {} block
   * @param {*} contenxt
   */
  self.masonryReset = function(block, contenxt) {

    // check for magicGrids and destroy them

    block.each(function (i) {

      var gallery = $(this);

      if (gallery.attr('id') === 'p-photogallery-' + i && !gallery.hasClass('is-reset') ) {

        var timer = setTimeout(function(){

          clearTimeout(timer);

          // because of the delay, we need to check again
          // if not on a wider screen by now
          if (self.screen === 'xs') {

            gallery.find('.field__items--name-field-cb-media-unlimited').attr('style', '');
            gallery.find('.field__item--name-field-cb-media-unlimited').attr('style', '');

            gallery.addClass('is-reset');
          }

        }, 200);

        timer;

      }

    });

  }


  /**
   * Hide everything but the limited amount of field items
   * show/hide on click of a 'load more' field
   *
   * NOTE: we reload the masonry items when showing more items
   *
   */
  self.loadMoreFieldItems = function(block, context) {

    // by default, all field items > the limit should by hidden using CSS
    // when there is a load-more button

    once('js-photo-gallery-loadMoreCheck', block).forEach(function (blockElement, i) {

      var block = $(blockElement);

      var loadMoreButton = block.find('.field--name-field-cb-photo-gallery-load-more', context);

      if (loadMoreButton.length && block.hasClass('has--load-more')) {

        once('js-load-more', loadMoreButton).forEach(function (buttonElement) {
          $(buttonElement).on('click', function (e) {

            var itemLimit = block.data('limit');

            // loop the field items
            //
            // remove visibility class if they have one
            // add class if they don't

            if (block.hasClass('has--visible-items')) {
              block.removeClass('has--visible-items');
            } else {
              block.addClass('has--visible-items');
            }

            $('.field__item', block).each(function (index) {

              var item = $(this);

              // remove the classes
              if (index > parseInt(itemLimit - 1)) {

                if (item.hasClass('is--visible')) {
                  $(this).removeClass('is--visible');
                } else {
                  $(this).addClass('is--visible');
                }
              }
            });

            if (self.screen === 'xs') {
              self.masonryReset(block, context);
            } else {
              // if in Masonry mode, retrigger the magic so the images reflow with the newly visible images in it
              if (block.hasClass('block--view-mode-photo-gallery-masonry')) {

                if (typeof self.magicGrids[i] !== 'undefined' && self.magicGrids[i] !== null) {
                  // reposition items
                  self.magicGrids[i].positionItems();
                }
              }
            }


            e.preventDefault();

          });
        });
      }

    });

  };

})(jQuery, Drupal, window, document);
