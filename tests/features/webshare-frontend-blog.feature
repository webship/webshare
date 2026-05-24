@webshare @frontend
Feature: Webshare module — front-end rendering on a blog node
  Varbase's frontend theme (vartheme_bs5) uses Layout Builder, so the
  module's node-view integration is exercised on a blog node URL rather
  than the homepage.

  Background:
    Given I am on "/blog/community-behind-varbase-support-and-collaboration"

  Scenario: The Sharing component renders on the blog node
    Then ".webshare" should be visible
    And ".webshare__list" should be visible

  Scenario: Rail-end placement matches the approved article design
    Then ".webshare" should have class "webshare--vertical"
    And ".webshare" should have class "webshare--rail-end"

  Scenario: Default platforms render and Copy is disabled by default
    Then ".webshare__item--linkedin" should be visible
    And ".webshare__item--facebook_share" should be visible
    And ".webshare__item--x" should be visible
    And ".webshare__item--whatsapp" should have a count of 0
    And ".webshare__item--copy" should have a count of 0

  Scenario: Platform share links open in a new tab safely
    Then ".webshare__item--facebook_share a[target='_blank']" should have a count of 1
    And ".webshare__item--facebook_share a[rel*='noreferrer']" should have a count of 1

  Scenario: Native share button is present and visible
    Then ".webshare__native-button" should have a count of 1
    And ".webshare__native-button" should be visible

  Scenario: Web Share API payload data attributes are exposed
    Then ".webshare[data-webshare-url]" should have a count of 1
    And ".webshare[data-webshare-title]" should have a count of 1
    And ".webshare[data-webshare-text]" should have a count of 1

  Scenario: The Webshare component produces no JavaScript errors
    Then there should be no JavaScript errors
