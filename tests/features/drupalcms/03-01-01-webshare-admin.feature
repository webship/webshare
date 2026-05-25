@webshare @drupal-cms @admin
Feature: Webshare module - back-end administration on Drupal CMS
  As a Drupal CMS administrator
  I want to manage the Webshare platforms via the Gin admin UI
  So that I can enable networks and save the configuration

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I enable only the default Webshare platforms
    And I am on "/admin/config/services/webshare"

  Scenario: The configuration form is reachable on Drupal CMS
    Then the "webshare admin form" element should be visible
    And the "drupal admin error messages" element should have a count of 0

  Scenario: All platforms appear in the management table
    Then the "webshare admin table rows" element should have a count of 13
    And the "webshare admin linkedin row cell" element should have a count of 1
    And the "webshare admin facebook row cell" element should have a count of 1
    And the "webshare admin mastodon row cell" element should have a count of 1

  Scenario: The "Add Custom Platform" button is available on Drupal CMS
    Then the "webshare admin add platform link" element should have a count of 1

  Scenario: The Gin admin theme is the active back-end theme
    Then the "gin admin form" element should have a count of 1

  Scenario: No JavaScript errors are produced on the admin form
    Then there should be no JavaScript errors
