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

  Drupal.behaviors.WebShareScroll = {
    attach: function (context, settings) {
      // Ensure the scroll listener is added only once
      $(once('webshare-scroll', 'body', context)).each(function () {
        window.addEventListener('scroll', function () {
          // Select both left and right menus
          const menus = document.querySelectorAll('.webshare-left, .webshare-right');
          const main = document.querySelector('main'); // Main content area
          const footer = document.querySelector('footer'); // Footer element
  
          if (menus.length > 0 && main && footer) {
            const scrollPosition = window.scrollY;
            const mainTop = main.getBoundingClientRect().top + window.scrollY;
            const mainBottom = main.getBoundingClientRect().bottom + window.scrollY;
            const footerTop = footer.getBoundingClientRect().top + window.scrollY;
  
            menus.forEach(menu => {
              const menuHeight = menu.offsetHeight;
              const menuTop = scrollPosition;
              const menuBottom = menuTop + menuHeight;
  
              // Determine the specific class to add based on the menu type
              const showClass = menu.classList.contains('webshare-left')
                ? 'show-menu-left'
                : 'show-menu-right';
  
              // Add or remove the appropriate class based on scroll position
              if (
                menuTop >= mainTop &&
                menuBottom <= mainBottom &&
                menuBottom < footerTop
              ) {
                menu.classList.add(showClass);
              } else {
                menu.classList.remove(showClass);
              }
            });
          }
        });
      });
    },
  };
  
})(jQuery, Drupal, once);
