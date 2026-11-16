/**
 * Lihan homepage enhancements (vanilla, no dependencies).
 *
 * Inspiration reels ([data-lh-reels], built on the shared carousel):
 * - marks the card nearest the viewport centre with .is-center (CSS scales it);
 * - plays a reel video on hover / keyboard focus, and on touch screens plays
 *   the centred one while the carousel is on screen.
 * Respects prefers-reduced-motion (no autoplaying video).
 * Re-runs on the shared `lihan:init` event (AJAX / Elementor preview content).
 */
( function () {
	'use strict';

	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var canHover = window.matchMedia( '(hover: hover) and (pointer: fine)' );

	function play( slide ) {
		var video = slide && slide.querySelector( 'video' );
		if ( ! video || reduceMotion.matches ) {
			return;
		}
		var promise = video.play();
		if ( promise && typeof promise.then === 'function' ) {
			promise.then( function () {
				slide.classList.add( 'is-playing' );
			} ).catch( function () {} );
		} else {
			slide.classList.add( 'is-playing' );
		}
	}

	function pause( slide ) {
		var video = slide && slide.querySelector( 'video' );
		if ( ! video ) {
			return;
		}
		video.pause();
		slide.classList.remove( 'is-playing' );
	}

	function initReels( carousel ) {
		if ( carousel.lihanReels ) {
			return;
		}
		var viewport = carousel.querySelector( '.lh-carousel__viewport' );
		var slides = Array.prototype.slice.call( carousel.querySelectorAll( '.lh-reel' ) );
		if ( ! viewport || ! slides.length ) {
			return;
		}
		carousel.lihanReels = true;

		var frame = 0;
		var visible = false;
		var center = null;

		function syncTouchVideos() {
			if ( canHover.matches ) {
				return;
			}
			slides.forEach( function ( slide ) {
				if ( visible && slide === center ) {
					play( slide );
				} else {
					pause( slide );
				}
			} );
		}

		function update() {
			frame = 0;
			var box = viewport.getBoundingClientRect();
			if ( ! box.width ) {
				return;
			}
			var mid = box.left + box.width / 2;
			var best = null;
			var bestDistance = Infinity;
			slides.forEach( function ( slide ) {
				var rect = slide.getBoundingClientRect();
				var distance = Math.abs( rect.left + rect.width / 2 - mid );
				if ( distance < bestDistance ) {
					bestDistance = distance;
					best = slide;
				}
			} );
			if ( best !== center ) {
				slides.forEach( function ( slide ) {
					slide.classList.toggle( 'is-center', slide === best );
				} );
				center = best;
			}
			syncTouchVideos();
		}

		function schedule() {
			if ( ! frame ) {
				frame = window.requestAnimationFrame( update );
			}
		}

		viewport.addEventListener( 'scroll', schedule, { passive: true } );
		window.addEventListener( 'resize', schedule );
		if ( 'onscrollend' in window ) {
			viewport.addEventListener( 'scrollend', schedule );
		}

		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( entries ) {
				visible = entries[ 0 ].isIntersecting;
				syncTouchVideos();
			}, { threshold: 0.35 } ).observe( carousel );
		}

		slides.forEach( function ( slide ) {
			if ( ! slide.querySelector( 'video' ) ) {
				return;
			}
			slide.addEventListener( 'mouseenter', function () {
				if ( canHover.matches ) {
					play( slide );
				}
			} );
			slide.addEventListener( 'mouseleave', function () {
				if ( canHover.matches ) {
					pause( slide );
				}
			} );
			slide.addEventListener( 'focusin', function () {
				play( slide );
			} );
			slide.addEventListener( 'focusout', function ( event ) {
				if ( ! slide.contains( event.relatedTarget ) ) {
					pause( slide );
				}
			} );
		} );

		reduceMotion.addEventListener( 'change', function () {
			if ( reduceMotion.matches ) {
				slides.forEach( pause );
			}
		} );

		update();
	}

	function init( root ) {
		var scope = root && root.querySelectorAll ? root : document;
		var list = scope.querySelectorAll( '[data-lh-reels]' );
		Array.prototype.forEach.call( list, initReels );
		if ( scope !== document && scope.matches && scope.matches( '[data-lh-reels]' ) ) {
			initReels( scope );
		}
	}

	document.addEventListener( 'lihan:init', function ( event ) {
		init( event.detail && event.detail.root );
	} );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			init( document );
		} );
	} else {
		init( document );
	}
}() );
