( function () {
	'use strict';

	var MOBILE_BREAKPOINT = 1024;

	function isMobile() {
		return window.matchMedia( '(max-width: ' + MOBILE_BREAKPOINT + 'px)' ).matches;
	}

	function focusableElements( container ) {
		return Array.prototype.slice.call(
			container.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			)
		).filter( function ( element ) {
			return element.offsetParent !== null;
		} );
	}

	function setSectionState( section, expanded ) {
		var toggle = section.querySelector( ':scope > .algq-navigation__section-head > .algq-navigation__section-toggle' );
		if ( ! toggle ) {
			return;
		}

		section.classList.toggle( 'is-expanded', expanded );
		toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
	}

	function closeSections( nav, except ) {
		nav.querySelectorAll( '[data-algq-nav-section]' ).forEach( function ( section ) {
			if ( except && section === except ) {
				return;
			}
			setSectionState( section, false );
		} );
	}

	function initNavigation( nav ) {
		var mobileToggle = nav.querySelector( '.algq-navigation__mobile-toggle' );
		var menu = nav.querySelector( '.algq-navigation__menu' );
		var backdrop = nav.querySelector( '.algq-navigation__backdrop' );
		var sections = nav.querySelectorAll( '[data-algq-nav-section]' );

		if ( ! mobileToggle || ! menu ) {
			return;
		}

		function openMobileMenu() {
			if ( ! isMobile() ) {
				return;
			}

			nav.classList.add( 'is-mobile-open' );
			document.body.classList.add( 'algq-navigation-open' );
			mobileToggle.setAttribute( 'aria-expanded', 'true' );
			mobileToggle.setAttribute( 'aria-label', 'Close navigation' );

			// The six primary sections are visible immediately. No second Menu action is required.
			var firstPrimaryLink = menu.querySelector( '.algq-navigation__section-link' );
			if ( firstPrimaryLink ) {
				window.requestAnimationFrame( function () {
					firstPrimaryLink.focus();
				} );
			}
		}

		function closeMobileMenu( restoreFocus ) {
			nav.classList.remove( 'is-mobile-open' );
			document.body.classList.remove( 'algq-navigation-open' );
			mobileToggle.setAttribute( 'aria-expanded', 'false' );
			mobileToggle.setAttribute( 'aria-label', 'Open navigation' );
			closeSections( nav );

			if ( restoreFocus ) {
				mobileToggle.focus();
			}
		}

		mobileToggle.addEventListener( 'click', function () {
			if ( nav.classList.contains( 'is-mobile-open' ) ) {
				closeMobileMenu( true );
			} else {
				openMobileMenu();
			}
		} );

		if ( backdrop ) {
			backdrop.addEventListener( 'click', function () {
				closeMobileMenu( true );
			} );
		}

		sections.forEach( function ( section ) {
			var toggle = section.querySelector( ':scope > .algq-navigation__section-head > .algq-navigation__section-toggle' );
			if ( ! toggle ) {
				return;
			}

			toggle.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';

				if ( ! expanded ) {
					closeSections( nav, section );
				}
				setSectionState( section, ! expanded );
			} );
		} );

		nav.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				if ( isMobile() && nav.classList.contains( 'is-mobile-open' ) ) {
					closeMobileMenu( true );
					return;
				}

				closeSections( nav );
				mobileToggle.focus();
				return;
			}

			if ( event.key !== 'Tab' || ! isMobile() || ! nav.classList.contains( 'is-mobile-open' ) ) {
				return;
			}

			var focusable = focusableElements( menu ).concat( [ mobileToggle ] );
			if ( ! focusable.length ) {
				return;
			}

			var first = focusable[ 0 ];
			var last = focusable[ focusable.length - 1 ];
			if ( event.shiftKey && document.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( isMobile() ) {
				return;
			}
			if ( ! nav.contains( event.target ) ) {
				closeSections( nav );
			}
		} );

		window.addEventListener( 'resize', function () {
			if ( ! isMobile() && nav.classList.contains( 'is-mobile-open' ) ) {
				closeMobileMenu( false );
			}
		} );
	}

	function boot() {
		document.querySelectorAll( '[data-algq-navigation]' ).forEach( initNavigation );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
