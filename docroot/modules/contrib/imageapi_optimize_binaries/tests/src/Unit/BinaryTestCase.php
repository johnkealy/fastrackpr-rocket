<?php

namespace Drupal\Tests\imageapi_optimize_binaries\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Base test for our binary tests
 *
 * @group imageapi_optimize
 */
abstract class BinaryTestCase extends UnitTestCase {

  protected function getLoggerMock() {
    return $this->createMock('\Psr\Log\LoggerInterface');
  }


  protected function getImageFactoryMock() {
    return $this->createMock('\Drupal\Core\Image\ImageFactory');
  }

  protected function getFileSystemMock() {
    $fileSystemMock = $this->createMock('\Drupal\Core\File\FileSystemInterface');
    $fileSystemMock
      ->method('realpath')->will($this->returnArgument(0));
    return $fileSystemMock;
  }

  protected function getShellOperationsMock() {
    $shellOperationsMock =  $this->createMock('\Drupal\imageapi_optimize_binaries\ImageAPIOptimizeShellOperationsInterface');

    $shellOperationsMock
      ->method('findExecutablePath')->will($this->returnArgument(0));

    return $shellOperationsMock;
  }
}
