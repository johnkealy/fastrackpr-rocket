<?php

namespace Drupal\disable_language\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
// This is the interface we are going to implement.
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\disable_language\DisableLanguageManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribe to KernelEvents::REQUEST events.
 */
class DisabledLanguagesEventSubscriber implements EventSubscriberInterface {

  /**
   * Aggregated asset routes that should not be redirected.
   */
  const ASSET_ROUTES = ['system.js_asset', 'system.css_asset'];

  /**
   * Contains disable_language.disable_language_manager service.
   *
   * @var \Drupal\disable_language\DisableLanguageManager
   */
  protected $disableLanguageManager;

  /**
   * Contains current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * This module's settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The schemes of all available StreamWrapper.
   *
   * @var array
   */
  protected $schemes;

  /**
   * A plugin manager for conditions plugins.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * DisabledLanguagesEventSubscriber constructor.
   *
   * @param \Drupal\disable_language\DisableLanguageManager $disableLanguageManager
   *   Class DisableLanguageManager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   A proxied implementation of AccountInterface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   A plugin manager for conditions plugins.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $streamWrapperManager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    DisableLanguageManager $disableLanguageManager,
    AccountProxyInterface $currentUser,
    ConfigFactoryInterface $configFactory,
    ConditionManager $conditionManager,
    StreamWrapperManager $streamWrapperManager,
    LanguageManagerInterface $languageManager) {
    $this->currentUser = $currentUser;
    $this->disableLanguageManager = $disableLanguageManager;
    $this->config = $configFactory->get('disable_language.settings');
    $this->conditionManager = $conditionManager;
    $this->schemes = array_keys($streamWrapperManager->getWrappers());
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // On a normal request.
    $events[KernelEvents::REQUEST][] = ['checkForDisabledLanguageAndRedirect'];
    // On an access denied request.
    $events[KernelEvents::EXCEPTION][] = [
      'checkForDisabledLanguageAndRedirect',
      0,
    ];
    return $events;
  }

  /**
   * Check if the current request should be redirected.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function checkForDisabledLanguageAndRedirect(RequestEvent $event) {
    // Do not redirect if this is a file.
    $params = $event->getRequest()->attributes->all();
    if (isset($params['scheme']) && in_array($params['scheme'], $this->schemes)) {
      return;
    }
    // Do not redirect aggregated CSS/JS files.
    if (in_array(RouteMatch::createFromRequest($event->getRequest())->getRouteName(), self::ASSET_ROUTES)) {
      return;
    }
    elseif ($this->isPathExcluded()) {
      return;
    }
    elseif (!$this->currentUser->hasPermission('view disabled languages')) {
      if ($this->disableLanguageManager->isCurrentLanguageDisabled()) {
        // Get the configured redirect language if there is one.
        $redirect_language = $this->disableLanguageManager->getFallbackLanguage();
        if ($redirect_language) {
          $language = $this->languageManager->getLanguage($redirect_language);
        }
        else {
          $language = $this->disableLanguageManager->getFirstEnabledLanguage();
        }
        if (isset($language)) {
          // Create url object to redirect to in the correct language. By
          // default we redirect to the frontpage.
          $url = Url::fromRoute('<front>', [], ['language' => $language]);
          // Check our configuration to see which routes should redirect to the
          // current page but in the correct language instead of the frontpage.
          $redirectOverrideRoutes = $this->config->get('redirect_override_routes');
          if (!empty($redirectOverrideRoutes)) {
            $routeMatch = RouteMatch::createFromRequest($event->getRequest());
            $routeName = $routeMatch->getRouteName();
            if (in_array($routeName, $redirectOverrideRoutes)) {
              $url = Url::fromRoute(
                    $routeMatch->getRouteName(),
                    $routeMatch->getRawParameters()->all(),
                    [
                      'language' => $language,
                      'query' => $event->getRequest()->query->all(),
                    ]
                );
            }
          }
          // Set the response.
          $cache = new CacheableMetadata();
          $cache->addCacheContexts(['languages', 'url', 'user.permissions']);
          $cache->addCacheableDependency($this->config);
          $cache->addCacheableDependency($language);
          $response = new TrustedRedirectResponse($url->toString(), '307');
          $response->addCacheableDependency($cache);
          $event->setResponse($response);
        }
      }
    }
  }

  /**
   * Check if the path belong to the list of excluded ones.
   *
   * @return bool
   *   Whether or not the requested path being accessed is excluded.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @throws \Drupal\Core\Executable\ExecutableException
   */
  private function isPathExcluded() {
    if (($excluded_path_config = $this->config->get('exclude_request_path')) && !empty($excluded_path_config['pages'])) {
      /**
* @var \Drupal\system\Plugin\Condition\RequestPath $condition
*/
      $condition = $this->conditionManager->createInstance('request_path');
      $condition->setConfiguration($excluded_path_config);
      return $this->conditionManager->execute($condition);
    }

    return FALSE;
  }

}
