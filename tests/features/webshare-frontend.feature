@webshare @frontend
Feature: Webshare module — front-end rendering
  As a site visitor
  I want to see social sharing buttons rendered by the Webshare module
  So that I can share the page on social platforms or through my device

  Background:
    Given I am on the homepage

  Scenario: The Webshare single-directory component is rendered
    Then ".webshare" should be visible
    And ".webshare__list" should be visible

  Scenario: The component uses the horizontal orientation by default
    Then ".webshare" should have class "webshare--horizontal"

  # Copy URL + WhatsApp are disabled on a fresh install; admins can opt-in.
  Scenario: All default platform share buttons are present
    Then ".webshare__item" should have a count of 4
    And ".webshare__item--linkedin" should be visible
    And ".webshare__item--facebook_share" should be visible
    And ".webshare__item--x" should be visible
    And ".webshare__item--whatsapp" should have a count of 0
    And ".webshare__item--copy" should have a count of 0

  Scenario: Platform buttons link to the correct sharing endpoints
    Then ".webshare__item--facebook_share a[href*='facebook.com/sharer']" should have a count of 1
    And ".webshare__item--x a[href*='twitter.com/intent']" should have a count of 1
    And ".webshare__item--linkedin a[href*='linkedin.com']" should have a count of 1

  Scenario: Platform share links open in a new tab safely
    Then ".webshare__item--facebook_share a[target='_blank']" should have a count of 1
    And ".webshare__item--facebook_share a[rel*='noreferrer']" should have a count of 1

  Scenario: The native Web Share API button is present
    Then ".webshare__native-button" should have a count of 1
    And ".webshare__native-button" should be visible

  Scenario: The component exposes the Web Share API payload data
    Then ".webshare[data-webshare-url]" should have a count of 1
    And ".webshare[data-webshare-title]" should have a count of 1
    And ".webshare[data-webshare-text]" should have a count of 1

  Scenario: The Webshare component produces no JavaScript errors
    Then there should be no JavaScript errors
