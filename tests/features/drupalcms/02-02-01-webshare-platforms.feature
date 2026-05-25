@webshare @drupal-cms @frontend @platforms
Feature: Webshare module — every platform share endpoint on Drupal CMS
  As a Drupal CMS content owner
  I want each social network button to point at the correct share endpoint
  So that visitors reach the right platform with the page URL pre-filled

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I enable all Webshare platforms
    And I am on the homepage

  Scenario: Every enabled platform renders a list item in the header component
    Then the "header share item linkedin" element should be visible
    And the "header share item facebook" element should be visible
    And the "header share item x" element should be visible
    And the "header share item whatsapp" element should be visible
    And the "header share item copy" element should be visible
    And the "header share item email" element should be visible
    And the "header share item telegram" element should be visible
    And the "header share item reddit" element should be visible
    And the "header share item pinterest" element should be visible
    And the "header share item threads" element should be visible
    And the "header share item bluesky" element should be visible
    And the "header share item tumblr" element should be visible
    And the "header share item mastodon" element should be visible

  Scenario Outline: The "<platform>" button targets its share endpoint
    Then the "header share <platform> endpoint link" element should have a count of 1
    And the "header share <platform> new tab link" element should have a count of 1
    And the "header share <platform> noopener link" element should have a count of 1

    Examples:
      | platform |
      | linkedin |
      | facebook |
      | x        |
      | whatsapp |
      | telegram |
      | reddit   |
      | pinterest |
      | threads  |
      | bluesky  |
      | tumblr   |
      | mastodon |

  Scenario: The Email button uses a mailto: link
    Then the "header share email mailto link" element should have a count of 1

  Scenario: The Copy URL button is a clipboard button, not a link
    Then the "header share copy button input" element should have a count of 1
    And the "header share copy data attribute" element should have a count of 1
    And the "header share copy link" element should have a count of 0

  Scenario: Enabling all platforms produces no JavaScript errors
    Then there should be no JavaScript errors
