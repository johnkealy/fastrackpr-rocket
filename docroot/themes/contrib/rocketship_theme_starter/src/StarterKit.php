<?php

namespace Drupal\rocketship_theme_starter;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Theme\StarterKitInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class StarterKit implements StarterKitInterface {

  /**
   * List of classes defined in Rocketship Starter.
   *   Used to differentiate capitalized machine name from theme name used in comments and string content.
   *
   * @var array
   */
  private static $classes = [
    'StarterKit',
  ];

  /**
   * List of files & directories that shouldn't be copied over.
   *
   * @var array
   */
  private static $deletable = [
    '/src/StarterKit.php',
  ];

  /**
   * Array of files to avoid renaming.
   *
   * @var array files
   */
  private static $skipFileRename = [];

  /**
   * Array of files to avoid editing.
   *
   * @var array files
   */
  private static $skipFileContentEdit = [
    "/^gulp$/",
  ];

  /**
   * Finds and replaces string/regex matches in file names and contents.
   *
   * @param string $dir
   *   The working directory of the template being generated.
   * @param string $find
   *   The string to be removed.
   * @param string $replace
   *   The string to be added.
   * @param bool $skip_filters
   *   If `true`, do not filter results based on $skipFileRename or $skipFileContentEdit arrays.
   */
  private static function findAndReplace($dir, $find, $replace, $skip_filters = FALSE): void {
    $fs = new Filesystem();

    // Edit file names.
    $finder = new Finder();
    $finder->files()
      ->in($dir)
      ->name("/$find/")
      ->filter(function (\SplFileInfo $file) use ($dir, $skip_filters) {
        if (!$skip_filters) {
          $relative_path = str_replace($dir . '/', '', $file->getPathname());
          return !in_array($relative_path, self::$skipFileRename);
        }

        return TRUE;
      });

    foreach ($finder as $file) {
      $filepath_segments = explode('/', $file->getRealPath());
      $filename = array_pop($filepath_segments);
      $filename = str_replace($find, $replace, $filename);
      $filepath_segments[] = $filename;
      $fs->rename($file->getRealPath(), implode('/', $filepath_segments));
    }

    // Edit file contents.
    $finder = new Finder();
    $finder->files()
      ->in($dir)
      ->contains($find)
      ->filter(function (\SplFileInfo $file) use ($dir, $skip_filters) {
        if (!$skip_filters) {
          $relative_path = str_replace($dir . '/', '', $file->getPathname());
          return !in_array($relative_path, self::$skipFileContentEdit);
        }

        return TRUE;
      });

    foreach ($finder as $file) {
      $contents = file_get_contents($file->getRealPath());
      $contents = str_replace($find, $replace, $contents);
      file_put_contents($file->getRealPath(), $contents);
    }
  }

  /**
   * Updates values in the new theme's .info.yml file.
   *
   * @param string $working_dir
   *   The working directory of the template being generated.
   * @param string $machine_name
   *   The theme's machine name.
   * @param string $theme_name
   *   The theme's name.
   */
  private static function updateThemeInfo(string $working_dir, string $machine_name, string $theme_name): void {
    // Edit the info file for new theme.
    $info_file = "$working_dir/$machine_name.info.yml";
    // @todo: yaml::decode strips comments and empty enters, check for another solution.
    $info = Yaml::decode(file_get_contents($info_file));

    unset($info['starterkit']);
    unset($info['package']);
    file_put_contents($info_file, Yaml::encode($info));
  }

  /**
   * Removes $deletable files & directories from the working directory prior to copying into final destination.
   *
   * @param string $working_dir
   *   The working directory of the template being generated.
   */
  private static function removeDeletableFiles(string $working_dir): void {
    $fs = new Filesystem();

    foreach (self::$deletable as $item) {
      $fs->remove($working_dir . $item);
    }
  }

  /**
   * Copy over original readme file.
   */
  private static function renameReadMeFile(string $working_dir): void {
    $fs = new Filesystem();

    $fs->rename($working_dir . '/README.txt', $working_dir . '/README.md', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function postProcess(string $working_dir, string $machine_name, string $theme_name): void {
    self::updateThemeInfo($working_dir, $machine_name, $theme_name);
    self::renameReadMeFile($working_dir);
    self::removeDeletableFiles($working_dir);

    // Replace "Rocketship Starter" in class names before
    // doing bulk find/replace.
    $old_pattern = 'Rocketship Starter';
    $new_pattern = str_replace(' ', '', ucwords(str_replace('_', ' ', $machine_name)));
    foreach (self::$classes as $old_class) {
      $new_class = str_replace($old_pattern, $new_pattern, $old_class);
      self::findAndReplace($working_dir, $old_class, $new_class);
    }

    self::findAndReplace($working_dir, 'themes/contrib/', 'themes/custom/');
    self::findAndReplace($working_dir, 'rocketship_theme_starter', $machine_name);
    self::findAndReplace($working_dir, 'Rocketship Starter', $theme_name);
    // Update the components.
    self::findAndReplace($working_dir, 'rocketship-theme-starter', str_replace('_', '-', $machine_name));
    // Update the components in storybook itself.
    self::findAndReplace($working_dir . '/.storybook', 'rocketship-theme-starter', str_replace('_', '-', $machine_name));
  }

}
