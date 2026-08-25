/* Algonquian Real Estate shared admin UI interactions — v1.0 */
(function(){'use strict';
function ready(fn){if(document.readyState!=='loading'){fn();}else{document.addEventListener('DOMContentLoaded',fn);}}
ready(function(){
  document.querySelectorAll('[data-algq-dark-toggle]').forEach(function(btn){btn.addEventListener('click',function(){var root=document.querySelector('.algq-admin-shell,.algq-dashboard');if(!root)return;root.classList.toggle('algq-dark');try{localStorage.setItem('algq_admin_dark',root.classList.contains('algq-dark')?'1':'0');}catch(e){}});});
  var root=document.querySelector('.algq-admin-shell,.algq-dashboard');try{if(root&&localStorage.getItem('algq_admin_dark')==='1')root.classList.add('algq-dark');}catch(e){}
  document.querySelectorAll('.algq-tabs').forEach(function(tablist){var tabs=[].slice.call(tablist.querySelectorAll('.algq-tab'));tabs.forEach(function(tab,i){tab.setAttribute('role','tab');tab.addEventListener('click',function(){activate(tab,tabs);});tab.addEventListener('keydown',function(e){if(e.key!=='ArrowRight'&&e.key!=='ArrowLeft')return;e.preventDefault();var n=e.key==='ArrowRight'?(i+1)%tabs.length:(i-1+tabs.length)%tabs.length;tabs[n].focus();activate(tabs[n],tabs);});});});
  document.querySelectorAll('[data-algq-dismiss]').forEach(function(btn){btn.addEventListener('click',function(){var notice=btn.closest('.notice,.algq-notice');if(notice){notice.style.opacity='0';notice.style.transform='translateY(-6px)';setTimeout(function(){notice.remove();},180);}});});
  document.querySelectorAll('[data-algq-confirm-export]').forEach(function(el){el.addEventListener('click',function(e){if(!window.confirm(el.getAttribute('data-algq-confirm-export')||'Export this report?'))e.preventDefault();});});
});
function activate(tab,tabs){tabs.forEach(function(t){var active=t===tab;t.classList.toggle('is-active',active);t.setAttribute('aria-selected',active?'true':'false');var id=t.getAttribute('aria-controls');if(id){var p=document.getElementById(id);if(p){p.hidden=!active;p.setAttribute('role','tabpanel');}}});}
})();
