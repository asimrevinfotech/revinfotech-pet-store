( function () {
	'use strict';

	function paginate( wrap ) {
		var perPage = parseInt( wrap.getAttribute( 'data-rows-per-page' ), 10 ) || 10;
		var rows = Array.prototype.slice.call( wrap.querySelectorAll( 'tbody tr' ) );
		var prevBtn = wrap.querySelector( '.rps-prev' );
		var nextBtn = wrap.querySelector( '.rps-next' );
		var indicator = wrap.querySelector( '.rps-page-indicator' );
		var totalPages = Math.max( 1, Math.ceil( rows.length / perPage ) );
		var currentPage = 1;

		if ( rows.length <= perPage ) {
			if ( wrap.querySelector( '.rps-pet-table-pagination' ) ) {
				wrap.querySelector( '.rps-pet-table-pagination' ).style.display = 'none';
			}
		}

		function render() {
			rows.forEach( function ( row, index ) {
				var start = ( currentPage - 1 ) * perPage;
				var end = start + perPage;
				row.style.display = ( index >= start && index < end ) ? '' : 'none';
			} );

			if ( indicator ) {
				indicator.textContent = currentPage + ' / ' + totalPages;
			}

			if ( prevBtn ) {
				prevBtn.disabled = currentPage <= 1;
			}

			if ( nextBtn ) {
				nextBtn.disabled = currentPage >= totalPages;
			}
		}

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				if ( currentPage > 1 ) {
					currentPage -= 1;
					render();
				}
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				if ( currentPage < totalPages ) {
					currentPage += 1;
					render();
				}
			} );
		}

		render();
	}

	function hideBrokenImages( wrap ) {
		var images = wrap.querySelectorAll( '.rps-pet-image' );
		images.forEach( function ( img ) {
			function hide() {
				img.style.display = 'none';
			}

			if ( img.complete && 0 === img.naturalWidth ) {
				hide();
			} else {
				img.addEventListener( 'error', hide );
			}
		} );
	}

	function init() {
		var wraps = document.querySelectorAll( '.rps-pet-table-wrap' );
		wraps.forEach( function ( wrap ) {
			hideBrokenImages( wrap );
			paginate( wrap );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
