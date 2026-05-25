@smoke @webshare @drupal-cms
Feature: Smoke — Webshare renders on the Drupal CMS home page
  As an anonymous visitor on a Drupal CMS site
  I want the home page to render the Webshare share rail
  So that the Webshare module is exercised on the Drupal CMS distribution

  Scenario: The Drupal CMS home page renders the Webshare component in header and footer
    Given I am on the homepage
    Then the "header share rail" element should be visible
    And the "footer share rail" element should be visible
    And the "header share list" element should be visible
    And there should be no JavaScript errors
