/**
 * @file
 */

(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.twitterMediaEntity = {
    attach: function (context) {
      function _init () {
        twttr.widgets.load(context);
      }

      // If the tweet is being embedded in a CKEditor's iFrame the widgets
      // library might not have been loaded yet.
      if (typeof twttr == 'undefined') {
        $.getScript('//platform.twitter.com/widgets.js', _init);
      }
      else {
        _init();
      }
    }
  };

})(jQuery, Drupal);
