@webshare @admin
Feature: Webshare module — back-end administration
  As a site administrator
  I want to manage the Webshare platforms and settings
  So that I can enable networks, reorder them, and save the configuration

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I enable only the default Webshare platforms
    And I am on "/admin/config/services/webshare"

  Scenario: The configuration form is reachable for administrators
    Then the "webshare admin form" element should be visible
    And the "drupal admin error messages" element should have a count of 0

  Scenario: All platforms appear in the management table
    Then the "webshare admin table rows" element should have a count of 13
    And the "webshare admin linkedin row cell" element should have a count of 1
    And the "webshare admin facebook row cell" element should have a count of 1
    And the "webshare admin whatsapp row cell" element should have a count of 1
    And the "webshare admin mastodon row cell" element should have a count of 1

  Scenario: Each platform row exposes Edit and Delete operations
    Then the "webshare admin edit linkedin link" element should have a count of 1
    And the "webshare admin delete linkedin link" element should have a count of 1
    And the "webshare admin modal links" element should have a count of 27

  Scenario: The "Add Custom Platform" button is available
    Then the "webshare admin add platform link" element should have a count of 1

  Scenario: Each platform row carries a draggable weight control
    Then the "webshare admin draggable rows" element should have a count of 13
    And the "webshare admin weight selects" element should have a count of 13

  Scenario: Submitting the form persists the configuration
    When I press "Save configuration"
    Then the "drupal admin status messages" element should contain text "The configuration options have been saved."
    And the "drupal admin error messages" element should have a count of 0

  Scenario: No JavaScript errors are produced on the admin form
    Then there should be no JavaScript errors
