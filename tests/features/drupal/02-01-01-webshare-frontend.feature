@webshare @frontend
Feature: Webshare module — front-end rendering
  As a site visitor
  I want to see social sharing buttons rendered by the Webshare module
  So that I can share the page on social platforms or through my device

  # The Share block is placed in two regions (see 01-01-01). Detailed
  # assertions target the Content Above instance via the `above share …`
  # named selectors registered in tests/selectors/drupal-olivero.json so
  # they stay deterministic regardless of the second region.

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I enable only the default Webshare platforms
    And I am on the homepage

  Scenario: The Share block renders in both the Content Above and Content Below regions
    Then the "share rail" element should have a count of 2
    And the "above share rail" element should be visible
    And the "below share rail" element should be visible

  Scenario: The Webshare single-directory component is rendered
    Then the "above share rail" element should be visible
    And the "above share list" element should be visible

  Scenario: The component uses the orientation configured on the block
    Then the "above share rail" element should have class "webshare--horizontal"

  # Only LinkedIn / Facebook / X ship enabled on a fresh install; the native
  # share button is rendered as an extra list item, so the rail holds 4 items.
  Scenario: The default platform share buttons are present
    Then the "above share item" element should have a count of 4
    And the "above share item linkedin" element should be visible
    And the "above share item facebook" element should be visible
    And the "above share item x" element should be visible
    And the "above share item whatsapp" element should have a count of 0
    And the "above share item copy" element should have a count of 0

  Scenario: Platform buttons link to the correct sharing endpoints
    Then the "above share facebook endpoint link" element should have a count of 1
    And the "above share x endpoint link" element should have a count of 1
    And the "above share linkedin endpoint link" element should have a count of 1

  Scenario: Platform share links open in a new tab safely
    Then the "above share facebook new tab link" element should have a count of 1
    And the "above share facebook noreferrer link" element should have a count of 1
    And the "above share facebook noopener link" element should have a count of 1

  Scenario: The native Web Share API button is present
    Then the "above share native button" element should have a count of 1
    And the "above share native button" element should be visible

  Scenario: The component exposes the Web Share API payload data
    Then the "above share data-url attribute" element should have a count of 1
    And the "above share data-title attribute" element should have a count of 1
    And the "above share data-text attribute" element should have a count of 1

  Scenario: The Webshare component produces no JavaScript errors
    Then there should be no JavaScript errors
