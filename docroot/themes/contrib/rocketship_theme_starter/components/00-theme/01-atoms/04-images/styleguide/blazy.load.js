(function (Drupal, drupalSettings, $, window, document) {
  Drupal.behaviors.blazyLoad = {
    attach: function (context) {
      once('js-blazy-load', '.b-lazy').forEach(function(blazyElement, i) {
        var el = $(blazyElement),
            loadingWrapper = el.closest('.media--loading'),
            field = el.closest('.field.blazy');

        // mark their field
        field.addClass('blazy--on');

        if (i === 0) {
          field.addClass('blazy--first');
        }

        // Mark as loaded after a certain time + when img's are loaded

        var blazyTimout =
          setTimeout(function() {
            clearTimeout(blazyTimout);

            // if it's an image
            if (el[0].tagName === 'IMG') {
              // when img source is loaded, mark as loaded
              window.rocketshipUI.imgLoaded (el, function() {
                el.addClass('b-loaded');
                loadingWrapper.removeClass('media--loading');
                loadingWrapper.addClass('is-b-loaded');
              });
            // if it's a picture
            }
            else if(el[0].tagName === 'PICTURE') {
              // find the image inside and wait for that to load
              var image = el.find('img');
              // when img source is loaded, mark as loaded
              window.rocketshipUI.imgLoaded (image, function() {
                el.addClass('b-loaded');
                loadingWrapper.removeClass('media--loading');
                loadingWrapper.addClass('is-b-loaded');
              });
            }
            else {
              el.addClass('b-loaded');
              loadingWrapper.removeClass('media--loading');
              loadingWrapper.addClass('is-b-loaded');
            }
          }, 1000);
      });
    }
  };

}(Drupal, drupalSettings, jQuery, window, document));
