@setup @webshare @drupal-cms
Feature: Provision the Webshare Drupal CMS test users
  As the site owner of a Drupal CMS install
  I want one test user per Standard role
  So that the access-control scenarios can log in as each of them

  # The Webshare Share component is placed in the Mercury header and footer
  # Canvas page_region entities (`mercury.header` / `mercury.footer`) at
  # site-setup time — classic block placement does not apply on a Canvas-
  # rendered Drupal CMS front page. See the `webship-js-test-drupal-cms`
  # job's before_script in .gitlab-ci.yml.

  Scenario: The Webmaster provisions the non-admin testing users
    Given I am a logged in user with the "Webmaster" user
    And I add testing users
    When I am on the homepage
    Then the "header share rail" element should be visible
    And the "footer share rail" element should be visible
