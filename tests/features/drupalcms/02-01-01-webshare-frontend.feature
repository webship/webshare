@webshare @drupal-cms @frontend
Feature: Webshare module - front-end rendering on Drupal CMS
  As a Drupal CMS site visitor
  I want to see the social sharing buttons in the header and footer
  So that I can share the page on social platforms or through my device

  # Detailed assertions target the header Canvas component via the
  # `header share …` named selectors registered in
  # tests/selectors/drupal-cms-mercury.json so they stay deterministic
  # regardless of the footer instance.

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I enable only the default Webshare platforms
    And I am on the homepage

  Scenario: The Share component renders in both Mercury header and footer
    Then the "share rail" element should have a count of 2
    And the "header share rail" element should be visible
    And the "footer share rail" element should be visible

  Scenario: The Webshare single-directory component is rendered
    Then the "header share rail" element should be visible
    And the "header share list" element should be visible

  Scenario: The component uses the orientation configured in the page region
    Then the "header share rail" element should have class "webshare--horizontal"

  # Only LinkedIn / Facebook / X ship enabled on a fresh install; the native
  # share button is rendered as an extra list item, so the rail holds 4 items.
  Scenario: The default platform share buttons are present
    Then the "header share item" element should have a count of 4
    And the "header share item linkedin" element should be visible
    And the "header share item facebook" element should be visible
    And the "header share item x" element should be visible
    And the "header share item whatsapp" element should have a count of 0
    And the "header share item copy" element should have a count of 0

  Scenario: Platform buttons link to the correct sharing endpoints
    Then the "header share facebook endpoint link" element should have a count of 1
    And the "header share x endpoint link" element should have a count of 1
    And the "header share linkedin endpoint link" element should have a count of 1

  Scenario: Platform share links open in a new tab safely
    Then the "header share facebook new tab link" element should have a count of 1
    And the "header share facebook noopener link" element should have a count of 1

  Scenario: The native Web Share API button is present
    Then the "header share native button" element should have a count of 1
    And the "header share native button" element should be visible

  Scenario: The component exposes the Web Share API payload data
    Then the "header share data-url attribute" element should have a count of 1
    And the "header share data-title attribute" element should have a count of 1
    And the "header share data-text attribute" element should have a count of 1

  Scenario: The Webshare component produces no JavaScript errors
    Then there should be no JavaScript errors
