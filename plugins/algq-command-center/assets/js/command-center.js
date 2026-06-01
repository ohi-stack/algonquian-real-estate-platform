(function () {
  'use strict';

  const storageKeyTheme = 'algq_command_center_theme';
  const storageKeyLayout = 'algq_command_center_layout';

  function applyTheme(dashboard, theme) {
    if (!dashboard) return;
    dashboard.classList.toggle('algq-dark', theme === 'dark');
  }

  function initTheme(dashboard) {
    const saved = window.localStorage.getItem(storageKeyTheme) || 'light';
    applyTheme(dashboard, saved);
    dashboard.querySelectorAll('[data-algq-theme-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        const next = dashboard.classList.contains('algq-dark') ? 'light' : 'dark';
        window.localStorage.setItem(storageKeyTheme, next);
        applyTheme(dashboard, next);
      });
    });
  }

  function initTabs(dashboard) {
    const tabs = dashboard.querySelectorAll('[data-algq-tab]');
    const panels = dashboard.querySelectorAll('[data-algq-panel]');
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        const target = tab.getAttribute('data-algq-tab');
        tabs.forEach(function (item) { item.classList.remove('is-active'); });
        panels.forEach(function (panel) {
          panel.classList.toggle('is-active', panel.getAttribute('data-algq-panel') === target);
        });
        tab.classList.add('is-active');
      });
    });
  }

  function initDrag(dashboard) {
    dashboard.querySelectorAll('[data-algq-sortable]').forEach(function (zone) {
      let dragged = null;
      const saved = window.localStorage.getItem(storageKeyLayout);
      if (saved) {
        try {
          JSON.parse(saved).forEach(function (label) {
            const match = Array.from(zone.children).find(function (card) {
              const cardLabel = card.querySelector('.algq-kpi-label');
              return cardLabel && cardLabel.textContent === label;
            });
            if (match) zone.appendChild(match);
          });
        } catch (e) {}
      }

      zone.addEventListener('dragstart', function (event) {
        dragged = event.target.closest('.algq-kpi-card');
        if (dragged) dragged.classList.add('is-dragging');
      });

      zone.addEventListener('dragend', function () {
        if (dragged) dragged.classList.remove('is-dragging');
        dragged = null;
        const layout = Array.from(zone.children).map(function (card) {
          const label = card.querySelector('.algq-kpi-label');
          return label ? label.textContent : '';
        }).filter(Boolean);
        window.localStorage.setItem(storageKeyLayout, JSON.stringify(layout));
      });

      zone.addEventListener('dragover', function (event) {
        event.preventDefault();
        const after = Array.from(zone.querySelectorAll('.algq-kpi-card:not(.is-dragging)')).find(function (card) {
          const box = card.getBoundingClientRect();
          return event.clientY < box.top + box.height / 2;
        });
        if (!dragged) return;
        if (after) zone.insertBefore(dragged, after);
        else zone.appendChild(dragged);
      });
    });
  }

  function initExports(dashboard) {
    dashboard.querySelectorAll('[data-algq-export]').forEach(function (button) {
      button.addEventListener('click', function () {
        const type = button.getAttribute('data-algq-export');
        window.alert('Algonquian Command Center ' + type.toUpperCase() + ' export is staged. Production export handlers must verify capability, nonce, and audit logging before file output.');
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-algq-dashboard]').forEach(function (dashboard) {
      initTheme(dashboard);
      initTabs(dashboard);
      initDrag(dashboard);
      initExports(dashboard);
    });
  });
})();
