/* global HandballProspectsEditor, jQuery */
/**
 * Classic Editor-rutan: sök → välj → klart. Valen hålls som JSON i det
 * dolda fältet och sparas med inlägget. En nyvald spelare läggs också i
 * taggrutan (samma väg som skribentens egen "Lägg till").
 */
( function () {
	'use strict';

	const config = window.HandballProspectsEditor;
	const box = document.querySelector( '[data-hp-box]' );
	if ( ! config || ! box ) {
		return;
	}

	const field = box.querySelector( '[data-hp-field]' );
	const search = box.querySelector( '[data-hp-search]' );
	const results = box.querySelector( '[data-hp-results]' );
	const status = box.querySelector( '[data-hp-status]' );
	const selectedList = box.querySelector( '[data-hp-selected]' );

	let selected = parse( field.value );
	let found = [];
	let active = -1;
	let timer = null;
	let controller = null;

	function parse( json ) {
		try {
			const value = JSON.parse( json || '[]' );
			return Array.isArray( value ) ? value : [];
		} catch ( e ) {
			return [];
		}
	}

	function el( tag, className, text ) {
		const node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( text !== undefined ) {
			node.textContent = text;
		}
		return node;
	}

	function metaLine( player ) {
		return [ player.meta, player.position ].filter( Boolean ).join( ' · ' );
	}

	function save() {
		field.value = JSON.stringify(
			selected.map( ( { id, name, age, birth_year, club, url } ) => ( { id, name, age, birth_year, club, url } ) )
		);
		renderSelected();
	}

	function renderSelected() {
		selectedList.replaceChildren();
		if ( selected.length === 0 ) {
			selectedList.appendChild( el( 'li', 'hp-box__empty', config.i18n.empty ) );
			return;
		}

		selected.forEach( ( player, index ) => {
			const item = el( 'li', 'hp-box__player' );
			const label = el( 'span', 'hp-box__label' );
			label.appendChild( el( 'strong', '', player.name ) );
			const meta = player.meta || [ player.age ? player.age : null, player.club ].filter( Boolean ).join( ' · ' );
			if ( meta ) {
				label.appendChild( el( 'span', 'hp-box__meta', meta ) );
			}
			item.appendChild( label );

			const actions = el( 'span', 'hp-box__actions' );
			actions.appendChild( button( '↑', config.i18n.moveUp, index === 0, () => move( index, -1 ) ) );
			actions.appendChild( button( '↓', config.i18n.moveDown, index === selected.length - 1, () => move( index, 1 ) ) );
			actions.appendChild( button( '✕', config.i18n.remove, false, () => remove( index ) ) );
			item.appendChild( actions );

			selectedList.appendChild( item );
		} );
	}

	function button( symbol, label, disabled, onClick ) {
		const node = el( 'button', 'button-link hp-box__action', symbol );
		node.type = 'button';
		node.disabled = disabled;
		node.setAttribute( 'aria-label', label );
		node.title = label;
		node.addEventListener( 'click', onClick );
		return node;
	}

	function move( index, step ) {
		const [ player ] = selected.splice( index, 1 );
		selected.splice( index + step, 0, player );
		save();
	}

	function remove( index ) {
		selected.splice( index, 1 );
		save();
	}

	function add( player ) {
		if ( selected.some( ( chosen ) => chosen.id === player.id ) ) {
			closeResults();
			return;
		}
		if ( selected.length >= config.max ) {
			status.textContent = config.i18n.full;
			return;
		}

		selected.push( player );
		save();
		addTag( player.name );
		search.value = '';
		closeResults();
		search.focus();
	}

	/** Taggrutans egen "Lägg till": tagBox läser fältet och lägger taggen. */
	function addTag( name ) {
		const tagBox = document.getElementById( 'post_tag' );
		const input = tagBox && tagBox.querySelector( 'input.newtag' );
		if ( ! input || ! window.tagBox || ! window.jQuery ) {
			return;
		}
		input.value = name;
		window.tagBox.flushTags( jQuery( tagBox ) );
	}

	function closeResults() {
		found = [];
		active = -1;
		results.replaceChildren();
		results.hidden = true;
		status.textContent = '';
	}

	function renderResults() {
		results.replaceChildren();
		found.forEach( ( player, index ) => {
			const option = el( 'li', 'hp-box__result' + ( index === active ? ' is-active' : '' ) );
			option.setAttribute( 'role', 'option' );
			option.setAttribute( 'aria-selected', index === active ? 'true' : 'false' );
			option.appendChild( el( 'strong', '', player.name ) );
			const meta = metaLine( player );
			if ( meta ) {
				option.appendChild( el( 'span', 'hp-box__meta', meta ) );
			}
			if ( selected.some( ( chosen ) => chosen.id === player.id ) ) {
				option.classList.add( 'is-chosen' );
			}
			option.addEventListener( 'mousedown', ( event ) => {
				event.preventDefault();
				add( player );
			} );
			results.appendChild( option );
		} );
		results.hidden = found.length === 0;
	}

	async function run( term ) {
		if ( controller ) {
			controller.abort();
		}
		controller = new AbortController();
		status.textContent = config.i18n.searching;

		try {
			const url = new URL( config.searchUrl, window.location.origin );
			url.searchParams.set( 'q', term );
			const response = await fetch( url, {
				headers: { 'X-WP-Nonce': config.nonce },
				signal: controller.signal,
				credentials: 'same-origin',
			} );
			if ( ! response.ok ) {
				throw new Error( String( response.status ) );
			}
			found = await response.json();
			active = found.length > 0 ? 0 : -1;
			status.textContent = found.length === 0 ? config.i18n.noResults : '';
			renderResults();
		} catch ( error ) {
			if ( error.name !== 'AbortError' ) {
				closeResults();
				status.textContent = config.i18n.error;
			}
		}
	}

	search.addEventListener( 'input', () => {
		clearTimeout( timer );
		const term = search.value.trim();
		if ( term.length < 2 ) {
			closeResults();
			return;
		}
		timer = setTimeout( () => run( term ), 250 );
	} );

	search.addEventListener( 'keydown', ( event ) => {
		// Enter i sökfältet får aldrig skicka hela inläggsformuläret.
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			if ( found[ active ] ) {
				add( found[ active ] );
			}
			return;
		}
		if ( event.key === 'Escape' ) {
			closeResults();
			return;
		}
		if ( ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) && found.length > 0 ) {
			event.preventDefault();
			active = ( active + ( event.key === 'ArrowDown' ? 1 : -1 ) + found.length ) % found.length;
			renderResults();
		}
	} );

	search.addEventListener( 'blur', () => setTimeout( closeResults, 150 ) );

	renderSelected();
}() );
