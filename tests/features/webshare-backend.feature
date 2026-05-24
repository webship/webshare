@webshare @admin
Feature: Webshare module — back-end administration
  As a site administrator
  I want to manage the Webshare module configuration
  So that I can enable platforms, change defaults, and save settings

  Background:
    Given I am logged in as a Drupal admin
    And I am on "/admin/config/services/webshare"

  Scenario: The configuration form is reachable for administrators
    Then "form[data-drupal-form-id='webshare_config_form']" should be visible
    And ".messages--error" should not contain "Access denied"

  Scenario: The two configuration sections (General + Platforms) are rendered
    Then "summary" should contain "General Settings"
    And "summary" should contain "Manage Platforms"

  Scenario: All default platforms appear in the management table
    Then "table#edit-platforms-table tbody tr" should have a count of 5
    And "table#edit-platforms-table tbody tr td:has-text('LinkedIn')" should have a count of 1
    And "table#edit-platforms-table tbody tr td:has-text('Facebook')" should have a count of 1
    And "table#edit-platforms-table tbody tr td:has-text('X')" should have a count of 1
    And "table#edit-platforms-table tbody tr td:has-text('WhatsApp')" should have a count of 1
    And "table#edit-platforms-table tbody tr td:has-text('Copy')" should have a count of 1

  Scenario: Each platform row exposes Edit and Delete operations
    Then "a.use-ajax[href*='/platform/linkedin/edit']" should have a count of 1
    And "a.use-ajax[href*='/platform/linkedin/delete']" should have a count of 1
    And "a.use-ajax[data-dialog-type='modal']" should have a count of 11

  Scenario: The "Add Custom Platform" button is available
    Then "a.use-ajax[href*='/platform/add']" should have a count of 1

  Scenario: Submitting the form persists the configuration
    When I press "Save configuration"
    Then ".messages--status" should contain "The configuration options have been saved."
    And ".messages--error" should have a count of 0

  Scenario: No JavaScript errors are produced on the admin form
    Then there should be no JavaScript errors
