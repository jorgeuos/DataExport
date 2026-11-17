/**
 * © 2024 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
  var POLL_MS = 5000;
  var STARTED_KEY = 'dataExportBackupStarted';

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text == null ? '' : String(text);
    return div.innerHTML;
  }

  function formatElapsed(seconds) {
    var m = Math.floor(seconds / 60);
    var s = seconds % 60;
    return m > 0 ? m + ' min ' + s + ' s' : s + ' s';
  }

  function renderStatus(container, button, status) {
    if (!status) {
      container.innerHTML = '';
      return;
    }
    container.setAttribute('data-state', status.state);
    if (status.state === 'running') {
      container.innerHTML = '<p class="alert alert-info">Backup running since ' + escapeHtml(status.startedAt)
        + ' (' + formatElapsed(status.elapsed) + ')&hellip; You can leave this page.</p>';
      if (button) { button.disabled = true; }
      return;
    }
    if (button) { button.disabled = false; }
    if (status.state === 'done' && status.file) {
      container.innerHTML = '<p class="alert alert-success">Backup finished: <a href="' + escapeHtml(status.downloadUrl) + '">'
        + escapeHtml(status.file) + '</a> (' + escapeHtml(status.size) + ', ' + formatElapsed(status.elapsed) + ')</p>';
    } else if (status.state === 'failed') {
      container.innerHTML = '<p class="alert alert-danger">Backup failed: ' + escapeHtml(status.error) + '</p>';
    }
  }

  function poll(container, button) {
    fetch(container.getAttribute('data-status-url'), { credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (status) {
        renderStatus(container, button, status);
        if (status && status.state === 'running') {
          window.setTimeout(function () { poll(container, button); }, POLL_MS);
          return;
        }
        // Download automatically only in the tab that started the backup
        if (status && status.state === 'done' && status.downloadUrl && window.sessionStorage.getItem(STARTED_KEY)) {
          window.sessionStorage.removeItem(STARTED_KEY);
          window.location.href = status.downloadUrl;
        }
      })
      .catch(function () {
        window.setTimeout(function () { poll(container, button); }, POLL_MS * 2);
      });
  }

  function initBackup() {
    var form = document.getElementById('de-backup-form');
    var container = document.getElementById('de-backup-status');
    if (!form || !container || !window.fetch) {
      return;
    }
    var button = document.getElementById('de-backup-button');

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (button) { button.disabled = true; }
      fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (result) {
          if (!result.ok) {
            container.innerHTML = '<p class="alert alert-danger">' + escapeHtml(result.error) + '</p>';
            if (result.status && result.status.state === 'running') {
              renderStatus(container, button, result.status);
              poll(container, button);
            } else if (button) {
              button.disabled = false;
            }
            return;
          }
          window.sessionStorage.setItem(STARTED_KEY, '1');
          renderStatus(container, button, result.status);
          window.setTimeout(function () { poll(container, button); }, 1000);
        })
        .catch(function (error) {
          container.innerHTML = '<p class="alert alert-danger">Could not start the backup: ' + escapeHtml(error.message) + '</p>';
          if (button) { button.disabled = false; }
        });
    });

    if (container.getAttribute('data-state') === 'running') {
      poll(container, button);
    }
  }

  function initFileSelection() {
    var mainCheck = document.getElementById('de-sel-files');
    if (!mainCheck) {
      return;
    }
    var fileCheckboxes = document.querySelectorAll('.de-all-files input[type="checkbox"]');
    mainCheck.addEventListener('change', function () {
      fileCheckboxes.forEach(function (checkbox) {
        checkbox.checked = mainCheck.checked;
      });
    });
  }

  function init() {
    initBackup();
    initFileSelection();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
