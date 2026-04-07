/**
 * Vendor Query Loop infinite scroll loader.
 *
 * Watches a sentinel element and appends additional pages of <li> markup
 * fetched from the REST endpoint. Never modifies window.history — the URL
 * stays unchanged across loads.
 */
( function () {
	'use strict';

	function init( wrapper ) {
		var queryId = wrapper.getAttribute( 'data-query-id' );
		var postId = parseInt( wrapper.getAttribute( 'data-post-id' ), 10 ) || 0;
		var totalPages = parseInt(
			wrapper.getAttribute( 'data-total-pages' ),
			10
		);
		var current =
			parseInt( wrapper.getAttribute( 'data-current-page' ), 10 ) || 1;
		var scrollOffset = parseInt(
			wrapper.getAttribute( 'data-scroll-offset' ),
			10
		);
		if ( isNaN( scrollOffset ) || scrollOffset < 0 ) {
			scrollOffset = 400;
		}
		var attrsRaw = wrapper.getAttribute( 'data-attributes' );
		var filtersRaw = wrapper.getAttribute( 'data-filters' );
		var attributes;
		var filters;
		try {
			attributes = JSON.parse( attrsRaw || '{}' );
			filters = JSON.parse( filtersRaw || '{}' );
		} catch ( e ) {
			return;
		}

		var list = wrapper.querySelector( 'ul.theabd--vendor-wrap' );
		var sentinel = wrapper.querySelector(
			'.theabd--vendor-query-loop-sentinel'
		);
		var status = wrapper.querySelector(
			'.theabd--vendor-query-loop-status'
		);

		if ( ! list || ! sentinel || ! totalPages || current >= totalPages ) {
			return;
		}

		var loading = false;
		var observer;

		function fetchNext() {
			if ( loading || current >= totalPages ) {
				return;
			}
			loading = true;
			if ( status ) {
				status.textContent = 'Loading';
			}
			var nextPage = current + 1;

			var apiFetch = window.wp && window.wp.apiFetch;
			var request;

			var payload = {
				queryId: queryId,
				postId: postId,
				page: nextPage,
				attributes: attributes,
				filters: filters,
			};

			if ( apiFetch ) {
				request = apiFetch( {
					path: '/another-blocks-for-dokan/v1/vendor-query-loop',
					method: 'POST',
					data: payload,
				} );
			} else {
				request = fetch(
					'/wp-json/another-blocks-for-dokan/v1/vendor-query-loop',
					{
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify( payload ),
					}
				).then( function ( r ) {
					return r.json();
				} );
			}

			request
				.then( function ( res ) {
					if ( res && res.items ) {
						list.insertAdjacentHTML( 'beforeend', res.items );
					}
					current = res && res.page ? res.page : nextPage;
					wrapper.setAttribute(
						'data-current-page',
						String( current )
					);
					if ( res && res.hasMore === false ) {
						observer.disconnect();
					}
				} )
				.catch( function () {} )
				.finally( function () {
					loading = false;
					if ( status ) {
						status.textContent = '';
					}
				} );
		}

		observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						fetchNext();
					}
				} );
			},
			{ rootMargin: scrollOffset + 'px 0px' }
		);
		observer.observe( sentinel );
	}

	function boot() {
		var wrappers = document.querySelectorAll(
			'.theabd--vendor-query-loop[data-infinite="1"]'
		);
		Array.prototype.forEach.call( wrappers, init );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
