(function(){'use strict';
function ready(fn){if(document.readyState!=='loading'){fn();}else{document.addEventListener('DOMContentLoaded',fn);}}
ready(function(){
 var shells=document.querySelectorAll('.algq-admin-shell,.algq-dashboard,.wrap[class*="algq"]');
 shells.forEach(function(shell){shell.classList.add('algq-motion-enter');
  shell.querySelectorAll('.algq-kpi-card,.algq-panel,.algq-plugin-card').forEach(function(card,i){card.classList.add('algq-motion-enter');if(i<8){card.style.animationDelay=(Math.min(i,7)*45)+'ms';}});
 });
 document.addEventListener('click',function(e){
  var tab=e.target.closest('.algq-tab');if(tab){var tabs=tab.closest('.algq-tabs');if(tabs){tabs.querySelectorAll('.algq-tab').forEach(function(t){t.classList.remove('is-active');t.setAttribute('aria-selected','false');});tab.classList.add('is-active');tab.setAttribute('aria-selected','true');var target=tab.getAttribute('data-target');if(target){var scope=tabs.parentElement;scope.querySelectorAll('.algq-tab-panel').forEach(function(p){p.hidden=true;});var panel=scope.querySelector(target);if(panel){panel.hidden=false;}}}}
  var dismiss=e.target.closest('[data-algq-dismiss]');if(dismiss){var notice=dismiss.closest('.notice,.algq-notice');if(notice){notice.style.opacity='0';notice.style.transform='translateY(-6px)';setTimeout(function(){notice.remove();},180);}}
  var dark=e.target.closest('[data-algq-dark-toggle]');if(dark){var root=dark.closest('.algq-admin-shell,.algq-dashboard')||document.body;root.classList.toggle('algq-dark');try{localStorage.setItem('algq-admin-dark',root.classList.contains('algq-dark')?'1':'0');}catch(err){}}
  var confirmEl=e.target.closest('[data-algq-confirm]');if(confirmEl&&!window.confirm(confirmEl.getAttribute('data-algq-confirm')||'Continue with this action?')){e.preventDefault();e.stopImmediatePropagation();}
 });
 try{if(localStorage.getItem('algq-admin-dark')==='1'){shells.forEach(function(s){s.classList.add('algq-dark');});}}catch(err){}
});
})();
