(function(){
  'use strict';

  function ready(fn){
    if(document.readyState !== 'loading'){ fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  ready(function(){
    var wrap = document.querySelector('.algq-admin-wrap');
    if(!wrap){ return; }

    document.querySelectorAll('.algq-admin-card').forEach(function(card){
      card.setAttribute('tabindex','0');
      card.addEventListener('mouseenter', function(){ card.classList.add('is-active'); });
      card.addEventListener('mouseleave', function(){ card.classList.remove('is-active'); });
    });

    document.querySelectorAll('.algq-checklist li').forEach(function(item){
      item.addEventListener('click', function(){ item.classList.toggle('is-expanded'); });
    });

    var themeSelect = document.querySelector('select[name="algq_education_options[brand_theme]"]');
    if(themeSelect){
      themeSelect.addEventListener('change', function(){
        wrap.setAttribute('data-theme-preview', themeSelect.value || 'blue_gold');
      });
    }
  });
})();
