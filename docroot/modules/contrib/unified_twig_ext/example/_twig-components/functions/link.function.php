<?php

if (!class_exists('Drupal')) {
  $function = new \Twig\TwigFunction(
    'link',
    function ($title, $url, $attributes) {
      $title = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $url = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      if (isset($attributes) && isset($attributes['class'])) {
        $classes = implode(' ', $attributes['class']);
        $classes = htmlspecialchars($classes, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<a href="' . $url . '" class="' . $classes . '">' . $title . '</a>';
      }
      else {
        return '<a href="' . $url . '">' . $title . '</a>';
      }
    },
    ['is_safe' => ['html']]
  );
}
