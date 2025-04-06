<?php

namespace Drupal\config_import_locale\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure the behaviour of interface translations on config import.
 */
class ConfigImportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_import_locale_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['config_import_locale.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config_import_locale.settings');

    $form['overwrite_interface_translation'] = [
      '#type' => 'radios',
      '#title' => $this->t('Interface overwrite behaviour'),
      '#description' => $this->t('Choose what happens to interface translations on config import.'),
      '#options' => [
        'default' => $this->t('<b>Default:</b> Interface translations may be overwritten if the string is imported via config import.'),
        'no_overwrite' => $this->t('<b>No overwrites:</b> Existing interface translations will be kept, new translations may be added.'),
        'nothing' => $this->t('<b>Nothing:</b> Config imports will never add / change any interface translations.'),
      ],
      '#default_value' => $config->get('overwrite_interface_translation'),
    ];

    $form['overwrite_config_translation'] = [
      '#type' => 'radios',
      '#title' => $this->t('Config overwrite behaviour'),
      '#description' => $this->t('Choose what happens to config translations on interface translation updates.'),
      '#options' => [
        'default' => $this->t('<b>Default:</b> Config translations may be overwritten if the string is updated via interface translations.'),
        'nothing' => $this->t('<b>Nothing:</b> Interface translation updates will never add / change any config translations.'),
      ],
      '#default_value' => $config->get('overwrite_config_translation'),
    ];

    $form['overwrite_context'] = [
      '#type' => 'select',
      '#title' => $this->t('Import context'),
      '#description' => $this->t("Choose where this module's behaviour should apply"),
      '#options' => [
        'cli' => $this->t('Only in CLI'),
        'ui' => $this->t('Only in UI'),
      ],
      '#empty_option' => $this->t('Everywhere'),
      '#default_value' => $config->get('overwrite_context'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('config_import_locale.settings')
      ->set('overwrite_interface_translation', $form_state->getValue('overwrite_interface_translation'))
      ->set('overwrite_config_translation', $form_state->getValue('overwrite_config_translation'))
      ->set('overwrite_context', $form_state->getValue('overwrite_context'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
