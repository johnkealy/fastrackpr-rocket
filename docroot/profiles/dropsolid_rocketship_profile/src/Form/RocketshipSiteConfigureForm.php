<?php

namespace Drupal\dropsolid_rocketship_profile\Form;

use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserNameValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the site configuration form.
 */
class RocketshipSiteConfigureForm extends ConfigFormBase {

  /**
   * Constructs a new SiteConfigureForm.
   *
   * @param string $root
   *   The app root.
   * @param string $sitePath
   *   The site path.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer.
   * @param \Drupal\user\UserNameValidator $userNameValidator
   *   The user validator.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The country manager.
   */
  public function __construct(
    protected $root,
    protected $sitePath,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleInstallerInterface $moduleInstaller,
    protected UserNameValidator $userNameValidator,
    protected StateInterface $state,
    protected CountryManagerInterface $countryManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->getParameter('app.root'),
      $container->getParameter('site.path'),
      $container->get('entity_type.manager'),
      $container->get('module_installer'),
      $container->get('user.name_validator'),
      $container->get('state'),
      $container->get('country_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rocketship_install_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'system.date',
      'system.site',
      'update.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Configure site');

    // Warn about settings.php permissions risk.
    $settings_dir = $this->sitePath;
    $settings_file = $settings_dir . '/settings.php';
    // Check that $_POST is empty so we only show this message when the form is
    // first displayed, not on the next page after it is submitted. (We do not
    // want to repeat it multiple times because it is a general warning that is
    // not related to the rest of the installation process; it would also be
    // especially out of place on the last page of the installer, where it would
    // distract from the message that the Drupal installation has completed
    // successfully.)
    $post_params = $this->getRequest()->request->all();
    if (empty($post_params) && (!drupal_verify_install_file($this->root . '/' . $settings_file, FILE_EXIST | FILE_READABLE | FILE_NOT_WRITABLE) || !drupal_verify_install_file($this->root . '/' . $settings_dir, FILE_NOT_WRITABLE, 'dir'))) {
      $this->messenger()->addWarning(
        t(
          'All necessary changes to %dir and %file have been made, so you should remove write permissions to them now in order to avoid security risks. If you are unsure how to do so, consult the <a href=":handbook_url">online handbook</a>.', [
            '%dir' => $settings_dir,
            '%file' => $settings_file,
            ':handbook_url' => 'https://www.drupal.org/server-permissions',
          ]
        )
      );
    }

    $form['#attached']['library'][] = 'system/drupal.system';
    // Add JavaScript time zone detection.
    $form['#attached']['library'][] = 'core/drupal.timezone';
    // We add these strings as settings because JavaScript translation does not
    // work during installation.
    $form['#attached']['drupalSettings']['copyFieldValue']['edit-site-mail'] = ['edit-account-mail'];

    $form['site_information'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Site information'),
    ];
    $form['site_information']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#default_value' => $this->config('system.site')->get('name'),
      '#required' => TRUE,
      '#weight' => -20,
    ];
    $form['site_information']['site_mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Site email address'),
      '#default_value' => $this->config('system.site')->get('mail'),
      '#description' => $this->t("Automated emails, such as registration information, will be sent from this address. Use an address ending in your site's domain to help prevent these emails from being flagged as spam."),
      '#required' => TRUE,
      '#weight' => -15,
    ];

    $form['admin_account'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Site maintenance account'),
    ];

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load(1);

    $form['admin_account']['account']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
      '#required' => TRUE,
      '#attributes' => ['class' => ['username']],
      '#default_value' => $user->getAccountName(),
    ];
    $form['admin_account']['account']['pass'] = [
      '#type' => 'container',
      '#markup' => $this->t('A secure password will be generated for this user. To increase security, logging in as user 1 should only be done using drush'),
    ];
    $form['admin_account']['account']['#tree'] = TRUE;
    $form['admin_account']['account']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#default_value' => $user->getEmail(),
      '#required' => TRUE,
    ];

    $form['regional_settings'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Regional settings'),
    ];
    $countries = $this->countryManager->getList();
    $form['regional_settings']['site_default_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Default country'),
      '#empty_value' => '',
      '#default_value' => $this->config('system.date')->get('country.default'),
      '#options' => $countries,
      '#description' => $this->t('Select the default country for the site.'),
      '#weight' => 0,
    ];
    $form['regional_settings']['date_default_timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Default time zone'),
      // Use system timezone if set, but avoid throwing a warning in PHP >=5.4.
      '#default_value' => $this->config('system.date')->get('timezone.default'),
      '#options' => TimeZoneFormHelper::getOptionsListByRegion(),
      '#description' => $this->t('By default, dates in this site will be displayed in the chosen time zone.'),
      '#weight' => 5,
      '#attributes' => ['class' => ['timezone-detect']],
    ];

    $form['update_notifications'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Update notifications'),
      '#description' => $this->t('The system will notify you when updates and important security releases are available for installed components. Anonymous information about your site is sent to <a href=":drupal">Drupal.org</a>.', [':drupal' => 'https://www.drupal.org']),
    ];
    $form['update_notifications']['enable_update_status_module'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check for updates automatically'),
      '#default_value' => 1,
    ];
    $form['update_notifications']['enable_update_status_emails'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Receive email notifications'),
      '#default_value' => FALSE,
      '#states' => [
        'visible' => [
          'input[name="enable_update_status_module"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#weight' => 15,
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $violations = $this->userNameValidator->validateName($form_state->getValue(['account', 'name']));
    if ($violations->count() > 0) {
      $form_state->setErrorByName('account][name', $violations[0]->getMessage());
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.site')
      ->set('name', (string) $form_state->getValue('site_name'))
      ->set('mail', (string) $form_state->getValue('site_mail'))
      ->save(TRUE);

    $this->config('system.date')
      ->set('timezone.default', (string) $form_state->getValue('date_default_timezone'))
      ->set('country.default', (string) $form_state->getValue('site_default_country'))
      ->save(TRUE);

    $account_values = $form_state->getValue('account');

    // Enable update.module if this option was selected.
    $update_status_module = $form_state->getValue('enable_update_status_module');
    if ($update_status_module) {
      $this->moduleInstaller->install(['file', 'update'], FALSE);
      // Never send emails for updates!
      $this->config('update.settings')->set('notification.emails', [])->save(TRUE);
    }

    // We precreated user 1 with placeholder values. Let's save the real values.
    $account = $this->entityTypeManager->getStorage('user')->load(1);
    $account->init = $account->mail = $account_values['mail'];
    $account->roles = $account->getRoles();
    $account->activate();
    $account->timezone = $form_state->getValue('date_default_timezone');
    $account->pass = \Drupal::service('password_generator')->generate(20);
    $account->name = $account_values['name'];
    $account->save();

    // Record when this install ran.
    $this->state->set('install_time', $_SERVER['REQUEST_TIME']);
  }

}
