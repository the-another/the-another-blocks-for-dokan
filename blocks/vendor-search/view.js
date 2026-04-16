/**
 * Vendor search block frontend script.
 *
 * Handles filter form toggle and sort-by auto-submit.
 * Loaded in the footer via wp_enqueue_script — DOM is already available.
 *
 * @package AnotherBlocksForDokan
 */

( function () {
	var filterButton = document.querySelector( '.tanbfd--vendor-query-loop-filter-button' );
	var filterForm = document.getElementById( 'tanbfd--vendor-query-looping-filter-form-wrap' );
	var cancelButton = document.getElementById( 'cancel-filter-btn' );
	var sortSelect = document.getElementById( 'stores_orderby' );

	function toggleFilterForm() {
		if ( filterButton && filterForm ) {
			var isHidden = filterForm.classList.contains( 'tanbfd--hidden' );
			filterButton.setAttribute( 'aria-expanded', isHidden ? 'true' : 'false' );
			filterForm.classList.toggle( 'tanbfd--hidden' );
		}
	}

	if ( filterButton && filterForm ) {
		var isInitiallyVisible = ! filterForm.classList.contains( 'tanbfd--hidden' );
		filterButton.setAttribute( 'aria-expanded', isInitiallyVisible ? 'true' : 'false' );

		filterButton.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			toggleFilterForm();
		} );

		if ( cancelButton ) {
			cancelButton.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				toggleFilterForm();
			} );
		}
	}

	if ( sortSelect ) {
		sortSelect.addEventListener( 'change', function () {
			if ( this.form ) {
				this.form.submit();
			}
		} );
	}
} )();
