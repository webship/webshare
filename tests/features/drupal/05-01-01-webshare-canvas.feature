@webshare @canvas
Feature: Webshare module — Drupal Canvas integration
  As a site builder using Drupal Canvas
  I want to place the Webshare Share component on Canvas pages and templates
  So that the share rail renders wherever Canvas composes the layout

  # The Canvas page, the node.article.full content template and the sample
  # blog node are provisioned by the CI before_script. The site-wide Share
  # block is scoped to the front page, so these pages show only their own
  # Canvas-placed instance — hence the component selectors below resolve to
  # `.webshare` without a region prefix.

  Scenario: The Share component renders on a Canvas page
    Given I am on "/webshare-canvas-test"
    Then the "share rail" element should have a count of 1
    And the "share rail" element should have class "webshare--horizontal"
    And the "share heading" element should contain text "Share this page"
    And the "share item linkedin" element should be visible
    And the "share native button" element should be visible
    And there should be no JavaScript errors

  Scenario: The Share rail renders on a blog post full content view via Canvas
    Given I am on "/blog/community-behind-webshare"
    Then the "share rail" element should have a count of 1
    And the "share rail" element should have class "webshare--vertical"
    And the "share rail" element should have class "webshare--rail-end"
    And the "share heading" element should contain text "Share this article"
    And the "share facebook endpoint link" element should have a count of 1
    And there should be no JavaScript errors
