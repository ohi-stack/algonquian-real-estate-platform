(function(){
	'use strict';

	var body=document.body;
	if(!body||!body.classList.contains('are-admin-ui')){return;}

	var media=window.matchMedia?window.matchMedia('(prefers-reduced-motion: reduce)'):null;
	var reduce=media&&media.matches;
	var hydrated=new WeakSet();

	function hydrate(root){
		var scope=root&&root.querySelectorAll?root:document;
		var cards=scope.querySelectorAll('.postbox,.card,.algq-admin-card,.algq-admin-panel,.algq-kpi,.algq-platform-kpis>div,.are-ui-card,.are-ui-panel,.are-ui-kpi');
		cards.forEach(function(el,index){
			if(hydrated.has(el)){return;}
			hydrated.add(el);
			el.classList.add('are-ui-hover-lift');
			if(!reduce&&index<24){
				el.classList.add('are-ui-motion-ready');
				el.style.setProperty('--are-delay',Math.min(index*35,280)+'ms');
				window.requestAnimationFrame(function(){el.classList.add('are-ui-motion-in');});
			}
		});

		scope.querySelectorAll('[data-are-progress]').forEach(function(el){
			if(hydrated.has(el)){return;}
			hydrated.add(el);
			var raw=parseFloat(el.getAttribute('data-are-progress')||'0');
			var value=Math.max(0,Math.min(100,isNaN(raw)?0:raw));
			var bar=el.querySelector('.are-ui-progress__bar');
			if(bar){
				bar.setAttribute('aria-valuemin','0');
				bar.setAttribute('aria-valuemax','100');
				bar.setAttribute('aria-valuenow',String(value));
				bar.style.width=reduce?value+'%':'0%';
				if(!reduce){window.requestAnimationFrame(function(){bar.style.width=value+'%';});}
			}
		});

		scope.querySelectorAll('[data-are-live="1"]').forEach(function(el){
			if(el.querySelector('.are-ui-live-dot')){return;}
			var dot=document.createElement('span');
			dot.className='are-ui-live-dot';
			dot.setAttribute('aria-hidden','true');
			el.insertBefore(dot,el.firstChild);
		});
	}

	hydrate(document);

	if('MutationObserver' in window){
		var target=document.getElementById('wpbody-content');
		if(target){
			var observer=new MutationObserver(function(mutations){
				mutations.forEach(function(mutation){
					mutation.addedNodes.forEach(function(node){
						if(node.nodeType===1){hydrate(node);}
					});
				});
			});
			observer.observe(target,{childList:true,subtree:true});
		}
	}
})();
