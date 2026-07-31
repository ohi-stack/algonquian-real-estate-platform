(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var search = document.querySelector('[data-algq-doc-search]');
    if (search) {
      search.addEventListener('input', function () {
        var term = search.value.trim().toLowerCase();
        document.querySelectorAll('[data-algq-doc-card]').forEach(function (card) {
          var text = card.getAttribute('data-search-text') || '';
          card.hidden = term !== '' && text.indexOf(term) === -1;
        });
        document.querySelectorAll('[data-algq-doc-category]').forEach(function (category) {
          var visible = category.querySelector('[data-algq-doc-card]:not([hidden])');
          category.hidden = term !== '' && !visible;
        });
      });
    }

    document.querySelectorAll('[data-algq-document-id]').forEach(function (link) {
      link.addEventListener('click', function () {
        var form = document.querySelector('.algq-doc-request input[name="document_id"]');
        if (form) {
          form.value = link.getAttribute('data-algq-document-id') || '0';
        }
      });
    });
  });
}());
