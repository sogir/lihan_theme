/**
 * Lihan theme: WooCommerce behaviour (vanilla JS).
 *
 * jQuery is only used, when WooCommerce has loaded it, to listen to and trigger
 * WooCommerce's own jQuery events (added_to_cart, found_variation…).
 *
 * - Cart drawer: opens after AJAX add to cart.
 * - AJAX add to cart on single product forms (falls back to a normal submit).
 * - Variation button swatches kept in sync with the native selects.
 * - Quantity − / + buttons.
 * - Product gallery: mobile swipe slider dots.
 * - Card colour swatches swap the card image.
 * - Archive description clamp, size guide dialog, review link → accordion,
 *   sticky summary that never hides its bottom, WhatsApp link with the chosen options.
 */
( function () {
	'use strict';

	var settings = window.lihanWoo || {};
	var i18n = settings.i18n || {};

	function jq() {
		return typeof window.jQuery === 'function' ? window.jQuery : null;
	}

	function onReady( fn ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', fn );
		} else {
			fn();
		}
	}

	function fire( el, type ) {
		el.dispatchEvent( new Event( type, { bubbles: true } ) );
	}

	/* ------------------------------------------------------------------
	 * Cart drawer
	 * ---------------------------------------------------------------- */

	function openCartDrawer() {
		var drawer = document.getElementById( 'lh-cart-drawer' );
		if ( ! drawer || drawer.classList.contains( 'is-open' ) ) {
			return;
		}
		if ( window.Lihan && typeof window.Lihan.openDrawer === 'function' ) {
			window.Lihan.openDrawer( 'lh-cart-drawer' );
			return;
		}
		// Use the shared [data-lh-open] API through the drawer's own hidden trigger.
		var trigger = document.querySelector( '.lh-cart-drawer-trigger' );
		if ( trigger ) {
			trigger.click();
		}
	}

	/** Replace fragments without jQuery (used only when jQuery is missing). */
	function applyFragments( fragments ) {
		if ( ! fragments ) {
			return;
		}
		Object.keys( fragments ).forEach( function ( selector ) {
			var nodes;
			try {
				nodes = document.querySelectorAll( selector );
			} catch ( e ) {
				return;
			}
			nodes.forEach( function ( node ) {
				var tpl = document.createElement( 'template' );
				tpl.innerHTML = String( fragments[ selector ] ).trim();
				if ( tpl.content.firstElementChild ) {
					node.replaceWith( tpl.content.firstElementChild );
				}
			} );
		} );
	}

	function bindDrawerEvents() {
		var $ = jq();
		if ( ! $ ) {
			return;
		}
		$( document.body ).on( 'added_to_cart', function () {
			if ( settings.openDrawerOnAdd ) {
				openCartDrawer();
			}
		} );

		// Keep WooCommerce's notice scrolling clear of the sticky header.
		if ( typeof $.scroll_to_notices === 'function' ) {
			$.scroll_to_notices = function ( $el ) {
				if ( ! $el || ! $el.length ) {
					return;
				}
				var top = $el[ 0 ].getBoundingClientRect().top + window.pageYOffset - headerOffset() - 20;
				window.scrollTo( { top: Math.max( 0, top ), behavior: reducedMotion() ? 'auto' : 'smooth' } );
			};
		}
	}

	function reducedMotion() {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	function headerOffset() {
		// main.js keeps --lh-sticky-top equal to the sticky header's bottom edge (0 when not sticky).
		var value = parseFloat( window.getComputedStyle( document.documentElement ).getPropertyValue( '--lh-sticky-top' ) );
		return isNaN( value ) ? 0 : value;
	}

	/* ------------------------------------------------------------------
	 * AJAX add to cart (single product)
	 * ---------------------------------------------------------------- */

	function showFormNotices( form, html ) {
		var product = form.closest( '.product' ) || document;
		var wrapper = document.querySelector( '.woocommerce-notices-wrapper' );
		var container = document.createElement( 'div' );
		container.className = 'lh-form-notices';
		container.innerHTML = html;
		var old = product.querySelector( '.lh-form-notices' );
		if ( old ) {
			old.remove();
		}
		if ( wrapper && ! product.contains( wrapper ) && wrapper.closest( '.lh-shop' ) ) {
			wrapper.innerHTML = html;
			container = wrapper;
		} else {
			form.parentNode.insertBefore( container, form );
		}
		var $ = jq();
		if ( $ && typeof $.scroll_to_notices === 'function' ) {
			$.scroll_to_notices( $( container ) );
		} else {
			container.scrollIntoView( { block: 'center' } );
		}
	}

	function buildFormData( form, submitter ) {
		var data;
		try {
			data = submitter ? new FormData( form, submitter ) : new FormData( form );
		} catch ( e ) {
			data = new FormData( form );
			if ( submitter && submitter.name && ! data.has( submitter.name ) ) {
				data.append( submitter.name, submitter.value );
			}
		}
		return data;
	}

	function onSingleSubmit( event ) {
		var form = event.target;
		if ( ! ( form instanceof HTMLFormElement ) || ! form.matches( 'form.cart' ) || ! form.closest( '.lh-product-layout' ) ) {
			return;
		}
		if ( ! settings.ajaxSingle || ! settings.ajaxAddUrl || ! window.fetch || form.method.toLowerCase() !== 'post' ) {
			return;
		}
		var submitter = event.submitter || null;
		if ( submitter && submitter.hasAttribute( 'data-lh-buy-now' ) ) {
			return;
		}
		var button = submitter || form.querySelector( '.single_add_to_cart_button' );
		if ( button && button.classList.contains( 'disabled' ) ) {
			return;
		}
		var data = buildFormData( form, submitter );
		if ( ! data.has( 'add-to-cart' ) ) {
			if ( button && button.name === 'add-to-cart' ) {
				data.append( 'add-to-cart', button.value );
			} else {
				return; // Not a standard add-to-cart form: let it submit normally.
			}
		}

		event.preventDefault();

		if ( button ) {
			button.classList.add( 'loading' );
			button.setAttribute( 'aria-busy', 'true' );
			button.disabled = true;
		}
		var done = function () {
			if ( button ) {
				button.classList.remove( 'loading' );
				button.removeAttribute( 'aria-busy' );
				button.disabled = false;
			}
		};

		var old = ( form.closest( '.product' ) || document ).querySelector( '.lh-form-notices' );
		if ( old ) {
			old.remove();
		}

		window.fetch( settings.ajaxAddUrl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin',
			headers: { Accept: 'application/json' },
		} )
			.then( function ( response ) {
				return response.text().then( function ( text ) {
					var json = null;
					try {
						json = JSON.parse( text );
					} catch ( e ) {
						json = null;
					}
					return json;
				} );
			} )
			.then( function ( json ) {
				done();
				var $ = jq();
				if ( ! json ) {
					// Unknown response: the item may have been added. Show the real cart state.
					if ( $ ) {
						$( document.body ).trigger( 'wc_fragment_refresh' );
					}
					if ( settings.openDrawerOnAdd ) {
						openCartDrawer();
					} else if ( settings.cartUrl ) {
						window.location.href = settings.cartUrl;
					}
					return;
				}
				if ( json.redirect ) {
					window.location.href = json.redirect;
					return;
				}
				if ( json.error ) {
					showFormNotices( form, json.notices || '<ul class="woocommerce-error" role="alert"><li>' + ( i18n.addError || '' ) + '</li></ul>' );
					return;
				}
				if ( $ ) {
					$( document.body ).trigger( 'added_to_cart', [ json.fragments, json.cart_hash, button ? $( button ) : null ] );
				} else {
					applyFragments( json.fragments );
					if ( settings.openDrawerOnAdd ) {
						openCartDrawer();
					}
				}
				document.dispatchEvent( new CustomEvent( 'lihan:added_to_cart', { detail: { form: form, response: json } } ) );
			} )
			.catch( function () {
				// Network failure before the request was handled: fall back to a normal submit.
				done();
				if ( submitter && submitter.name ) {
					var hidden = document.createElement( 'input' );
					hidden.type = 'hidden';
					hidden.name = submitter.name;
					hidden.value = submitter.value;
					form.appendChild( hidden );
				}
				HTMLFormElement.prototype.submit.call( form );
			} );
	}

	/** "Buy now" on a variable product without a complete selection: let WooCommerce explain. */
	function onBuyNowClick( event ) {
		var buyNow = event.target.closest( '[data-lh-buy-now]' );
		if ( ! buyNow ) {
			return;
		}
		var form = buyNow.form;
		var addButton = form ? form.querySelector( '.single_add_to_cart_button' ) : null;
		if ( addButton && addButton.classList.contains( 'disabled' ) ) {
			event.preventDefault();
			addButton.click();
		}
	}

	/* ------------------------------------------------------------------
	 * Variation swatches
	 * ---------------------------------------------------------------- */

	function initSwatches( root ) {
		( root || document ).querySelectorAll( 'form.variations_form' ).forEach( function ( form ) {
			if ( form.dataset.lhSwatches ) {
				return;
			}
			var groups = form.querySelectorAll( '[data-lh-swatches]' );
			if ( ! groups.length ) {
				return;
			}
			form.dataset.lhSwatches = '1';

			var variations = null;
			try {
				variations = JSON.parse( form.getAttribute( 'data-product_variations' ) || 'false' );
			} catch ( e ) {
				variations = null;
			}

			function selectFor( group ) {
				return form.querySelector( 'select[name="' + CSS.escape( group.getAttribute( 'data-lh-swatches' ) ) + '"]' ) ||
					form.querySelector( 'select[data-attribute_name="' + CSS.escape( group.getAttribute( 'data-lh-swatches' ) ) + '"]' );
			}

			function chosen() {
				var out = {};
				form.querySelectorAll( '.variations select' ).forEach( function ( select ) {
					out[ select.getAttribute( 'data-attribute_name' ) || select.name ] = select.value;
				} );
				return out;
			}

			/** Availability of a value given the other chosen attributes: 'ok' | 'oos' | 'none'. */
			function availability( attr, value, current ) {
				if ( ! Array.isArray( variations ) ) {
					return 'ok';
				}
				var found = false;
				for ( var i = 0; i < variations.length; i++ ) {
					var attrs = variations[ i ].attributes || {};
					var match = true;
					for ( var name in current ) {
						if ( ! Object.prototype.hasOwnProperty.call( current, name ) ) {
							continue;
						}
						var want = name === attr ? value : current[ name ];
						var have = attrs[ name ];
						if ( want && have && have !== want ) {
							match = false;
							break;
						}
					}
					if ( match ) {
						found = true;
						if ( variations[ i ].is_in_stock !== false && variations[ i ].variation_is_active !== false ) {
							return 'ok';
						}
					}
				}
				return found ? 'oos' : 'none';
			}

			function refresh() {
				var current = chosen();
				groups.forEach( function ( group ) {
					var select = selectFor( group );
					if ( ! select ) {
						return;
					}
					var attr = select.getAttribute( 'data-attribute_name' ) || select.name;
					var labelText = '';
					group.querySelectorAll( '.lh-swatch' ).forEach( function ( button ) {
						var value = button.getAttribute( 'data-value' );
						var isSelected = select.value !== '' && select.value === value;
						var state = availability( attr, value, current );
						button.setAttribute( 'aria-pressed', isSelected ? 'true' : 'false' );
						button.classList.toggle( 'is-selected', isSelected );
						button.classList.toggle( 'is-oos', state === 'oos' );
						button.classList.toggle( 'is-unavailable', state === 'none' );
						var note = state === 'oos' ? i18n.outOfStock : ( state === 'none' ? i18n.unavailable : '' );
						var base = button.getAttribute( 'title' ) || button.textContent.trim();
						if ( ! button.dataset.lhLabel ) {
							button.dataset.lhLabel = base;
						}
						button.setAttribute( 'aria-label', button.dataset.lhLabel + ( note ? ' (' + note + ')' : '' ) );
						if ( isSelected ) {
							labelText = button.dataset.lhLabel;
						}
					} );
					// Show the chosen value next to the attribute label.
					var row = group.closest( 'tr' );
					var label = row ? row.querySelector( 'th label, .label label' ) : null;
					if ( label ) {
						var span = label.parentNode.querySelector( '.lh-swatch-value' );
						if ( ! span ) {
							span = document.createElement( 'span' );
							span.className = 'lh-swatch-value';
							label.parentNode.appendChild( span );
						}
						span.textContent = labelText ? labelText : '';
					}
				} );
			}

			form.addEventListener( 'click', function ( event ) {
				var button = event.target.closest( '.lh-swatch' );
				if ( ! button || ! form.contains( button ) ) {
					return;
				}
				event.preventDefault();
				var group = button.closest( '[data-lh-swatches]' );
				var select = selectFor( group );
				if ( ! select ) {
					return;
				}
				var value = button.getAttribute( 'data-value' );
				if ( select.value === value ) {
					select.value = '';
					fire( select, 'change' );
					refresh();
					return;
				}
				var hasOption = Array.prototype.some.call( select.options, function ( option ) {
					return option.value === value && ! option.disabled;
				} );
				if ( ! hasOption ) {
					// Not combinable with the current choice: clear the other attributes first.
					form.querySelectorAll( '.variations select' ).forEach( function ( other ) {
						if ( other !== select && other.value !== '' ) {
							other.value = '';
							fire( other, 'change' );
						}
					} );
				}
				var exists = Array.prototype.some.call( select.options, function ( option ) {
					return option.value === value;
				} );
				if ( ! exists ) {
					var option = document.createElement( 'option' );
					option.value = value;
					option.textContent = button.dataset.lhLabel || value;
					select.appendChild( option );
				}
				select.value = value;
				fire( select, 'change' );
				refresh();
			} );

			form.addEventListener( 'change', function ( event ) {
				if ( event.target.matches( '.variations select' ) ) {
					window.requestAnimationFrame( refresh );
				}
			} );

			var $ = jq();
			if ( $ ) {
				$( form ).on( 'woocommerce_update_variation_values reset_data found_variation', function () {
					window.requestAnimationFrame( refresh );
				} );
			}
			refresh();
		} );
	}

	/* ------------------------------------------------------------------
	 * WhatsApp link with the chosen options
	 * ---------------------------------------------------------------- */

	function initWhatsApp() {
		var link = document.querySelector( '[data-lh-wa-base]' );
		var form = document.querySelector( '.lh-product-layout form.variations_form' );
		if ( ! link || ! form ) {
			return;
		}
		var update = function () {
			var parts = [];
			form.querySelectorAll( '.variations select' ).forEach( function ( select ) {
				if ( select.value ) {
					var row = select.closest( 'tr' );
					var label = row ? row.querySelector( 'th label' ) : null;
					var option = select.options[ select.selectedIndex ];
					parts.push( ( label ? label.textContent.trim() + ': ' : '' ) + ( option ? option.textContent.trim() : select.value ) );
				}
			} );
			var text = link.getAttribute( 'data-lh-wa-text' ) + ( parts.length ? '\n' + parts.join( ', ' ) : '' );
			link.href = link.getAttribute( 'data-lh-wa-base' ) + '?text=' + encodeURIComponent( text );
		};
		form.addEventListener( 'change', update );
		var $ = jq();
		if ( $ ) {
			$( form ).on( 'found_variation reset_data', update );
		}
	}

	/* ------------------------------------------------------------------
	 * Quantity buttons
	 * ---------------------------------------------------------------- */

	function onQtyClick( event ) {
		var button = event.target.closest( '[data-lh-qty]' );
		if ( ! button ) {
			return;
		}
		var input = button.parentNode.querySelector( 'input.qty' );
		if ( ! input || input.readOnly || input.disabled ) {
			return;
		}
		event.preventDefault();
		var step = parseFloat( input.step ) || 1;
		var min = input.min !== '' ? parseFloat( input.min ) : 0;
		var max = input.max !== '' ? parseFloat( input.max ) : Infinity;
		var value = parseFloat( input.value );
		if ( isNaN( value ) ) {
			value = min || 0;
		}
		value += parseInt( button.getAttribute( 'data-lh-qty' ), 10 ) * step;
		value = Math.min( max, Math.max( min, value ) );
		var decimals = ( String( step ).split( '.' )[ 1 ] || '' ).length;
		var next = value.toFixed( decimals );
		if ( next !== input.value ) {
			input.value = next;
			fire( input, 'input' );
			fire( input, 'change' );
		}
	}

	/* ------------------------------------------------------------------
	 * Product gallery: mobile slider dots
	 * ---------------------------------------------------------------- */

	function initGallery( root ) {
		( root || document ).querySelectorAll( '.lh-gallery' ).forEach( function ( gallery ) {
			var wrapper = gallery.querySelector( '.woocommerce-product-gallery__wrapper' );
			if ( ! wrapper || gallery.querySelector( '.lh-gallery__dots' ) ) {
				return;
			}
			var slides = wrapper.querySelectorAll( '.woocommerce-product-gallery__image' );
			if ( slides.length < 2 ) {
				return;
			}
			var dots = document.createElement( 'div' );
			dots.className = 'lh-gallery__dots';
			slides.forEach( function ( slide, index ) {
				var dot = document.createElement( 'button' );
				dot.type = 'button';
				dot.className = 'lh-gallery__dot' + ( index === 0 ? ' is-active' : '' );
				dot.setAttribute( 'aria-label', ( i18n.goToImage || '%d' ).replace( '%d', index + 1 ) );
				dot.addEventListener( 'click', function () {
					wrapper.scrollTo( { left: slide.offsetLeft - wrapper.offsetLeft, behavior: reducedMotion() ? 'auto' : 'smooth' } );
				} );
				dots.appendChild( dot );
			} );
			gallery.appendChild( dots );

			var ticking = false;
			wrapper.addEventListener( 'scroll', function () {
				if ( ticking ) {
					return;
				}
				ticking = true;
				window.requestAnimationFrame( function () {
					ticking = false;
					var index = Math.round( wrapper.scrollLeft / Math.max( 1, wrapper.clientWidth ) );
					dots.querySelectorAll( '.lh-gallery__dot' ).forEach( function ( dot, i ) {
						dot.classList.toggle( 'is-active', i === index );
						dot.setAttribute( 'aria-current', i === index ? 'true' : 'false' );
					} );
				} );
			}, { passive: true } );
		} );
	}

	function bindGalleryEvents() {
		var $ = jq();
		if ( ! $ ) {
			return;
		}
		// Variation galleries replace the markup; variation images reset to the first slide.
		$( document.body ).on( 'wc-product-gallery-after-init', '.woocommerce-product-gallery', function () {
			initGallery( this.parentNode );
		} );
		$( document.body ).on( 'woocommerce_gallery_reset_slide_position', '.woocommerce-product-gallery', function () {
			var wrapper = this.querySelector( '.woocommerce-product-gallery__wrapper' );
			if ( wrapper ) {
				wrapper.scrollTo( { left: 0 } );
			}
		} );
	}

	/* ------------------------------------------------------------------
	 * Card colour swatches swap the card image
	 * ---------------------------------------------------------------- */

	function onCardSwatch( event ) {
		var dot = event.target.closest( '[data-lh-swatch-src]' );
		if ( ! dot ) {
			return;
		}
		var card = dot.closest( '.lh-product-card, li.product' );
		var img = card ? card.querySelector( '.lh-product-card__img--main' ) : null;
		var src = dot.getAttribute( 'data-lh-swatch-src' );
		if ( ! img || ! src ) {
			return;
		}
		if ( ! img.dataset.lhOrigSrc ) {
			img.dataset.lhOrigSrc = img.getAttribute( 'src' ) || '';
			img.dataset.lhOrigSrcset = img.getAttribute( 'srcset' ) || '';
		}
		img.setAttribute( 'src', src );
		var srcset = dot.getAttribute( 'data-lh-swatch-srcset' );
		if ( srcset ) {
			img.setAttribute( 'srcset', srcset );
		} else {
			img.removeAttribute( 'srcset' );
		}
		card.classList.add( 'is-swatch-preview' );
		card.querySelectorAll( '[data-lh-swatch-src]' ).forEach( function ( other ) {
			other.classList.toggle( 'is-selected', other === dot );
		} );
	}

	/* ------------------------------------------------------------------
	 * Archive description clamp
	 * ---------------------------------------------------------------- */

	function initClamp() {
		document.querySelectorAll( '[data-lh-clamp]' ).forEach( function ( box ) {
			var content = box.querySelector( '.lh-clamp__content' );
			var toggle = box.querySelector( '.lh-clamp__toggle' );
			if ( ! content || ! toggle ) {
				return;
			}
			var measure = function () {
				if ( box.classList.contains( 'is-expanded' ) ) {
					return;
				}
				toggle.hidden = content.scrollHeight <= content.clientHeight + 2;
			};
			measure();
			window.addEventListener( 'resize', measure, { passive: true } );
			toggle.addEventListener( 'click', function () {
				var open = ! box.classList.contains( 'is-expanded' );
				box.classList.toggle( 'is-expanded', open );
				toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				var label = toggle.querySelector( '.lh-visually-hidden' );
				if ( label ) {
					label.textContent = open ? i18n.readLess : i18n.readMore;
				}
			} );
		} );
	}

	/* ------------------------------------------------------------------
	 * Size guide dialog
	 * ---------------------------------------------------------------- */

	function onDialogClick( event ) {
		var opener = event.target.closest( '[data-lh-dialog]' );
		if ( opener ) {
			var dialog = document.getElementById( opener.getAttribute( 'data-lh-dialog' ) );
			if ( dialog && typeof dialog.showModal === 'function' ) {
				event.preventDefault();
				dialog.showModal();
				document.documentElement.classList.add( 'lh-modal-open' );
			}
			return;
		}
		var closer = event.target.closest( '[data-lh-dialog-close]' );
		if ( closer ) {
			var parent = closer.closest( 'dialog' );
			if ( parent ) {
				parent.close();
			}
			return;
		}
		// Click on the backdrop closes the dialog.
		if ( event.target instanceof HTMLDialogElement && event.target.classList.contains( 'lh-modal' ) ) {
			var rect = event.target.getBoundingClientRect();
			var inside = event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
			if ( ! inside ) {
				event.target.close();
			}
		}
	}

	function bindDialogs() {
		document.querySelectorAll( 'dialog.lh-modal' ).forEach( function ( dialog ) {
			dialog.addEventListener( 'close', function () {
				document.documentElement.classList.remove( 'lh-modal-open' );
			} );
		} );
	}

	/* ------------------------------------------------------------------
	 * Reviews link / hash → open the matching accordion
	 * ---------------------------------------------------------------- */

	function openAccordionFor( hash ) {
		if ( ! hash || hash.length < 2 ) {
			return false;
		}
		var target;
		try {
			target = document.querySelector( hash );
		} catch ( e ) {
			return false;
		}
		if ( ! target ) {
			return false;
		}
		var details = target.closest( 'details' ) || ( target.tagName === 'DETAILS' ? target : null );
		if ( ! details ) {
			return false;
		}
		details.open = true;
		var top = details.getBoundingClientRect().top + window.pageYOffset - headerOffset() - 20;
		window.scrollTo( { top: Math.max( 0, top ), behavior: reducedMotion() ? 'auto' : 'smooth' } );
		return true;
	}

	function onHashLink( event ) {
		var link = event.target.closest( 'a[href*="#"]' );
		if ( ! link || link.pathname !== window.location.pathname ) {
			return;
		}
		var hash = link.hash === '#reviews' || link.classList.contains( 'woocommerce-review-link' ) ? '#lh-acc-reviews' : link.hash;
		if ( hash && document.querySelector( '.lh-product-accordions' ) && openAccordionFor( hash ) ) {
			event.preventDefault();
		}
	}

	/* ------------------------------------------------------------------
	 * Sticky summary: if it is taller than the viewport, stick by its bottom edge
	 * ---------------------------------------------------------------- */

	function initStickySummary() {
		var summary = document.querySelector( '.lh-product-layout > .summary' );
		if ( ! summary || ! window.ResizeObserver ) {
			return;
		}
		var update = function () {
			if ( window.innerWidth < 1024 ) {
				summary.style.removeProperty( '--lh-summary-top' );
				return;
			}
			var base = headerOffset() + 20;
			var height = summary.offsetHeight;
			var room = window.innerHeight;
			summary.style.setProperty( '--lh-summary-top', ( height + base > room ? room - height - 20 : base ) + 'px' );
		};
		new window.ResizeObserver( update ).observe( summary );
		window.addEventListener( 'resize', update, { passive: true } );
		update();
	}

	/* ------------------------------------------------------------------
	 * Cart / Checkout blocks: keep the header badge in sync with the Store API cart
	 * ---------------------------------------------------------------- */

	function syncBlockCart() {
		var wp = window.wp;
		if ( ! wp || ! wp.data || typeof wp.data.subscribe !== 'function' || typeof wp.data.select !== 'function' ) {
			return;
		}
		var last = null;
		wp.data.subscribe( function () {
			var store;
			try {
				store = wp.data.select( 'wc/store/cart' );
			} catch ( e ) {
				return;
			}
			if ( ! store || typeof store.getCartData !== 'function' ) {
				return;
			}
			var data = store.getCartData();
			var count = data && typeof data.itemsCount === 'number' ? data.itemsCount : null;
			if ( count === null || count === last ) {
				return;
			}
			var first = last === null;
			last = count;
			document.querySelectorAll( '.lh-cart-count' ).forEach( function ( badge ) {
				var num = badge.querySelector( '.lh-cart-count__num' );
				if ( num ) {
					num.textContent = String( count );
				}
				badge.setAttribute( 'data-count', String( count ) );
				badge.classList.toggle( 'is-empty', count === 0 );
			} );
			var $ = jq();
			if ( ! first && $ ) {
				$( document.body ).trigger( 'wc_fragment_refresh' );
			}
		} );
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ---------------------------------------------------------------- */

	document.addEventListener( 'submit', onSingleSubmit );
	document.addEventListener( 'click', function ( event ) {
		onQtyClick( event );
		onBuyNowClick( event );
		onDialogClick( event );
		onHashLink( event );
	} );
	document.addEventListener( 'click', onCardSwatch );
	document.addEventListener( 'mouseover', function ( event ) {
		if ( window.matchMedia && window.matchMedia( '(hover: hover)' ).matches ) {
			onCardSwatch( event );
		}
	} );

	onReady( function () {
		bindDrawerEvents();
		bindGalleryEvents();
		initSwatches( document );
		initWhatsApp();
		initGallery( document );
		initClamp();
		bindDialogs();
		initStickySummary();
		syncBlockCart();

		if ( window.location.hash && /^#(reviews|comment-\d+|lh-acc-[\w-]+)$/.test( window.location.hash ) ) {
			openAccordionFor( window.location.hash === '#reviews' ? '#lh-acc-reviews' : window.location.hash );
		}

		var $ = jq();
		if ( $ ) {
			// Variation forms rendered later (quick views, page builders).
			$( document.body ).on( 'wc_variation_form', 'form.variations_form', function () {
				initSwatches( this.parentNode );
			} );
		}
	} );

	document.addEventListener( 'lihan:init', function ( event ) {
		var root = event.detail && event.detail.root ? event.detail.root : document;
		initSwatches( root );
		initGallery( root );
	} );
}() );

/*
 * Keep WooCommerce notices clear of the sticky header: WooCommerce scrolls to notices with a
 * fixed 100px offset, which leaves them under our taller header. Registered after
 * WooCommerce's own ready handler, so this definition wins.
 */
( function () {
	'use strict';
	if ( ! window.jQuery ) {
		return;
	}
	window.jQuery( function ( $ ) {
		$.scroll_to_notices = function ( scrollElement ) {
			if ( ! scrollElement || ! scrollElement.length ) {
				return;
			}
			var styles = window.getComputedStyle( document.documentElement );
			var offset = parseFloat( styles.getPropertyValue( '--lh-sticky-top' ) ) || 0;
			var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			$( 'html, body' ).stop().animate(
				{ scrollTop: Math.max( 0, scrollElement.offset().top - offset - 20 ) },
				reduce ? 0 : 600
			);
		};
	} );
}() );
