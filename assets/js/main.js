/**
 * Lihan theme core scripts (vanilla, no dependencies).
 *
 * Components
 * - Drawers / toggles: [data-lh-open="ID"] opens #ID, [data-lh-close] closes.
 * - Carousel: [data-lh-carousel] (scroll-snap) and [data-lh-carousel-fade] (crossfade).
 * - Read more: [data-lh-readmore].
 * - Announcement rotator, sticky header state, desktop dropdown Escape handling,
 *   mobile sub-menu accordion, footer accordions.
 *
 * Public API
 * - window.Lihan.init( root )               (re)initialise components inside root (idempotent).
 * - window.Lihan.openDrawer( id, opener )   open a drawer / panel by element id.
 * - window.Lihan.closeDrawer()              close the open drawer.
 * - document event 'lihan:open-drawer'      detail: { id } — same as openDrawer.
 * - document event 'lihan:init'             dispatched after each init, detail: { root }.
 */
( function () {
	'use strict';

	var settings = window.lihanSettings || {};
	var i18n = settings.i18n || {};
	var doc = document;
	var html = doc.documentElement;
	var reduceMotion = window.matchMedia ? window.matchMedia( '(prefers-reduced-motion: reduce)' ) : { matches: false, addEventListener: function () {} };

	var FOCUSABLE = 'a[href], area[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), iframe, [tabindex]:not([tabindex="-1"]), [contenteditable="true"]';

	function t( key, fallback ) {
		return typeof i18n[ key ] === 'string' && i18n[ key ] ? i18n[ key ] : fallback;
	}

	function sprintf( format ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var index = 0;
		return String( format ).replace( /%(?:(\d+)\$)?[ds]/g, function ( match, position ) {
			var value = position ? args[ parseInt( position, 10 ) - 1 ] : args[ index++ ];
			return value === undefined ? '' : String( value );
		} );
	}

	function each( list, callback ) {
		Array.prototype.forEach.call( list, callback );
	}

	/**
	 * Elements matching selector inside root, including root itself.
	 */
	function queryAll( root, selector ) {
		var scope = root && root.querySelectorAll ? root : doc;
		var found = Array.prototype.slice.call( scope.querySelectorAll( selector ) );
		if ( scope !== doc && scope.matches && scope.matches( selector ) ) {
			found.unshift( scope );
		}
		return found;
	}

	function isVisible( el ) {
		return !! ( el.offsetWidth || el.offsetHeight || el.getClientRects().length ) && window.getComputedStyle( el ).visibility !== 'hidden';
	}

	function smoothBehavior() {
		return reduceMotion.matches ? 'auto' : 'smooth';
	}

	/* ---------------------------------------------------------------------
	 * Drawers
	 * ------------------------------------------------------------------ */
	var drawer = {
		current: null,
		opener: null,
		overlay: null,

		getOverlay: function () {
			if ( ! this.overlay || ! this.overlay.isConnected ) {
				this.overlay = doc.querySelector( '.lh-overlay' );
				if ( ! this.overlay ) {
					this.overlay = doc.createElement( 'div' );
					this.overlay.className = 'lh-overlay';
					this.overlay.setAttribute( 'data-lh-close', '' );
					this.overlay.setAttribute( 'aria-hidden', 'true' );
					doc.body.appendChild( this.overlay );
				}
			}
			return this.overlay;
		},

		setExpanded: function ( id, expanded ) {
			each( doc.querySelectorAll( '[data-lh-open="' + id + '"]' ), function ( toggle ) {
				toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
			} );
		},

		lock: function () {
			var scrollbar = window.innerWidth - html.clientWidth;
			html.style.setProperty( '--lh-scrollbar', ( scrollbar > 0 ? scrollbar : 0 ) + 'px' );
			html.classList.add( 'lh-locked' );
		},

		unlock: function () {
			html.classList.remove( 'lh-locked' );
			html.style.removeProperty( '--lh-scrollbar' );
		},

		open: function ( id, opener ) {
			var panel = doc.getElementById( id );
			if ( ! panel ) {
				return false;
			}
			if ( this.current === panel ) {
				return true;
			}
			if ( this.current ) {
				this.close( { restoreFocus: false } );
			}

			var isSearch = panel.classList.contains( 'lh-search-drawer' );

			this.current = panel;
			this.opener = opener || ( doc.activeElement !== doc.body ? doc.activeElement : null );

			panel.classList.add( 'is-open' );
			panel.removeAttribute( 'aria-hidden' );
			panel.removeAttribute( 'inert' );
			this.setExpanded( id, true );
			this.getOverlay().classList.add( 'is-visible' );
			html.classList.toggle( 'lh-search-open', isSearch );
			html.classList.add( 'lh-drawer-open' );
			if ( ! isSearch ) {
				this.lock();
			}

			// Focus the first field (search) or the first focusable element once visible.
			window.setTimeout( function () {
				if ( drawer.current !== panel ) {
					return;
				}
				var target = panel.querySelector( 'input[type="search"], [autofocus]' ) || drawer.focusables( panel )[ 0 ];
				if ( target ) {
					target.focus( { preventScroll: true } );
				} else {
					panel.setAttribute( 'tabindex', '-1' );
					panel.focus( { preventScroll: true } );
				}
			}, 60 );

			panel.dispatchEvent( new CustomEvent( 'lihan:drawer-open', { bubbles: true, detail: { id: id } } ) );
			return true;
		},

		close: function ( options ) {
			var panel = this.current;
			if ( ! panel ) {
				return;
			}
			var restore = ! options || options.restoreFocus !== false;

			panel.classList.remove( 'is-open' );
			this.setExpanded( panel.id, false );
			this.getOverlay().classList.remove( 'is-visible' );
			html.classList.remove( 'lh-search-open', 'lh-drawer-open' );
			this.unlock();
			this.current = null;

			if ( restore && this.opener && this.opener.isConnected && typeof this.opener.focus === 'function' ) {
				this.opener.focus( { preventScroll: true } );
			}
			this.opener = null;

			panel.dispatchEvent( new CustomEvent( 'lihan:drawer-close', { bubbles: true, detail: { id: panel.id } } ) );
		},

		focusables: function ( panel ) {
			return Array.prototype.filter.call( panel.querySelectorAll( FOCUSABLE ), isVisible );
		},

		trap: function ( event ) {
			var panel = this.current;
			if ( ! panel ) {
				return;
			}
			var items = this.focusables( panel );
			if ( ! items.length ) {
				event.preventDefault();
				return;
			}
			var first = items[ 0 ];
			var last = items[ items.length - 1 ];
			if ( ! panel.contains( doc.activeElement ) ) {
				event.preventDefault();
				first.focus();
			} else if ( event.shiftKey && doc.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && doc.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		}
	};

	function initDrawers() {
		if ( drawer.bound ) {
			return;
		}
		drawer.bound = true;

		doc.addEventListener( 'click', function ( event ) {
			var target = event.target instanceof Element ? event.target : null;
			if ( ! target ) {
				return;
			}

			var opener = target.closest( '[data-lh-open]' );
			if ( opener ) {
				var id = opener.getAttribute( 'data-lh-open' );
				// Leave modified clicks on links alone (open the fallback URL in a new tab).
				if ( opener.tagName === 'A' && ( event.metaKey || event.ctrlKey || event.shiftKey || event.button === 1 ) ) {
					return;
				}
				var panel = id ? doc.getElementById( id ) : null;
				if ( panel ) {
					event.preventDefault();
					if ( drawer.current === panel ) {
						drawer.close();
					} else {
						drawer.open( id, opener );
					}
				}
				return;
			}

			var closer = target.closest( '[data-lh-close]' );
			if ( closer && drawer.current && ( closer.classList.contains( 'lh-overlay' ) || drawer.current.contains( closer ) ) ) {
				event.preventDefault();
				drawer.close();
			}
		} );

		doc.addEventListener( 'keydown', function ( event ) {
			if ( ! drawer.current ) {
				return;
			}
			if ( event.key === 'Escape' || event.key === 'Esc' ) {
				drawer.close();
			} else if ( event.key === 'Tab' && ! drawer.current.classList.contains( 'lh-search-drawer' ) ) {
				drawer.trap( event );
			}
		} );

		// The search panel is a light dropdown: clicking outside the header closes it.
		doc.addEventListener( 'focusin', function ( event ) {
			var panel = drawer.current;
			if ( panel && panel.classList.contains( 'lh-search-drawer' ) ) {
				var header = panel.closest( '.lh-site-header' ) || panel;
				if ( event.target instanceof Node && ! header.contains( event.target ) ) {
					drawer.close( { restoreFocus: false } );
				}
			}
		} );

		// Close drawers that are hidden at the new breakpoint (mobile menu / search on desktop).
		var desktop = window.matchMedia( '(min-width: 1024px)' );
		var onChange = function () {
			if ( desktop.matches && drawer.current && ( drawer.current.id === 'lh-mobile-menu' || drawer.current.id === 'lh-search-drawer' ) ) {
				drawer.close( { restoreFocus: false } );
			}
		};
		if ( desktop.addEventListener ) {
			desktop.addEventListener( 'change', onChange );
		}

		doc.addEventListener( 'lihan:open-drawer', function ( event ) {
			if ( event.detail && event.detail.id ) {
				drawer.open( String( event.detail.id ), event.detail.opener || null );
			}
		} );
		doc.addEventListener( 'lihan:close-drawer', function () {
			drawer.close();
		} );
	}

	/* ---------------------------------------------------------------------
	 * Mobile menu accordion
	 * ------------------------------------------------------------------ */
	function initSubmenuToggles() {
		if ( initSubmenuToggles.bound ) {
			return;
		}
		initSubmenuToggles.bound = true;

		doc.addEventListener( 'click', function ( event ) {
			var toggle = event.target instanceof Element ? event.target.closest( '.lh-submenu-toggle' ) : null;
			if ( ! toggle ) {
				return;
			}
			event.preventDefault();
			var open = toggle.getAttribute( 'aria-expanded' ) !== 'true';
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			var item = toggle.parentElement;
			if ( item ) {
				item.classList.toggle( 'is-open', open );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Desktop dropdowns: Escape closes the open sub-menu.
	 * ------------------------------------------------------------------ */
	function initDropdowns( root ) {
		queryAll( root, '.lh-menu' ).forEach( function ( menu ) {
			if ( menu.lihanDropdowns ) {
				return;
			}
			menu.lihanDropdowns = true;

			menu.addEventListener( 'keydown', function ( event ) {
				if ( event.key !== 'Escape' && event.key !== 'Esc' ) {
					return;
				}
				var target = event.target instanceof Element ? event.target : null;
				if ( ! target ) {
					return;
				}
				var panel = target.closest( '.sub-menu' );
				var item;
				if ( panel ) {
					// From inside a panel: close it and return focus to its parent link.
					item = panel.parentElement;
					var link = item ? item.querySelector( ':scope > a' ) : null;
					if ( link ) {
						link.focus();
					}
				} else {
					item = target.closest( '.menu-item-has-children' );
				}
				if ( ! item ) {
					return;
				}
				item.classList.add( 'is-dismissed' );
			} );

			var reset = function ( event ) {
				var item = event.currentTarget;
				if ( event.type === 'focusout' && item.contains( event.relatedTarget ) ) {
					return;
				}
				item.classList.remove( 'is-dismissed' );
			};

			each( menu.querySelectorAll( '.menu-item-has-children' ), function ( item ) {
				item.addEventListener( 'mouseleave', reset );
				item.addEventListener( 'focusout', reset );
				item.addEventListener( 'mouseenter', function () {
					item.classList.remove( 'is-dismissed' );
				} );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Footer accordions (mobile)
	 * ------------------------------------------------------------------ */
	function initFooterToggles() {
		if ( initFooterToggles.bound ) {
			return;
		}
		initFooterToggles.bound = true;

		doc.addEventListener( 'click', function ( event ) {
			var toggle = event.target instanceof Element ? event.target.closest( '.lh-footer__toggle' ) : null;
			if ( ! toggle ) {
				return;
			}
			var open = toggle.getAttribute( 'aria-expanded' ) !== 'true';
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			var col = toggle.closest( '[data-lh-footer-col]' );
			if ( col ) {
				col.classList.toggle( 'is-open', open );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Announcement rotator
	 * ------------------------------------------------------------------ */
	function initAnnouncements( root ) {
		queryAll( root, '[data-lh-announce]' ).forEach( function ( list ) {
			if ( list.lihanAnnounce ) {
				return;
			}
			var items = list.querySelectorAll( '.lh-announce__item' );
			if ( items.length < 2 ) {
				return;
			}
			list.lihanAnnounce = true;

			var index = 0;
			var timer = 0;
			var paused = false;
			var delay = parseInt( list.getAttribute( 'data-interval' ), 10 ) || 4000;

			function show( next ) {
				items[ index ].classList.remove( 'is-active' );
				index = ( next + items.length ) % items.length;
				items[ index ].classList.add( 'is-active' );
			}

			function start() {
				stop();
				timer = window.setInterval( function () {
					if ( ! paused && ! doc.hidden ) {
						show( index + 1 );
					}
				}, delay );
			}

			function stop() {
				if ( timer ) {
					window.clearInterval( timer );
					timer = 0;
				}
			}

			list.addEventListener( 'mouseenter', function () {
				paused = true;
			} );
			list.addEventListener( 'mouseleave', function () {
				paused = false;
			} );
			list.addEventListener( 'focusin', function () {
				paused = true;
			} );
			list.addEventListener( 'focusout', function () {
				paused = false;
			} );

			// With reduced motion the CSS drops the fade; messages still change, gently.
			start();
		} );
	}

	/* ---------------------------------------------------------------------
	 * Sticky header state and the --lh-sticky-top / --lh-header-h variables
	 * ------------------------------------------------------------------ */
	function initHeader() {
		var header = doc.querySelector( '[data-lh-header]' );
		if ( ! header || header.lihanHeader ) {
			return;
		}
		header.lihanHeader = true;

		var sticky = header.classList.contains( 'lh-site-header--sticky' );

		function measure() {
			var height = Math.round( header.getBoundingClientRect().height );
			if ( ! height ) {
				return;
			}
			html.style.setProperty( '--lh-header-h', height + 'px' );
			if ( sticky ) {
				var top = parseFloat( window.getComputedStyle( header ).top ) || 0;
				html.style.setProperty( '--lh-sticky-top', ( height + top ) + 'px' );
			}
		}

		measure();
		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( measure ).observe( header );
		} else {
			window.addEventListener( 'resize', measure );
		}

		if ( ! sticky ) {
			return;
		}

		var sentinel = doc.querySelector( '.lh-header-sentinel' );
		if ( sentinel && 'IntersectionObserver' in window ) {
			var observer;
			var observe = function () {
				if ( observer ) {
					observer.disconnect();
				}
				var top = parseFloat( window.getComputedStyle( header ).top ) || 0;
				observer = new IntersectionObserver( function ( entries ) {
					header.classList.toggle( 'is-stuck', ! entries[ 0 ].isIntersecting );
				}, { rootMargin: '-' + Math.round( top ) + 'px 0px 0px 0px', threshold: 0 } );
				observer.observe( sentinel );
			};
			observe();
			window.addEventListener( 'resize', observe );
		} else {
			var onScroll = function () {
				header.classList.toggle( 'is-stuck', window.scrollY > 40 );
			};
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			onScroll();
		}
	}

	/* ---------------------------------------------------------------------
	 * Carousel
	 * ------------------------------------------------------------------ */
	function navButton( type ) {
		var button = doc.createElement( 'button' );
		var label = type === 'prev' ? t( 'previous', 'Previous' ) : t( 'next', 'Next' );
		var path = type === 'prev' ? 'm15 18-6-6 6-6' : 'm9 18 6-6-6-6';
		button.type = 'button';
		button.className = 'lh-carousel__' + type;
		button.setAttribute( 'aria-label', label );
		button.innerHTML = '<svg class="lh-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' + path + '"/></svg>';
		return button;
	}

	function Carousel( el ) {
		this.el = el;
		this.fade = el.hasAttribute( 'data-lh-carousel-fade' );
		this.loop = el.getAttribute( 'data-loop' ) === '1' || el.getAttribute( 'data-loop' ) === 'true';
		this.autoplay = parseInt( el.getAttribute( 'data-autoplay' ), 10 ) || 0;
		this.page = 0;
		this.pages = 1;
		this.step = 1;
		this.timer = 0;
		this.hovered = false;
		this.focused = false;
		this.inView = true;
		this.frame = 0;

		this.setup();
	}

	Carousel.prototype.setup = function () {
		var el = this.el;

		// WooCommerce loops: the <ul class="products"> is the track, its <li> are slides.
		var track = el.querySelector( '.lh-carousel__track' );
		if ( ! track ) {
			track = el.querySelector( 'ul.products' );
			if ( track ) {
				track.classList.add( 'lh-carousel__track' );
			}
		}
		if ( ! track ) {
			return;
		}
		this.track = track;

		var viewport = track.closest( '.lh-carousel__viewport' );
		if ( ! viewport || ! el.contains( viewport ) ) {
			viewport = doc.createElement( 'div' );
			viewport.className = 'lh-carousel__viewport';
			track.parentNode.insertBefore( viewport, track );
			viewport.appendChild( track );
		}
		this.viewport = viewport;

		each( track.children, function ( child ) {
			child.classList.add( 'lh-carousel__slide' );
		} );
		this.slides = Array.prototype.slice.call( track.children );

		// Custom properties for values the CSS attribute selectors do not cover.
		var map = { 'data-per-view': '--lh-pv', 'data-per-view-tablet': '--lh-pv-t', 'data-per-view-mobile': '--lh-pv-m' };
		Object.keys( map ).forEach( function ( attr ) {
			var value = parseFloat( el.getAttribute( attr ) );
			if ( value > 0 ) {
				el.style.setProperty( map[ attr ], String( value ) );
			}
		} );
		var gap = parseFloat( el.getAttribute( 'data-gap' ) );
		if ( gap >= 0 ) {
			el.style.setProperty( '--lh-gap', gap + 'px' );
		}
		var gapMobile = parseFloat( el.getAttribute( 'data-gap-mobile' ) );
		if ( gapMobile >= 0 ) {
			el.style.setProperty( '--lh-gap-m', gapMobile + 'px' );
		}

		this.prev = el.querySelector( '.lh-carousel__prev' );
		this.next = el.querySelector( '.lh-carousel__next' );
		this.counter = el.querySelector( '.lh-carousel__counter' );
		this.dots = el.querySelector( '.lh-carousel__dots' );

		// Scroll carousels without controls get the standard nav.
		if ( ! this.fade && ! this.prev && ! this.next && this.slides.length > 1 ) {
			var nav = doc.createElement( 'div' );
			nav.className = 'lh-carousel__nav';
			this.prev = navButton( 'prev' );
			this.next = navButton( 'next' );
			this.counter = doc.createElement( 'span' );
			this.counter.className = 'lh-carousel__counter';
			this.counter.setAttribute( 'aria-live', 'polite' );
			nav.appendChild( this.prev );
			nav.appendChild( this.counter );
			nav.appendChild( this.next );
			viewport.parentNode.insertBefore( nav, viewport.nextSibling );
		}

		this.bind();
		this.update();
		this.startAutoplay();
	};

	Carousel.prototype.bind = function () {
		var self = this;

		if ( this.prev ) {
			this.prev.addEventListener( 'click', function () {
				self.restartAutoplay();
				self.go( self.page - 1, true );
			} );
		}
		if ( this.next ) {
			this.next.addEventListener( 'click', function () {
				self.restartAutoplay();
				self.go( self.page + 1, true );
			} );
		}

		if ( this.dots ) {
			this.dots.addEventListener( 'click', function ( event ) {
				var dot = event.target instanceof Element ? event.target.closest( '.lh-carousel__dot' ) : null;
				if ( dot ) {
					self.restartAutoplay();
					self.go( parseInt( dot.getAttribute( 'data-index' ), 10 ) || 0, false );
				}
			} );
		}

		// Arrow keys on the controls.
		this.el.addEventListener( 'keydown', function ( event ) {
			var target = event.target instanceof Element ? event.target : null;
			if ( ! target || ! target.closest( '.lh-carousel__prev, .lh-carousel__next, .lh-carousel__dot' ) ) {
				return;
			}
			if ( event.key === 'ArrowLeft' || event.key === 'ArrowRight' ) {
				event.preventDefault();
				self.go( self.page + ( event.key === 'ArrowLeft' ? -1 : 1 ), true );
				if ( target.classList.contains( 'lh-carousel__dot' ) && self.dots ) {
					var active = self.dots.querySelector( '.is-active' );
					if ( active ) {
						active.focus();
					}
				}
			}
		} );

		// Pause autoplay on hover and focus.
		this.el.addEventListener( 'mouseenter', function () {
			self.hovered = true;
		} );
		this.el.addEventListener( 'mouseleave', function () {
			self.hovered = false;
		} );
		this.el.addEventListener( 'focusin', function () {
			self.focused = true;
		} );
		this.el.addEventListener( 'focusout', function ( event ) {
			if ( ! self.el.contains( event.relatedTarget ) ) {
				self.focused = false;
			}
		} );

		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( entries ) {
				self.inView = entries[ 0 ].isIntersecting;
			}, { threshold: 0.2 } ).observe( this.el );
		}

		if ( this.fade ) {
			this.bindSwipe();
		} else {
			this.viewport.addEventListener( 'scroll', function () {
				if ( ! self.frame ) {
					self.frame = window.requestAnimationFrame( function () {
						self.frame = 0;
						self.syncFromScroll();
					} );
				}
			}, { passive: true } );
		}

		if ( 'ResizeObserver' in window ) {
			var width = 0;
			new ResizeObserver( function ( entries ) {
				var w = Math.round( entries[ 0 ].contentRect.width );
				if ( w !== width ) {
					width = w;
					self.update();
				}
			} ).observe( this.viewport );
		} else {
			window.addEventListener( 'resize', function () {
				self.update();
			} );
		}

		if ( reduceMotion.addEventListener ) {
			reduceMotion.addEventListener( 'change', function () {
				self.restartAutoplay();
			} );
		}
	};

	Carousel.prototype.bindSwipe = function () {
		var self = this;
		var startX = 0;
		var startY = 0;
		var tracking = false;

		this.viewport.addEventListener( 'touchstart', function ( event ) {
			if ( event.touches.length !== 1 ) {
				tracking = false;
				return;
			}
			tracking = true;
			startX = event.touches[ 0 ].clientX;
			startY = event.touches[ 0 ].clientY;
		}, { passive: true } );

		this.viewport.addEventListener( 'touchend', function ( event ) {
			if ( ! tracking ) {
				return;
			}
			tracking = false;
			var touch = event.changedTouches[ 0 ];
			var dx = touch.clientX - startX;
			var dy = touch.clientY - startY;
			if ( Math.abs( dx ) > 40 && Math.abs( dx ) > Math.abs( dy ) * 1.2 ) {
				self.restartAutoplay();
				self.go( self.page + ( dx < 0 ? 1 : -1 ), true );
			}
		}, { passive: true } );
	};

	/** Current per-view value (resolved by the CSS media queries). */
	Carousel.prototype.perView = function () {
		var value = parseFloat( window.getComputedStyle( this.el ).getPropertyValue( '--lh-per-view' ) );
		return value > 0 ? value : 1;
	};

	Carousel.prototype.update = function () {
		if ( ! this.track ) {
			return;
		}
		var count = this.slides.length;

		if ( this.fade ) {
			this.pages = count;
			this.step = 1;
			var active = 0;
			this.slides.forEach( function ( slide, i ) {
				if ( slide.classList.contains( 'is-active' ) ) {
					active = i;
				}
			} );
			this.page = Math.min( this.page || active, Math.max( 0, count - 1 ) );
		} else {
			var perView = this.perView();
			this.step = Math.max( 1, Math.floor( perView + 0.001 ) );
			this.pages = count > perView + 0.01 ? 1 + Math.ceil( ( count - perView ) / this.step - 0.001 ) : 1;
			this.page = Math.min( this.page, this.pages - 1 );
		}

		this.el.classList.toggle( 'is-static', this.pages <= 1 );
		this.buildDots();

		if ( this.fade ) {
			this.applyFade();
		} else {
			this.syncFromScroll();
		}
		this.render();
	};

	Carousel.prototype.buildDots = function () {
		if ( ! this.dots ) {
			return;
		}
		var needed = this.pages > 1 ? this.pages : 0;
		if ( this.dots.children.length === needed && this.dots.lihanBuilt ) {
			return;
		}
		this.dots.lihanBuilt = true;
		this.dots.innerHTML = '';
		for ( var i = 0; i < needed; i++ ) {
			var dot = doc.createElement( 'button' );
			dot.type = 'button';
			dot.className = 'lh-carousel__dot';
			dot.setAttribute( 'data-index', String( i ) );
			dot.setAttribute( 'aria-label', sprintf( t( 'goToSlide', 'Go to slide %d' ), i + 1 ) );
			this.dots.appendChild( dot );
		}
	};

	Carousel.prototype.slideOffset = function ( index ) {
		var slide = this.slides[ Math.max( 0, Math.min( index, this.slides.length - 1 ) ) ];
		if ( ! slide ) {
			return 0;
		}
		var viewportBox = this.viewport.getBoundingClientRect();
		var slideBox = slide.getBoundingClientRect();
		var padding = parseFloat( window.getComputedStyle( this.viewport ).paddingLeft ) || 0;
		return slideBox.left - viewportBox.left + this.viewport.scrollLeft - padding;
	};

	Carousel.prototype.maxScroll = function () {
		return Math.max( 0, this.viewport.scrollWidth - this.viewport.clientWidth );
	};

	Carousel.prototype.go = function ( page, wrap ) {
		if ( this.pages <= 1 ) {
			return;
		}
		if ( page < 0 || page > this.pages - 1 ) {
			if ( ! ( wrap && ( this.loop || this.fromAutoplay ) ) ) {
				page = Math.max( 0, Math.min( page, this.pages - 1 ) );
			} else {
				page = ( page + this.pages ) % this.pages;
			}
		}

		this.page = page;

		if ( this.fade ) {
			this.applyFade();
		} else {
			var left = page >= this.pages - 1 ? this.maxScroll() : Math.min( this.slideOffset( page * this.step ), this.maxScroll() );
			this.viewport.scrollTo( { left: left, behavior: smoothBehavior() } );
		}
		this.render();
	};

	Carousel.prototype.syncFromScroll = function () {
		if ( this.fade || ! this.slides.length ) {
			return;
		}
		var left = this.viewport.scrollLeft;
		var max = this.maxScroll();
		if ( this.pages <= 1 ) {
			this.page = 0;
		} else if ( left >= max - 2 ) {
			this.page = this.pages - 1;
		} else {
			var slideWidth = this.slides[ 0 ].getBoundingClientRect().width;
			var gap = parseFloat( window.getComputedStyle( this.track ).columnGap ) || 0;
			var index = slideWidth ? left / ( slideWidth + gap ) : 0;
			this.page = Math.max( 0, Math.min( this.pages - 1, Math.round( index / this.step ) ) );
		}
		this.render();
	};

	Carousel.prototype.applyFade = function () {
		var page = this.page;
		this.slides.forEach( function ( slide, i ) {
			var active = i === page;
			slide.classList.toggle( 'is-active', active );
			if ( active ) {
				slide.removeAttribute( 'aria-hidden' );
				slide.removeAttribute( 'inert' );
			} else {
				slide.setAttribute( 'aria-hidden', 'true' );
				slide.setAttribute( 'inert', '' );
			}
		} );
	};

	Carousel.prototype.render = function () {
		var page = this.page;
		var pages = this.pages;
		var canWrap = this.loop;

		if ( this.counter ) {
			var text = ( page + 1 ) + ' / ' + pages;
			if ( this.counter.textContent !== text ) {
				this.counter.textContent = text;
			}
		}

		if ( this.prev ) {
			this.prev.setAttribute( 'aria-disabled', ! canWrap && page <= 0 ? 'true' : 'false' );
		}
		if ( this.next ) {
			this.next.setAttribute( 'aria-disabled', ! canWrap && page >= pages - 1 ? 'true' : 'false' );
		}

		if ( this.dots ) {
			each( this.dots.children, function ( dot, i ) {
				var active = i === page;
				dot.classList.toggle( 'is-active', active );
				if ( active ) {
					dot.setAttribute( 'aria-current', 'true' );
				} else {
					dot.removeAttribute( 'aria-current' );
				}
			} );
		}
	};

	Carousel.prototype.startAutoplay = function () {
		var self = this;
		this.stopAutoplay();
		if ( ! this.autoplay || this.pages <= 1 || reduceMotion.matches ) {
			return;
		}
		this.timer = window.setInterval( function () {
			if ( self.hovered || self.focused || ! self.inView || doc.hidden || drawer.current ) {
				return;
			}
			self.fromAutoplay = true;
			self.go( self.page + 1, true );
			self.fromAutoplay = false;
		}, Math.max( 2000, this.autoplay ) );
	};

	Carousel.prototype.stopAutoplay = function () {
		if ( this.timer ) {
			window.clearInterval( this.timer );
			this.timer = 0;
		}
	};

	Carousel.prototype.restartAutoplay = function () {
		if ( this.autoplay ) {
			this.startAutoplay();
		}
	};

	function initCarousels( root ) {
		queryAll( root, '[data-lh-carousel]' ).forEach( function ( el ) {
			if ( el.lihanCarousel ) {
				el.lihanCarousel.update();
				return;
			}
			el.lihanCarousel = new Carousel( el );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Read more
	 * ------------------------------------------------------------------ */
	function initReadmore( root ) {
		queryAll( root, '[data-lh-readmore]' ).forEach( function ( box ) {
			var content = box.querySelector( '.lh-readmore__content' );
			var toggle = box.querySelector( '.lh-readmore__toggle' );
			if ( ! content || ! toggle ) {
				return;
			}

			var lines = parseInt( box.getAttribute( 'data-lines' ), 10 );
			if ( lines > 0 ) {
				box.style.setProperty( '--lh-lines', String( lines ) );
			}

			var more = toggle.getAttribute( 'data-label-more' ) || t( 'readMore', 'Read More' );
			var less = toggle.getAttribute( 'data-label-less' ) || t( 'readLess', 'Read Less' );

			if ( ! content.id ) {
				content.id = 'lh-readmore-' + Math.random().toString( 36 ).slice( 2, 9 );
			}
			toggle.setAttribute( 'aria-controls', content.id );

			function measure() {
				if ( box.classList.contains( 'is-expanded' ) ) {
					return;
				}
				box.classList.remove( 'is-short' );
				var clamped = content.scrollHeight > content.clientHeight + 2;
				box.classList.toggle( 'is-short', ! clamped );
			}

			if ( box.lihanReadmore ) {
				measure();
				return;
			}
			box.lihanReadmore = true;

			toggle.addEventListener( 'click', function () {
				var expanded = ! box.classList.contains( 'is-expanded' );
				box.classList.toggle( 'is-expanded', expanded );
				toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
				var label = toggle.querySelector( '.lh-readmore__label' ) || toggle;
				label.textContent = expanded ? less : more;
				if ( ! expanded ) {
					var top = box.getBoundingClientRect().top;
					if ( top < 0 ) {
						box.scrollIntoView( { block: 'start', behavior: smoothBehavior() } );
					}
				}
			} );

			measure();
			if ( 'ResizeObserver' in window ) {
				var width = 0;
				new ResizeObserver( function ( entries ) {
					var w = Math.round( entries[ 0 ].contentRect.width );
					if ( w !== width ) {
						width = w;
						measure();
					}
				} ).observe( box );
			}
			if ( doc.fonts && doc.fonts.ready ) {
				doc.fonts.ready.then( measure );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Init
	 * ------------------------------------------------------------------ */
	function init( root ) {
		var scope = root && root.querySelectorAll ? root : doc;

		initDrawers();
		initSubmenuToggles();
		initFooterToggles();
		if ( scope === doc ) {
			initHeader();
		}
		initDropdowns( scope );
		initAnnouncements( scope );
		initCarousels( scope );
		initReadmore( scope );

		doc.dispatchEvent( new CustomEvent( 'lihan:init', { detail: { root: scope } } ) );
	}

	window.Lihan = window.Lihan || {};
	window.Lihan.init = init;
	window.Lihan.openDrawer = function ( id, opener ) {
		initDrawers();
		return drawer.open( String( id ), opener || null );
	};
	window.Lihan.closeDrawer = function () {
		drawer.close();
	};
	window.Lihan.isDrawerOpen = function ( id ) {
		return !! drawer.current && ( ! id || drawer.current.id === id );
	};

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', function () {
			init( doc );
		} );
	} else {
		init( doc );
	}
}() );
