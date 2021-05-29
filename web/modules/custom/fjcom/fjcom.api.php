<?php

/**
 * @file
 * Fjcom API documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on the available imports.
 *
 * @param array $info
 *   An array representing the creating node.
 */
function hook_import_url_alter(array &$info) {
  $info['title'] .= ' | Some alter';
  $info['body']['value'] .= ' | Some alter';
  $info['field_image']['alt'] .= ' | Some alter';
  $info['field_image']['title'] .= ' | Some alter';
}

/**
 * @} End of "addtogroup hooks".
 */
