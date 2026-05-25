@webshare @responsive @frontend
Feature: Webshare module — responsive share rail
  As a visitor on any device
  I want the share rail to render across breakpoints
  So that I can share the page on mobile and desktop alike

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I enable only the default Webshare platforms

  Scenario: The share rail renders on a small (mobile) viewport
    When I set the viewport to the "xs" breakpoint
    And I am on the homepage
    Then the "above share rail" element should be visible
    And the "above share list" element should be visible
    And the "above share item linkedin" element should be visible

  Scenario: The share rail renders on a medium (tablet) viewport
    When I set the viewport to the "md" breakpoint
    And I am on the homepage
    Then the "above share rail" element should be visible
    And the "above share list" element should be visible

  Scenario: The share rail renders on a large (desktop) viewport
    When I set the viewport to the "xl" breakpoint
    And I am on the homepage
    Then the "above share rail" element should be visible
    And the "above share native button" element should be visible
