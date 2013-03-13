<?php
/*
 * @file
 * Administration page callbacks
 */
function ics_calendar_add_calendar_block_form($form, &$form_state)
{
  module_load_include('inc', 'block', 'block.admin');
  $form = block_admin_configure($form, $form_state, 'ics_calendar', NULL);

  // Other modules should be able to use hook_form_block_add_block_form_alter()
  // to modify this form, so add a base form ID.
  $form_state['build_info']['base_form_id'] = 'block_add_block_form';

  // Prevent block_add_block_form_validate/submit() from being automatically
  // added because of the base form ID by providing these handlers manually.
  $form['#validate'] = array();
  $form['#submit'] = array('ics_calendar_add_calendar_block_form_submit');

  return $form;
}
function ics_calendar_add_calendar_block_form_submit($form, &$form_state) {
  // Determine the delta of the new block.
  $block_ids = variable_get('ics_calendar_block_ids', array());
  $delta = empty($block_ids) ? 1 : max($block_ids) + 1;
  $form_state['values']['delta'] = $delta;

  // Save the new array of blocks IDs.
  $block_ids[] = $delta;
  variable_set('ics_calendar_block_ids', $block_ids);

  // Save the block configuration.
  ics_calendar_block_save($delta, $form_state['values']);

  // Run the normal new block submission (borrowed from block_add_block_form_submit).
  $query = db_insert('block')->fields(array('visibility', 'pages', 'custom', 'title', 'module', 'theme', 'region', 'status', 'weight', 'delta', 'cache'));
  foreach (list_themes() as $key => $theme) {
    if ($theme->status) {
      $region = !empty($form_state['values']['regions'][$theme->name]) ? $form_state['values']['regions'][$theme->name] : BLOCK_REGION_NONE;
      $query->values(array(
        'visibility' => (int) $form_state['values']['visibility'],
        'pages' => trim($form_state['values']['pages']),
        'custom' => (int) $form_state['values']['custom'],
        'title' => $form_state['values']['title'],
        'module' => $form_state['values']['module'],
        'theme' => $theme->name,
        'region' => ($region == BLOCK_REGION_NONE ? '' : $region),
        'status' => 0,
        'status' => (int) ($region != BLOCK_REGION_NONE),
        'weight' => 0,
        'delta' => $delta,
        'cache' => DRUPAL_NO_CACHE,
      ));
    }
  }
  $query->execute();

  $query = db_insert('block_role')->fields(array('rid', 'module', 'delta'));
  foreach (array_filter($form_state['values']['roles']) as $rid) {
    $query->values(array(
      'rid' => $rid,
      'module' => $form_state['values']['module'],
      'delta' => $delta,
    ));
  }
  $query->execute();

  drupal_set_message(t('The block has been created.'));
  cache_clear_all();
  $form_state['redirect'] = 'admin/structure/block';
}

/**
 * Alters the block admin form to add delete links next to menu blocks.
 */
function ics_calendar_form_block_admin_display_form_alter(&$form, $form_state) {
  $blocks = module_invoke_all('ics_calendar_blocks');
  foreach (variable_get('ics_calendar_block_ids', array()) AS $delta) {
    if (empty($blocks[$delta])) {
      $form['blocks']['ics_calendar_' . $delta]['delete'] = array('#type' => 'link', '#title' => t('delete'), '#href' => 'admin/config/media/ics_calendar/delete-calendar-block/' . $delta);
    }
  }
}
// ics_calendar_add_calendar_block_form

function ics_calendar_settings_form() {
  $form = array();
  
  // Check if phpFlickr.php is in place
  _ics_calendar_check_libaries();
  
  $form['ics_calendar_defaultics'] = array(
    '#type' => 'textfield',
    '#title' => t('ICS Feed'),
    '#maxlength' => 500,
    '#description' => t('URL to ICS feed'),
    '#default_value' => variable_get('ics_calendar_defaultics', NULL),
  );

  $timeout_options = array(
    "0" => t('0 minutes'),
    "60" => t('1 minute'),
    "300" => t('5 minutes'),
    "3600" => t('1 hour'),
    "21600" =>  t('6 hours'),
    "43200" =>  t('12 hours'),
    "86400" =>  t('24 hours'),
  );
  $form['ics_calendar_cache_timeout'] = array(
    '#type' => 'select',
    '#title' => t('Select cache timeout.'),
    '#description' => t('Select the amount of time to cache the ics feed.'),
    '#default_value' => variable_get('ics_calendar_cache_timeout', '0'),
    '#options' => $timeout_options,
  );
  
  $form['#submit'][] = 'ics_calendar_settings_form_submit';
  
  return system_settings_form($form);
}

/**
 * Submit function for the settins form
 * Also creates the cache dir if option is checked
 */
function ics_calendar_settings_form_submit($form, &$form_state) {
  // Set the path for the cache dir, for now hardcoded, but leave the option to make this custom later
  variable_set('ics_calendar_cach_dir', 'public://icalparser_cache');
  $path = variable_get('ics_calendar_cach_dir', NULL);

  // Check if the dir exists, else try to create
  if (!file_prepare_directory($path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
    if (!file_prepare_directory($path, FILE_CREATE_DIRECTORY)) {
      drupal_set_message(t('Unable to create iCal Parser cache directory. Check the permissions on your files directory.'), 'error');
      return;
    }
  }
}


/**
 * Implements hook_block_info().
 */
function _ics_calendar_block_info() {
  $blocks = array();
  $deltas = variable_get('ics_calendar_block_ids', array());

  foreach (array_keys(module_invoke_all('ics_calendar_blocks')) as $delta) {
    $deltas[] = $delta;
  }
  foreach ($deltas AS $delta) {
    $blocks[$delta]['info'] = _ics_calendar_format_title(ics_calendar_get_config($delta));
    // Menu blocks can't be cached because each menu item can have
    // a custom access callback. menu.inc manages its own caching.
    $blocks[$delta]['cache'] = DRUPAL_NO_CACHE;
  }

  return $blocks;
}

/**
 * Return the title of the block.
 *
 * @param $config
 *   array The configuration of the menu block.
 * @return
 *   string The title of the block.
 */
function _ics_calendar_format_title($config) {
  // If an administrative title is specified, use it.
  // print_r($config);
  // exit;
  if (!empty($config['ics_admin_title'])) {
    return 'ICS Calendar: '.check_plain($config['ics_admin_title']);
  }

  $title = t('Unnamed ICS Calendar block');

  return $title;
}

/**
 * Menu callback: confirm deletion of menu blocks.
 */
function ics_calendar_delete($form, &$form_state, $delta = 0) {
  $title = _ics_calendar_format_title(ics_calendar_get_config($delta));
  $form['block_title'] = array('#type' => 'hidden', '#value' => $title);
  $form['delta'] = array('#type' => 'hidden', '#value' => $delta);

  return confirm_form($form, t('Are you sure you want to delete the "%name" block?', array('%name' => $title)), 'admin/structure/block', NULL, t('Delete'), t('Cancel'));
}

/**
 * Deletion of menu blocks.
 */
function ics_calendar_delete_submit($form, &$form_state) {
  // Remove the menu block configuration variables.
  $delta = $form_state['values']['delta'];
  $block_ids = variable_get('menu_block_ids', array());
  unset($block_ids[array_search($delta, $block_ids)]);
  sort($block_ids);
  variable_set('ics_calendar_block_ids', $block_ids);
  variable_del("ics_calendar_block_{$delta}_calendar_ics");
  variable_del("ics_calendar_block_{$delta}_cache_timeout");
  variable_del("ics_calendar_block_{$delta}_ics_admin_title");
  variable_del("ics_calendar_block_{$delta}_ics_type");

  db_delete('block')
    ->condition('module', 'ics_calendar')
    ->condition('delta', $delta)
    ->execute();
  db_delete('block_role')
    ->condition('module', 'ics_calendar')
    ->condition('delta', $delta)
    ->execute();
  drupal_set_message(t('The block "%name" has been removed.', array('%name' => $form_state['values']['block_title'])));
  cache_clear_all();
  $form_state['redirect'] = 'admin/structure/block';
  return;
}




/**
 * Implements hook_block_save().
 */
function ics_calendar_block_save($delta = '', $edit = array()) {
  if (!empty($delta)) {
    // Don't save values for an exported block.
    $config = ics_calendar_get_config($delta);
    if (empty($config['exported_to_code'])) {
      variable_set("ics_calendar_block_{$delta}_calendar_ics", $edit['ics_calendar_ics']);
      variable_set("ics_calendar_block_{$delta}_cache_timeout", $edit['ics_calendar_cache_timeout']);
      variable_set("ics_calendar_block_{$delta}_ics_admin_title", $edit['ics_admin_title']);
      variable_set("ics_calendar_block_{$delta}_ics_type", $edit['ics_type']);
    }
  }
}


/**
 * Returns the configuration form for a menu tree.
 *
 * @param $form_state
 *   array An associated array of configuration options should be present in the
 *   'values' key. If none are given, default configuration is assumed.
 * @return
 *   array The form in Form API format.
 */
function ics_calendar_block_configure($delta = '') {
  // Create a pseudo form state.
  $form_state = array('values' => ics_calendar_get_config($delta));
  return ics_calendar_configure_form(array(), $form_state);
}

function ics_calendar_configure_form($form, &$form_state) {

  $config = array();
  // Get the config from the form state.
  if (!empty($form_state['values'])) {
    $config = $form_state['values'];
  }

  // Merge in the default configuration.
  $config += ics_calendar_get_config();

  // Don't display the config form if this delta is exported to code.
  if (!empty($config['exported_to_code'])) {
    $form['exported_message'] = array(
      '#markup' => '<p><em>' . t('Configuration is being provided by code.') . '</em></p>',
    );
    return $form;
  }

  // // Build the standard form.
  // $form['#attached']['js'][] = drupal_get_path('module', 'menu_block') . '/menu-block.js';
  // $form['#attached']['css'][] = drupal_get_path('module', 'menu_block') . '/menu-block-admin.css';
  // $form['#attached']['library'][] = array('system', 'ui.button');

  $form['menu-block-wrapper-start'] = array(
    '#markup' => '<div id="calendar-block-settings">',
    '#weight' => -20,
  );

  $form['ics_admin_title'] = array(
    '#type' => 'textfield',
    '#default_value' => $config['ics_admin_title'],
    '#title' => t('Administrative title'),
    '#description' => t('This title will be used administratively to identify this block. If blank, the regular title will be used.'),
  );
  

  $form['ics_calendar_ics'] = array(
    '#type' => 'textfield',
    '#maxlength' => 500,
    '#title' => t('ICS Feed'),
    '#description' => t('URL to ICS feed'),
    '#default_value' => $config['ics_calendar_ics'],
  );

  $form['ics_type'] = array(
    '#type' => 'select',
    '#title' => t('Calendar Type'),
    '#default_value' => $config['ics_type'],
    '#options' => array(
      'month'  => t('Month View'),
      'week'  => t('Week View'),
      'upcoming'  => t('Upcoming Events View'),
      'upcoming_list'  => t('Upcoming Events List View'),
    ),
  );


  $timeout_options = array(
    "0" => t('0 minutes'),
    "60" => t('1 minute'),
    "300" => t('5 minutes'),
    "3600" => t('1 hour'),
    "21600" =>  t('6 hours'),
    "43200" =>  t('12 hours'),
    "86400" =>  t('24 hours'),
  );
  $form['ics_calendar_cache_timeout'] = array(
    '#type' => 'select',
    '#title' => t('Select cache timeout.'),
    '#description' => t('Select the amount of time to cache the ics feed.'),
    '#default_value' => $config['ics_calendar_cache_timeout'],
    '#options' => $timeout_options,
  );

  // $form['display_options'] = array(
  //   '#type' => 'radios',
  //   '#title' => t('Display'),
  //   '#default_value' => 'basic',
  //   '#options' => array(
  //     'basic' => t('Basic options'),
  //     'advanced' => t('Advanced options'),
  //   ),
  //   '#attributes' => array('class' => array('clearfix')),
  //   '#weight' => -19,
  // );
  // $form['title_link'] = array(
  //   '#type' => 'checkbox',
  //   '#title' => t('Block title as link'),
  //   '#default_value' => $config['title_link'],
  //   '#description' => t('Make the default block title a link to that menu item. An overridden block title will not be a link.'),
  //   '#states' => array(
  //     'visible' => array(
  //       ':input[name=title]' => array('value' => ''),
  //     ),
  //   ),
  // );
  $form['menu-block-wrapper-close'] = array('#markup' => '</div>');

  // // Set visibility of advanced options.
  // foreach (array('title_link', 'follow', 'follow_parent', 'expanded', 'sort', 'parent_mlid') as $key) {
  //   $form[$key]['#states']['visible'][':input[name=display_options]'] = array('value' => 'advanced');
  // }
  // if ($config['title_link'] || $follow || $config['expanded'] || $config['sort'] || $config['parent_mlid']) {
  //   $form['display_options']['#default_value'] = 'advanced';
  // }

  return $form;
}

