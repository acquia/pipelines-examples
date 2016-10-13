Feature: In order to verify that Pipelines can install and run Behat, I need a simple feature.

  Scenario: Visit the index of the installed site-install
    Given I am not logged in
    When I am on the homepage
    Then I should see the text "No front page content has been created yet."
