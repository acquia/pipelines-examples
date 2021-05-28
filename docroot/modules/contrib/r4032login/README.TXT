CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------
  * Redirect the HTTP 403 error page to the Drupal /user/login page with a message
    that reads, "Access denied. You must login to view this page." Also, a redirect
    to the desired page is appended in the url query string so that, once login is
    successful, the user is taken directly where they were originally trying to go.

  * Makes for a much more user-friendly Drupal.

REQUIREMENTS
------------
 * No extra module is require

INSTALLATION
------------
 * Extract and enable r4032login.

CONFIGURATION
-------------
 * Configure at admin/config/system/site-information .

MAINTAINERS
-----------
 * Brent Dunn - https://www.drupal.org/u/bdone
