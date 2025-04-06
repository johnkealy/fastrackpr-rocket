<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\layout_builder\SectionComponent
 * @group layout_builder
 */
class SectionComponentTest extends UnitTestCase {

  /**
   * The section object to test.
   *
   * @var \Drupal\layout_builder\Section
   */
  protected $section;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $component = new SectionComponent(
      'some-uuid',
      'some-region',
      ['id' => 'existing-block-id'],
      [],
      [
        'Initech' => [
          'Bill Lumbergh' => 'TPS reports',
          'Milton Waddams' => 'Red Stapler',
        ],
        'Chotchkies' => [
          'flair' => TRUE,
        ],
      ]
    );

    $this->section = new Section('layout_onecol', [], [$component]);
  }

  /**
   * @covers ::toRenderArray
   */
  public function testToRenderArray(): void {
    $existing_block = $this->prophesize(BlockPluginInterface::class);
    $existing_block->getPluginId()->willReturn('block_plugin_id');

    $block_manager = $this->prophesize(BlockManagerInterface::class);
    $block_manager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($existing_block->reveal());

    // Imitate an event subscriber by setting a resulting build on the event.
    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $event_dispatcher
      ->dispatch(Argument::type(SectionComponentBuildRenderArrayEvent::class), LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY)
      ->shouldBeCalled()
      ->will(function ($args) {
        /** @var \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event */
        $event = $args[0];
        $event->setBuild(['#markup' => $event->getPlugin()->getPluginId()]);
        return $event;
      });

    $layout_plugin = $this->prophesize(LayoutInterface::class);
    $layout_plugin->build(Argument::type('array'))->willReturnArgument(0);

    $layout_manager = $this->prophesize(LayoutPluginManagerInterface::class);
    $layout_manager->createInstance('layout_onecol', [])->willReturn($layout_plugin->reveal());

    $container = new ContainerBuilder();
    $container->set('plugin.manager.block', $block_manager->reveal());
    $container->set('event_dispatcher', $event_dispatcher->reveal());
    $container->set('plugin.manager.core.layout', $layout_manager->reveal());
    \Drupal::setContainer($container);

    $expected = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
      '#markup' => 'block_plugin_id',
    ];

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $result = $component->toRenderArray();
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::getThirdPartySettings
   * @dataProvider providerTestGetThirdPartySettings
   */
  public function testGetThirdPartySettings($provider, $expected): void {
    $this->assertSame($expected, $this->section->getComponent('some-uuid')->getThirdPartySettings($provider));
  }

  /**
   * Provides test data for ::testGetThirdPartySettings().
   *
   * @return array
   *   Third party settings.
   */
  public function providerTestGetThirdPartySettings(): array {
    $data = [];
    $data['Initech third party settings'] = [
      'Initech',
      [
        'Bill Lumbergh' => 'TPS reports',
        'Milton Waddams' => 'Red Stapler',
      ],
    ];
    $data['Chotchkies third party settings'] = [
      'Chotchkies',
      ['flair' => TRUE],
    ];
    $data['Nonexisting provider'] = [
      'non_existing_provider',
      [],
    ];
    return $data;
  }

  /**
   * @covers ::getThirdPartySetting
   * @dataProvider providerTestGetThirdPartySetting
   */
  public function testGetThirdPartySetting($provider, $key, $expected, $default = FALSE): void {
    if ($default) {
      $this->assertSame($expected, $this->section->getComponent('some-uuid')->getThirdPartySetting($provider, $key, $default));
      return;
    }
    $this->assertSame($expected, $this->section->getComponent('some-uuid')->getThirdPartySetting($provider, $key));
  }

  /**
   * Provides test data for ::testGetThirdPartySetting().
   *
   * @return array
   *   Third party settings.
   */
  public function providerTestGetThirdPartySetting(): array {
    $data = [];
    $data['Initech third party setting for "Bill Lumbergh" key'] = [
      'Initech',
      'Bill Lumbergh',
      'TPS reports',
    ];
    $data['Chotchkies third party setting for "flair" key'] = [
      'Chotchkies',
      'flair',
      TRUE,
    ];
    $data['Chotchkies third party setting for nonexisting key'] = [
      'Chotchkies',
      'non_existing_key',
      NULL,
    ];
    $data['Nonexisting provider third party setting for nonexisting key'] = [
      'non_existing_provider',
      'non_existing_key',
      NULL,
    ];
    $data['Nonexisting provider third party setting for nonexisting key with a default value provided'] = [
      'non_existing_provider',
      'non_existing_key',
      'default value',
      'default value',
    ];
    return $data;
  }

  /**
   * @covers ::setThirdPartySetting
   * @dataProvider providerTestSetThirdPartySetting
   */
  public function testSetThirdPartySetting($provider, $key, $value, $expected): void {
    $this->section->getComponent('some-uuid')->setThirdPartySetting($provider, $key, $value);
    $this->assertSame($expected, $this->section->getComponent('some-uuid')->getThirdPartySettings($provider));
  }

  /**
   * Provides test data for ::testSetThirdPartySettings().
   *
   * @return array
   *   Third party settings.
   */
  public function providerTestSetThirdPartySetting(): array {
    $data = [];
    $data['Override "Milton Waddams" third party setting for Initech provider'] = [
      'Initech',
      'Milton Waddams',
      'Storage B',
      [
        'Bill Lumbergh' => 'TPS reports',
        'Milton Waddams' => 'Storage B',
      ],
    ];
    $data['Add "Peter Gibbons" third party setting for Initech provider'] = [
      'Initech',
      'Peter Gibbons',
      'Programmer',
      [
        'Bill Lumbergh' => 'TPS reports',
        'Milton Waddams' => 'Red Stapler',
        'Peter Gibbons' => 'Programmer',
      ],
    ];
    $data['Add "Medical Providers" provider third party settings'] = [
      'Medical Providers',
      'Dr. Swanson',
      'Hypnotist',
      [
        'Dr. Swanson' => 'Hypnotist',
      ],
    ];
    return $data;
  }

  /**
   * @covers ::unsetThirdPartySetting
   * @dataProvider providerTestUnsetThirdPartySetting
   */
  public function testUnsetThirdPartySetting($provider, $key, $expected): void {
    $this->section->getComponent('some-uuid')->unsetThirdPartySetting($provider, $key);
    $this->assertSame($expected, $this->section->getComponent('some-uuid')->getThirdPartySettings($provider));
  }

  /**
   * Provides test data for ::testUnsetThirdPartySetting().
   *
   * @return array
   *   Third party settings.
   */
  public function providerTestUnsetThirdPartySetting(): array {
    $data = [];
    $data['Key with values'] = [
      'Initech',
      'Bill Lumbergh',
      [
        'Milton Waddams' => 'Red Stapler',
      ],
    ];
    $data['Key without values'] = [
      'Chotchkies',
      'flair',
      [],
    ];
    $data['Non-existing key'] = [
      'Chotchkies',
      'non_existing_key',
      [
        'flair' => TRUE,
      ],
    ];
    $data['Non-existing provider'] = [
      'non_existing_provider',
      'non_existing_key',
      [],
    ];
    return $data;
  }

  /**
   * @covers ::getThirdPartyProviders
   */
  public function testGetThirdPartyProviders(): void {
    $this->assertSame(['Initech', 'Chotchkies'], $this->section->getComponent('some-uuid')->getThirdPartyProviders());
    $this->section->getComponent('some-uuid')->unsetThirdPartySetting('Chotchkies', 'flair');
    $this->assertSame(['Initech'], $this->section->getComponent('some-uuid')->getThirdPartyProviders());
  }

  /**
   * Tests that deprecation notices are triggered.
   *
   * @group legacy
   *
   * @todo Remove below test when the drupal:10.1.x branch is opened.
   * @see https://www.drupal.org/project/drupal/issues/3160644
   */
  public function testDeprecationNotices(): void {
    $this->expectDeprecation('Setting random section component properties is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Component properties should be set via dedicated setters. See https://www.drupal.org/node/3100177');
    $this->expectDeprecation('Getting additional properties is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Additional component properties should be gotten via ::getThirdPartySetting(). See https://www.drupal.org/node/3100177');

    // Instantiate SectionComponent with additional settings is deprecated.
    new SectionComponent(
      'some-uuid',
      'some-region',
      [],
      // Provide deprecated 'additional' argument.
      [
        'spoiler-alert' => [
          'glitch-in-accounting' => 'fixed',
          'building-arson' => 'probably',
          'milton-on-beach' => TRUE,
        ],
      ],
      [],
    );

    // Instantiate SectionComponent object with preferred create() method.
    $component = new SectionComponent(
      'some-uuid',
      'some-region',
      [],
      [],
    );
    // Call deprecated set() and get() methods.
    $component->set('music', 'very 90s');
    $component->get('music');
  }

}
