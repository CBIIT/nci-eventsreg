(function (Drupal, once) {
  'use strict';
  function revealHash() {
    const id = window.location.hash.slice(1);
    if (!id) return;
    const target = document.getElementById(id);
    if (!target) return;
    const task = target.closest('.eth-task');
    if (task) {
      const discussion = task.querySelector('details.eth-discussion');
      if (discussion) discussion.open = true;
    }
  }
  Drupal.behaviors.eventTaskHub = {
    attach(context) {
      once('eth-links', '.eth-task', context).forEach(function (task) {
        task.querySelectorAll('.eth-discussion .pager a').forEach(function (link) {
          const url = new URL(link.href, window.location.href);
          url.hash = task.id;
          link.href = url.href;
        });
      });
      revealHash();
      once('eth-hash', 'body', context).forEach(function () {
        window.addEventListener('hashchange', revealHash);
      });
    }
  };
})(Drupal, once);
