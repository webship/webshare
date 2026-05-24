@smoke
Feature: Check the home page
  As an anonymous visitor
  I want the test site to load
  So that the Webshare module can be exercised

  Scenario: The home page loads with the Webshare block
    Given I am on the homepage
    Then ".webshare" should be visible
    And ".webshare__list" should be visible
