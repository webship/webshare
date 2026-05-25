@webshare @admin @modal
Feature: Webshare module - add and edit platforms in a modal
  As a site administrator
  I want to add custom platforms and edit existing ones in a dialog
  So that I can extend the share rail without leaving the settings page

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I am on "/admin/config/services/webshare"

  Scenario: The Add Custom Platform dialog exposes the platform fields
    When I click the "Add Custom Platform" link
    Then the "modal dialog" element should be visible within 10 seconds
    And the "modal title" element should contain text "Add Platform"
    And the "modal name field" element should be visible
    And the "modal url template field" element should be visible
    And there should be no JavaScript errors

  Scenario: The Edit dialog opens for an existing platform
    When I click the "webshare admin edit linkedin link" element
    Then the "modal dialog" element should be visible within 10 seconds
    And the "modal title" element should contain text "Edit Platform"
    And the "modal name field" element should be visible

  Scenario: The Delete dialog opens for an existing platform
    When I click the "modal dropbutton toggle" element
    And I click the "webshare admin delete linkedin link" element
    Then the "modal dialog" element should be visible within 10 seconds
    And the "modal title" element should contain text "Are you sure you want to delete"
    And the "modal dialog" element should contain text "Delete Platform"
