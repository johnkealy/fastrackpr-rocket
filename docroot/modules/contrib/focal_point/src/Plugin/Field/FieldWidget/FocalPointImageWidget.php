<?php

namespace Drupal\focal_point\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\crop\Entity\Crop;
use Drupal\focal_point\FocalPointManagerInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image_focal_point' widget.
 *
 * @FieldWidget(
 *   id = "image_focal_point",
 *   label = @Translation("Image (Focal Point)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class FocalPointImageWidget extends ImageWidget {

  const PREVIEW_TOKEN_NAME = 'focal_point_preview';

  /**
   * The Focal Point manager.
   *
   * @var \Drupal\focal_point\FocalPointManagerInterface
   */
  protected $focalPointManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ElementInfoManagerInterface $element_info,
    ImageFactory $image_factory = NULL,
    FocalPointManagerInterface $focal_point_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info, $image_factory);
    $this->focalPointManager = $focal_point_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('element_info'),
      $container->get('image.factory'),
      $container->get('focal_point.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'progress_indicator' => 'throbber',
      'preview_image_style' => 'thumbnail',
      'preview_link' => TRUE,
      'preview_styles' => [],
      'offsets' => '50,50',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    // We need a preview image for this widget.
    $form['preview_image_style']['#required'] = TRUE;
    unset($form['preview_image_style']['#empty_option']);
    // @todo Implement https://www.drupal.org/node/2872960
    //   The preview image should not be generated using a focal point effect
    //   and should maintain the aspect ratio of the original image.
    // phpcs:disable
    $form['preview_image_style']['#description'] = t(
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $form['preview_image_style']['#description']->getUntranslatedString() . "<br/>Do not choose an image style that alters the aspect ratio of the original image nor an image style that uses a focal point effect.",
      $form['preview_image_style']['#description']->getArguments(),
      $form['preview_image_style']['#description']->getOptions()
    );
    // phpcs:enable

    $form['preview_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display preview link'),
      '#default_value' => $this->getSetting('preview_link'),
      '#weight' => 30,
    ];

    $form['preview_styles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Image styles in preview'),
      '#description' => $this->t('Limit the image styles to be used in preview. Leave blank to use all the available styles.'),
      '#default_value' => $this->getSetting('preview_styles'),
      '#weight' => 32,
      '#options' => $this->focalPointManager->getFocalPointImageStyles(),
      '#element_validate' => [[$this, 'validateImageStylesWidget']],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][preview_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['offsets'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default focal point value'),
      '#default_value' => $this->getSetting('offsets'),
      '#description' => $this->t('Specify the default focal point of this widget in the form "leftoffset,topoffset" where offsets are in percentages. Ex: 25,75.'),
      '#size' => 7,
      '#maxlength' => 7,
      '#element_validate' => [[$this, 'validateFocalPointWidget']],
      '#required' => TRUE,
      '#weight' => 35,
    ];

    return $form;
  }

  /**
   * Image styles form element validation, filters the #value property.
   */
  public static function validateImageStylesWidget(array &$element, FormStateInterface $form_state) {
    $element['#value'] = array_filter($element['#value']);
    $form_state->setValueForElement($element, $element['#value']);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $has_preview = $this->getSetting('preview_link');
    $preview_status = $has_preview ? $this->t('Yes') : $this->t('No');
    $summary[] = $this->t('Preview link: @status', ['@status' => $preview_status]);
    if ($has_preview) {
      $preview_styles = implode(', ', $this->getSetting('preview_styles'));
      $summary[] = $this->t('Preview styles: @styles', ['@styles' => empty($preview_styles) ? $this->t('- All -') : $preview_styles]);
    }

    $offsets = $this->getSetting('offsets');
    $summary[] = $this->t('Default focal point: @offsets', ['@offsets' => $offsets]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#focal_point'] = [
      'preview_link' => $this->getSetting('preview_link'),
      'offsets' => $this->getSetting('offsets'),
      'preview_styles' => $this->getSetting('preview_styles'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Processes an image_focal_point field Widget.
   *
   * Expands the image_focal_point Widget to include the focal_point field.
   * This method is assigned as a #process callback in formElement() method.
   *
   * @todo Implement https://www.drupal.org/node/2657592
   *   Convert focal point selector tool into a standalone form element.
   * @todo Implement https://www.drupal.org/node/2848511
   *   Focal Point offsets not accessible by keyboard.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];
    $element_selectors = [
      'focal_point' => 'focal-point-' . implode('-', $element['#parents']),
    ];

    $default_focal_point_value = $item['focal_point'] ?? $element['#focal_point']['offsets'];

    // Override the default Image Widget template when using the Media Library
    // module so we can use the image field's preview rather than the preview
    // provided by Media Library.
    if ($form['#form_id'] == 'media_library_upload_form' || $form['#form_id'] == 'media_library_add_form') {
      $element['#theme'] = 'focal_point_media_library_image_widget';
      unset($form['media'][0]['preview']);
    }

    // Add the focal point indicator to preview.
    if (isset($element['preview'])) {
      $preview = [
        'indicator' => self::createFocalPointIndicator($element['#delta'], $element_selectors),
        'thumbnail' => $element['preview'],
      ];

      // Even for image fields with a cardinality higher than 1 the correct fid
      // can always be found in $item['fids'][0].
      $fid = $item['fids'][0] ?? '';
      if ($element['#focal_point']['preview_link'] && !empty($fid)) {
        $preview['preview_link'] = self::createPreviewLink($fid, $element['#field_name'], $element_selectors, $default_focal_point_value, $element['#focal_point']['preview_styles']);
      }

      // Use the existing preview weight value so that the focal point indicator
      // and thumbnail appear in the correct order.
      $preview['#weight'] = $element['preview']['#weight'] ?? 0;
      unset($preview['thumbnail']['#weight']);

      $element['preview'] = $preview;
    }

    // Add the focal point field.
    $element['focal_point'] = self::createFocalPointField($element['#field_name'], $element_selectors, $default_focal_point_value);

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Form API callback. Retrieves the value for the file_generic field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    $return = parent::value($element, $input, $form_state);

    // When an element is loaded, focal_point needs to be set. During a form
    // submission the value will already be there.
    if (isset($return['target_id']) && !isset($return['focal_point'])) {
      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::service('entity_type.manager')
        ->getStorage('file')
        ->load($return['target_id']);
      if ($file) {
        $crop_type = \Drupal::config('focal_point.settings')->get('crop_type');
        $crop = Crop::findCrop($file->getFileUri(), $crop_type);
        if ($crop) {
          $anchor = \Drupal::service('focal_point.manager')
            ->absoluteToRelative($crop->x->value, $crop->y->value, $return['width'], $return['height']);
          $return['focal_point'] = "{$anchor['x']},{$anchor['y']}";
        }
      }
      else {
        \Drupal::logger('focal_point')->notice("Attempted to get a focal point value for an invalid or temporary file.");
        $return['focal_point'] = $element['#focal_point']['offsets'];
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   *
   * Validation Callback; Focal Point process field.
   */
  public static function validateFocalPoint($element, FormStateInterface $form_state) {
    if (empty($element['#value']) || (FALSE === \Drupal::service('focal_point.manager')->validateFocalPoint($element['#value']))) {
      $replacements = ['@title' => strtolower($element['#title'])];
      $form_state->setError($element, new TranslatableMarkup('The @title field should be in the form "leftoffset,topoffset" where offsets are in percentages. Ex: 25,75.', $replacements));
    }
  }

  /**
   * {@inheritdoc}
   *
   * Validation Callback; Focal Point widget setting.
   */
  public function validateFocalPointWidget(array &$element, FormStateInterface $form_state) {
    static::validateFocalPoint($element, $form_state);
  }

  /**
   * Create and return a token to use for accessing the preview page.
   *
   * @return string
   *   A valid token.
   *
   * @codeCoverageIgnore
   */
  public static function getPreviewToken() {
    return \Drupal::csrfToken()->get(self::PREVIEW_TOKEN_NAME);
  }

  /**
   * Validate a preview token.
   *
   * @param string $token
   *   A drupal generated token.
   *
   * @return bool
   *   True if the token is valid.
   *
   * @codeCoverageIgnore
   */
  public static function validatePreviewToken($token) {
    return \Drupal::csrfToken()->validate($token, self::PREVIEW_TOKEN_NAME);
  }

  /**
   * Create the focal point form element.
   *
   * @param string $field_name
   *   The name of the field element for the image field.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   * @param string $default_focal_point_value
   *   The default focal point value in the form x,y.
   *
   * @return array
   *   The preview link form element.
   */
  private static function createFocalPointField($field_name, array $element_selectors, $default_focal_point_value) {
    $field = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Focal point'),
      '#description' => new TranslatableMarkup('Specify the focus of this image in the form "leftoffset,topoffset" where offsets are in percents. Ex: 25,75'),
      '#default_value' => $default_focal_point_value,
      '#element_validate' => [[static::class, 'validateFocalPoint']],
      '#attributes' => [
        'class' => ['focal-point', $element_selectors['focal_point']],
        'data-selector' => $element_selectors['focal_point'],
        'data-field-name' => $field_name,
      ],
      '#wrapper_attributes' => [
        'class' => ['focal-point-wrapper'],
      ],
      '#attached' => [
        'library' => ['focal_point/drupal.focal_point'],
      ],
    ];

    return $field;
  }

  /**
   * Create the focal point form element.
   *
   * @param int $delta
   *   The delta of the image field widget.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   *
   * @return array
   *   The focal point field form element.
   */
  private static function createFocalPointIndicator($delta, array $element_selectors) {
    $indicator = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['focal-point-indicator'],
        'data-selector' => $element_selectors['focal_point'],
        'data-delta' => $delta,
      ],
    ];

    return $indicator;
  }

  /**
   * Create the preview link form element.
   *
   * @param int $fid
   *   The fid of the image file.
   * @param string $field_name
   *   The name of the field element for the image field.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   * @param string $default_focal_point_value
   *   The default focal point value in the form x,y.
   * @param array $preview_image_styles
   *    (Optional) Array of image styles ID to use. Defaults to any available.
   *
   * @return array
   *   The preview link form element.
   */
  private static function createPreviewLink($fid, $field_name, array $element_selectors, $default_focal_point_value, array $preview_image_styles = []) {
    // Replace comma (,) with an x to make javascript handling easier.
    $preview_focal_point_value = str_replace(',', 'x', $default_focal_point_value);

    // Create a token to be used during an access check on the preview page.
    $token = self::getPreviewToken();

    $preview_link_url_query = ['focal_point_token' => $token];
    if (!empty($preview_image_styles)) {
      $preview_link_url_query['image_styles'] = implode(',', $preview_image_styles);
    }

    $preview_link = [
      '#type' => 'link',
      '#title' => new TranslatableMarkup('Preview'),
      '#url' => new Url('focal_point.preview',
        [
          'fid' => $fid,
          'focal_point_value' => $preview_focal_point_value,
        ],
        [
          'query' => $preview_link_url_query,
        ]),
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
      '#attributes' => [
        'class' => ['focal-point-preview-link', 'use-ajax'],
        'data-selector' => $element_selectors['focal_point'],
        'data-field-name' => $field_name,
        'data-dialog-type' => 'modal',
        'target' => '_blank',
      ],
    ];

    return $preview_link;
  }

}
