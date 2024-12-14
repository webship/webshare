<?php

namespace Drupal\webshare;

/**
 * Interface for WebshareService.
 */
interface WebshareServiceInterface {

  /**
   * Builds a renderable array of Social buttons.
   *
   * @param string $url
   *   Node url.
   * @param string $id
   *   Node entity type plus node id.
   *
   * @return array
   *   Renderable build array.
   */
  public function build($url, $id);

  /**
   * Determines if module is restricted to show or not on certain pages.
   *
   * @param string $view_mode
   *   Entity view mode.
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  public function isRestricted($view_mode);

}
