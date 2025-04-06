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
  if (typeof window.rocketshipAdminBlocksUI === 'undefined') { window.rocketshipAdminBlocksUI = {}; }

  var self = window.rocketshipAdminBlocksUI;

  ///////////////////////////////////////////////////////////////////////
  // Cache variables available across the namespace
  ///////////////////////////////////////////////////////////////////////


  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.RocketshipCoreAdminLayouts = {
    attach: function (context, settings) {

      // find the layout pickers:
      //
      // - Text block: alignment of the text
      // - Video stretch
      // - Image stretch
      // - Photo Gallery: grid vs mozaic

      var layoutText = $('[data-drupal-selector="edit-settings-block-form-field-cb-text-alignment"], #edit-field-cb-text-alignment--wrapper .fieldset-wrapper');
      var layoutMedia = $('[data-drupal-selector="edit-settings-block-form-field-cb-media-layout"], #edit-field-cb-media-layout--wrapper .fieldset-wrapper');
      var layoutGroup = $('[data-drupal-selector="edit-settings-view-mode"]');

      // find the blocks that have a text layout option
      var groupTextAlignment = $('.field--name-field-cb-text-alignment');

      // do stuff with it if they exist
      if (layoutText.length > 0) {
        self.layoutPicker(layoutText, 'layout');
      }
      if (layoutMedia.length > 0) {
        self.layoutPicker(layoutMedia, 'layout');
      }
      if (layoutGroup.length > 0) {
        self.layoutPicker(layoutGroup, 'view-mode');
      }

      // Now we can do stuff to override the CKE text alignment buttons
      if (groupTextAlignment.length) {
        self.CKEOverride(groupTextAlignment);
      }

    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: functions
  ///////////////////////////////////////////////////////////////////////

  /**
   * Replace the radiobuttons with images
   * Along with adding and classes to make styling easier
   *
   */
  self.layoutPicker = function(group, prefix) {

    once('js-once-cb-layoutPicker', group).forEach(function(groupElement) {

      var group = $(groupElement);

      if (typeof prefix === 'undefined') {
        prefix = 'layout';
      }

      group.addClass('cb-field-layouts');

      once('js-once-cb-layoutPicker-radio', group.find('input:radio')).forEach(function (inputRadioElement) {
        var optionLabel = $(inputRadioElement).next('label');

        var layout = $(inputRadioElement).val();
        var text = optionLabel.text().replace('_', '-');
        var textClean = text
          // remove first and last space
          .trim()
          // lowercase char only
          .toLowerCase()
          // replace all consecutive spaces with 1 dash
          .replace(/\s+/g, '-')
          // replace or remove various characters or substrings
          .replace('_', '-')
          .replace(':', '-')
          .replace('*', '')
          .replace('--', '-')
          .replace('-(optional)', '')
          .replace(')', '')
          .replace('(', '');

        // wrap the text in a div & put under the radio

        optionLabel.parent().append('<div class="text">' + optionLabel.html() + '</div>');

        // add a class for styling

        var optionClass = layout.replace('_', '-');

        optionLabel.addClass(prefix + '-' + optionClass);
        optionLabel.addClass(prefix + '-' + textClean);

      });

    });

  };

  /**
   * find the blocks that have a text layout option
   * so we can do stuff to override the CKE text alignment buttons
   *
   * @param block
   * @constructor
   */
  self.CKEOverride = function(layout) {

    once('js-once-cb-CKEOverride', layout).forEach(function(layoutElement) {

      var layout = $(layoutElement);
      var textareaField = layout.parent().find('.js-form-type-textarea');

      /**
       * Handle text align behaviour for CKEditor 5.
       */
      const handleCkeditor5 = function (editorId, fieldValue) {
        const alignment = fieldValue === 'centered' ? 'center' : fieldValue;
        const editor = Drupal.CKEditor5Instances.get(editorId);

        if (!editor) {
          return;
        }

        const alignmentButton = editor.ui.view.toolbar.items.find(function (TView, number) {
          return TView.element.classList.contains('ck-alignment-dropdown');
        });

        if (!alignmentButton) {
          return;
        }

        // Disable text alignment button when text in centered so user
        // can't interact it.
        alignmentButton.isEnabled = alignment !== 'center';

        // Do nothing if alignment is already active.
        const firstBlockElement = editor.model.document.selection.getSelectedBlocks().next().value;
        if (firstBlockElement.getAttribute('alignment') === alignment) {
          return;
        }

        editor.execute('selectAll');
        editor.execute('alignment', { value: alignment });
      };

      /**
       * Handle text align behaviour for CKEditor 4.
       *
       * @deprecated
       *   This will be removed then CKEditor 4 support is dropped from RS.
       */
      const handleCkeditor4 = function (editor, value) {
        if (value === 'centered') {

          // add a class to parent so we can
          // hide the cke text alignment buttons
          // to prevent conflicts between our custom layout and cke's own text alignment buttons

          if (!layout.parent().hasClass('override-cke-text-alignment')) {
            layout.parent().addClass('override-cke-text-alignment');
          }

        } else {

          // only do stuff if it was manipulated in the past
          if (layout.parent().hasClass('override-cke-text-alignment')) {
            // remove the class we used to hide the cke buttons
            layout.parent().removeClass('override-cke-text-alignment');
          }
        }

        const text = editor.getData();
        // wrap text in 'centered' classes for CKE to be able to preview centered text

        if (value === 'centered') {

          // wrap content in cke's 'center' class
          // so the cke preview matches our custom centered layout

          const newText = '<div class="text-align-center">' + text + '</div>';

          // textarea.text(newText);
          editor.setData(newText);

          // add a flag to know if it has been changed
          editor['hasRSAlignmentChanges'] = true;
        }
        else {

          // only do stuff if it was manipulated in the past
          if ( typeof editor['hasRSAlignmentChanges'] !== 'undefined' ) {

            // remove the alignment class we set to override the preview

            // remove first instance of our div with alignment class
            var newTextStart = text.replace('<div class="text-align-center">', '');

            // remove last closing div
            var newText = newTextStart.replace(new RegExp("<\/div>([^<\/div>]*)$"), '');

            // replace text in textarea
            // textarea.text(newText);
            editor.setData(newText);

            // add a flag to know if it has been changed
            editor['hasRSAlignmentChanges'] = false;
          }
        }
      };

      const layoutHandler = function (fieldValue) {
        // Wait when DOM is ready.
        setTimeout(function () {
          textareaField.each(function () {
            const field = $(this);
            const textarea = field.find('textarea');
            const ckeditor5Id = textarea.data('ckeditor5-id');

            if (typeof ckeditor5Id !== 'undefined') {
              handleCkeditor5(ckeditor5Id.toString(), fieldValue);
            }
            else if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[textarea.attr('id')]) {
              // Fallback to CKEditor 4 if exists.
              handleCkeditor4(CKEDITOR.instances[textarea.attr('id')], fieldValue);
            }
          });
        }, 0);
      };

      // find the active layout on load, and pass to the layout handler
      once('js-once-cb-CKEOverride-radio', layout.find('input:radio:checked')).forEach(function (checkedInputRadioElement) {

        var value = $(checkedInputRadioElement).val();

        layoutHandler(value);

      });

      // change of layout should also call the layoutHandler
      once('js-once-cb-CKEOverride-radio-change', layout.find('input:radio')).forEach(function (inputRadioElement) {
        $(inputRadioElement).change(function () {

          var value = $(this).val();

          layoutHandler(value);

        });

      });

    });

  }

})(jQuery, Drupal, window, document);
