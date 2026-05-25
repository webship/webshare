@webshare @canvas
Feature: Webshare module - Drupal Canvas integration
  As a site builder using Drupal Canvas
  I want to place the Webshare Share component on Canvas pages and templates
  So that the share rail renders wherever Canvas composes the layout

  # The Canvas page, the node.article.full content template and the sample
  # blog node are provisioned by the CI before_script. The site-wide Share
  # block is scoped to the front page, so these pages show only their own
  # Canvas-placed instance - hence the component selectors below resolve to
  # `.webshare` without a region prefix.

  Scenario: The Share component renders on a Canvas page
    Given I am on "/webshare-canvas-test"
    Then the "share rail" element should have a count of 1
    And the "share rail" element should have class "webshare--horizontal"
    And the "share heading" element should contain text "Share this page"
    And the "share item linkedin" element should be visible
    And the "share native button" element should be visible
    And there should be no JavaScript errors

  Scenario: The Canvas-rendered rail exposes the Web Share API payload attributes
    Given I am on "/webshare-canvas-test"
    Then the "share rail" element should have a count of 1
    # All three data-attributes must be present on the rendered <nav>,
    # otherwise the bundled share.js cannot populate navigator.share().
    And the "share data-url attribute" element should have a count of 1
    And the "share data-title attribute" element should have a count of 1
    And the "share data-text attribute" element should have a count of 1

  Scenario: Platform changes invalidate the Canvas-rendered rail cache
    # First confirm the Canvas page shows only the default 3 platforms
    # (linkedin / facebook / x) plus the native button = 4 list items.
    Given I am a logged in user with the "Webmaster" user
    And I enable only the default Webshare platforms
    And I am on "/webshare-canvas-test"
    Then the "share item" element should have a count of 4
    And the "share item whatsapp" element should have a count of 0

    # Enable WhatsApp - the webshare_platforms cache tag should bubble
    # from the Twig fn into the active render context and invalidate the
    # Canvas page, so the next load shows the 5th platform item.
    When I enable the Webshare platform "whatsapp"
    And I am on "/webshare-canvas-test"
    Then the "share item" element should have a count of 5
    And the "share item whatsapp" element should be visible
    And the "share whatsapp endpoint link" element should have a count of 1

    # Restore the defaults to keep the suite deterministic for later scenarios.
    When I enable only the default Webshare platforms
    And I am on "/webshare-canvas-test"
    Then the "share item" element should have a count of 4

  Scenario: The Share rail renders on a blog post full content view via Canvas
    Given I am on "/blog/community-behind-webshare"
    Then the "share rail" element should have a count of 1
    And the "share rail" element should have class "webshare--vertical"
    And the "share rail" element should have class "webshare--rail-end"
    And the "share heading" element should contain text "Share this article"
    And the "share facebook endpoint link" element should have a count of 1
    And there should be no JavaScript errors
