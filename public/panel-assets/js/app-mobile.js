(function () {
  'use strict';

  var MOBILE_MAX = 767.98;

  function isMobile() {
    return window.matchMedia('(max-width: ' + MOBILE_MAX + 'px)').matches;
  }

  function closeSidebar() {
    document.querySelector('.app')?.classList.remove('sidenav-toggled');
  }

  function initSidebarNavClose() {
    var sidebar = document.querySelector('.app-sidebar');
    if (!sidebar) {
      return;
    }

    sidebar.addEventListener('click', function (event) {
      if (!isMobile()) {
        return;
      }

      var link = event.target.closest('a.app-menu__item, a.treeview-item');
      if (!link || link.getAttribute('href') === '#') {
        return;
      }

      closeSidebar();
    });
  }

  function initResizeClose() {
    window.addEventListener('resize', function () {
      if (!isMobile()) {
        closeSidebar();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initSidebarNavClose();
      initResizeClose();
    });
  } else {
    initSidebarNavClose();
    initResizeClose();
  }
})();
