<?php

namespace Drupal\webshare;

/**
 * Interface for WebshareService.
 */
interface WebshareServiceInterface {

  /**
   * Builds a renderable array of share buttons.
   *
   * @param string $url
   *   Page url being shared.
   * @param string $id
   *   Stable id used to scope the buttons' DOM ids.
   * @param array $options
   *   Optional presentation overrides:
   *   - heading: Explicit component heading; empty string suppresses.
   *   - alignment: 'start' (default) or 'end' (logical CSS).
   *   - orientation: 'horizontal' (default) or 'vertical'.
   *   - mobile_visibility: 'all', 'hide_mobile' or 'mobile_only'.
   *   - placement: 'inline' (default) or 'rail-end'.
   *   - native_share: Whether to render the native Web Share API button.
   *   - share_title: Title passed to the native Web Share API.
   *   - share_text: Description text passed to the native Web Share API.
   *
   * @return array
   *   Renderable build array consuming the `webshare:share` SDC component.
   */
  public function build($url, $id, array $options = []);

}
