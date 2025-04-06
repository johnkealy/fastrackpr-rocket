<?php

namespace Drupal\node_keep\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Node Keep settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_keep_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['node_keep.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['hide_warning_messages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide warning messages'),
      '#description' => $this->t('Hide warning messages about "limited access permissions" shown on the node edit/delete page.'),
      '#default_value' => $this->config('node_keep.settings')->get('hide_warning_messages'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('node_keep.settings')
      ->set('hide_warning_messages', $form_state->getValue('hide_warning_messages'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
