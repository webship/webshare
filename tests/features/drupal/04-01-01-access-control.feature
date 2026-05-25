@webshare @admin @access
Feature: Webshare module - administration access control
  As a site owner
  I want only users with the "administer webshare" permission to reach settings
  So that the share configuration cannot be changed by regular users

  Scenario: The Webmaster can reach the Webshare settings
    Given I am a logged in user with the "Webmaster" user
    And I am on "/admin/config/services/webshare"
    Then the "webshare admin form" element should be visible

  Scenario: A content editor is denied the Webshare settings
    Given I am a logged in user with the "Content editor" user
    And I am on "/admin/config/services/webshare"
    Then the "webshare admin form" element should have a count of 0
    And the "drupal page heading" element should contain text "Access denied"

  Scenario: An authenticated user is denied the Webshare settings
    Given I am a logged in user with the "Authenticated user" user
    And I am on "/admin/config/services/webshare"
    Then the "webshare admin form" element should have a count of 0
    And the "drupal page heading" element should contain text "Access denied"
