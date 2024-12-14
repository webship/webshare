<?php

/**
 * @file
 * Provides views data for Webshare module.
 */

/**
 * Implements hook_views_data().
 */
function webshare_views_data() {
  $module_handler = \Drupal::service('module_handler');
  $data = [];
  $data['node']['webshare_field'] = [
    'title' => t('Webshare'),
    'help' => t('Buttons provided by the Webshare service.'),
    'field' => [
      'id' => 'webshare_field',
      'help' => t('Show Webshare module for a particular node.'),
    ],
  ];
  if ($module_handler->moduleExists('commerce_product')) {
    $data['commerce_product']['webshare_field'] = [
      'title' => t('Webshare'),
      'help' => t('Buttons provided by the Webshare service.'),
      'field' => [
        'id' => 'webshare_field',
        'help' => t('Show Webshare module for a particular product.'),
      ],
    ];
  }
  return $data;
}
