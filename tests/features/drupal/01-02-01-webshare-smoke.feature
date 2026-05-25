@smoke @webshare
Feature: Smoke — Webshare renders on the home page
  As an anonymous visitor
  I want the test site to load with the Webshare block
  So that the Webshare module can be exercised

  Scenario: The home page loads with the Webshare block
    Given I am on the homepage
    Then the "above share rail" element should be visible
    And the "above share list" element should be visible
    And there should be no JavaScript errors
