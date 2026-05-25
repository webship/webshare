@webshare @admin
Feature: Webshare module — admin discoverability
  As a site administrator
  I want the Webshare settings to be reachable from the configuration area
  So that I can find and manage the share rail

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The Webshare settings link appears on the Services configuration page
    Given I am on "/admin/config"
    Then the "webshare admin services link" element should have a count of 1

  Scenario: The settings page shows the expected title and platform table
    Given I am on "/admin/config/services/webshare"
    Then the "drupal page heading" element should contain text "Webshare"
    And the "webshare admin table" element should be visible
    And the "webshare admin add platform link" element should be visible
