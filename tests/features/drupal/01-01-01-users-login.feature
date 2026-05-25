@setup @webshare
Feature: Provision the Webshare test site
  As the site owner
  I want the Share block placed in two regions and one user per role
  So that the rendering and access-control scenarios have what they expect

  Scenario: The Webmaster configures the site for testing
    Given I am a logged in user with the "Webmaster" user
    And the Webshare Share blocks are placed in the content regions
    And I add testing users
    When I am on the homepage
    Then the "above share rail" element should be visible
    And the "below share rail" element should be visible
