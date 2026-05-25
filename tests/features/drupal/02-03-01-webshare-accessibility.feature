@webshare @a11y @frontend
Feature: Webshare module - accessibility of the share rail
  As a visitor using assistive technology
  I want the share rail to be a labelled landmark with accessible controls
  So that I can find and operate the sharing buttons

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I enable only the default Webshare platforms
    And I am on the homepage

  Scenario: The share rail is a labelled navigation landmark
    Then the "above share rail nav element" element should be visible
    And the "above share rail aria label" element should have a count of 1
    And the page should have a navigation landmark

  Scenario: Platform links expose an accessible label and open safely
    Then the "above share linkedin link aria label" element should have a count of 1
    And the "above share linkedin link noopener" element should have a count of 1
    And the "above share linkedin link noreferrer" element should have a count of 1

  Scenario: The native share button has an accessible label
    Then the "above share native button aria label" element should have a count of 1

  Scenario: Decorative platform icons are hidden from assistive technology
    Then the "above share linkedin icon" element should have a count of 1

  Scenario: The share rail produces no JavaScript errors
    Then there should be no JavaScript errors
