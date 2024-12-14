(function ($, Drupal, once) {

  'use strict';

  Drupal.behaviors.WebShare = {
    attach: function (context, settings) {
      $(once('webshare-activate',  context))
        .click(function (e) {
        var links = $(this).parent().parent().find('.webshare-links');
        $(links).toggleClass('webshare-active');
        $(links).toggleClass('webshare-inactive');
        e.stopPropagation();
      });
      $(once('webshare-deactivate',  context))
        .click(function () {
        $('.webshare-links.webshare-active').addClass('webshare-inactive');
        $('.webshare-links.webshare-active').removeClass('webshare-active');
      });
      $(once('webshare-link-copy', '.webshare-link.copy', context))
        .click(function () {
        var url = window.location.href;

        if (window.clipboardData && window.clipboardData.setData) {
          // IE specific to prevent textarea being shown while dialog is visible.
          return clipboardData.setData("Text", url);
        }
        else if (document.queryCommandSupported && document.queryCommandSupported('copy')) {
          var textarea = document.createElement('textarea');
          textarea.textContent = url;
          textarea.style.position = 'fixed';

          document.body.appendChild(textarea);

          textarea.focus();
          textarea.select();
          try {
            return document.execCommand('copy');
          }
          catch (ex) {
            return false;
          }
          finally {
            document.body.removeChild(textarea);
          }
        }
      });
    }
  };

})(jQuery, Drupal, once);
