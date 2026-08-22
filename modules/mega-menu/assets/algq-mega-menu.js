( function () {
	'use strict';

	function closeMenu( nav ) {
		var toggle = nav.querySelector( '.algq-mega__toggle' );
		var panel = nav.querySelector( '.algq-mega__panel' );

		if ( ! toggle || ! panel ) {
			return;
		}

		toggle.setAttribute( 'aria-expanded', 'false' );
		panel.hidden = true;
	}

	function initMenu( nav ) {
		var toggle = nav.querySelector( '.algq-mega__toggle' );
		var panel = nav.querySelector( '.algq-mega__panel' );

		if ( ! toggle || ! panel ) {
			return;
		}

		toggle.addEventListener( 'click', function () {
			var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
			toggle.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			panel.hidden = expanded;

			if ( ! expanded ) {
				var firstLink = panel.querySelector( 'a' );
				if ( firstLink ) {
					firstLink.focus();
				}
			}
		} );

		nav.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				closeMenu( nav );
				toggle.focus();
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! nav.contains( event.target ) ) {
				closeMenu( nav );
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.algq-mega' ).forEach( initMenu );
	} );
}() );
