<?php

use Twig\TwigFunction;

$function = new TwigFunction('icon', function($name = '', $title = false, $classes = array()) {
  // If $classes overridden with false or is a string, make it an empty array.
  // To avoid getting errors or printing blank space.
  if (!isset($classes) || $classes === false || is_string($classes)) {
    $classes = array();
  }

  return [
    '#type' => 'inline_template',
    '#template' => '<span class="wrapper--rs-icon"><svg class="rs-icon rs-icon--{{ name }}{% for class in classes %} {{class}}{% endfor %}" role="img" aria-hidden="true" xmlns:xlink="http://www.w3.org/1999/xlink">{% if title %}<title>{{ title|t }}</title>{% endif %}<use xlink:href="#rs-icon--{{ name }}"></use></svg></span>',
    '#context' => [
      'name' => $name,
      'title' => $title,
      'classes' => $classes
    ],
  ];
}, [
  'needs_context' => true,
  'is_safe' => ['html'],
]);
