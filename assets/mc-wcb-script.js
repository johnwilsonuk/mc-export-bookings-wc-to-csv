// Ajax Filtering
jQuery(function($)
{
    $(document).ready(function() {

    	$( 'select#mc-wcb-product-select' ).change( function(){
    	    var product_id = $(this).val();
    	    get_bookings_number( product_id );
    	});

    	function get_bookings_number( product_id ) {

    		var data = {
    		    action          		: 'mc_wcb_find_booking',
    		    selected_product_id		: product_id,
    		    security 				: mc_wcb_params.security,
    		};

    		$.get({
    		    type: 'get',
    		    url: mc_wcb_params.ajax_url,
    		    dataType: 'json',    
    		    data: data,
    		    contentType: "application/json; charset=utf-8",
    		    beforeSend: function ()
    		    {
    		    	$( 'select#mc-wcb-product-select' ).prop( 'disabled', 'disabled' );
    		    	$( '.mc-wcb-loader' ).fadeIn( 'slow' );
    		    	$( '.mc-wcb-export' ).fadeOut( 'slow' );
    		    	$( '.mc-wcb-result' ).fadeOut( 'slow' );
    		    	$( '.mc-wcb-download' ).fadeOut( 'slow' );
    		        
    		    },
    		    success: function( response )
    		    {
    		    	$( 'select#mc-wcb-product-select' ).prop( 'disabled', false );
    		    	$( '.mc-wcb-loader' ).fadeOut( 'slow' );
    		    	$( '.mc-wcb-result' ).hide().html( '<span>' + response.data.message + '</span>' ).fadeIn( 'slow' );
    		    	if ( true === response.success ) {
    		    		$( '.mc-wcb-export' ).fadeIn( 'slow' );
    		    	}
    		    },
    		    error: function( response )
    		    {
    		    	$( '.mc-wcb-loader' ).fadeOut( 'slow' );
    		    	$( '.mc-wcb-result' ).hide().html( '<span>' + response.message + '</span>' ).fadeIn( 'slow' );
    		    }
    		});
    	}

    	$('#mc-wcb-submit').click( function( e ) {
    		e.preventDefault();
    		var product_id = $( 'select#mc-wcb-product-select' ).val();
    		if ( product_id != null && product_id != 0 ) {
    			export_bookings( product_id );
    		}
    	});

    	function export_bookings( product_id ) {

    		var data = {
    		    action          		: 'mc_wcb_export',
    		    selected_product_id		: product_id,
    		    security 				: mc_wcb_params.security,
    		};

    		$.get({
    		    type: 'get',
    		    url: mc_wcb_params.ajax_url,
    		    dataType: 'json',    
    		    data: data,
    		    contentType: "application/json; charset=utf-8",
    		    beforeSend: function ()
    		    {
    		    	$( 'select#mc-wcb-product-select' ).prop( 'disabled', 'disabled' );
    		    	$( '.mc-wcb-loader' ).fadeIn( 'slow' );
    		    	$( '.mc-wcb-export' ).fadeOut( 'slow' );
    		    	$( '.mc-wcb-result' ).fadeOut( 'slow' );
    		    	$( '.mc-wcb-export-result' ).fadeIn( 'slow' );
    		        
    		    },
    		    success: function( response )
    		    {
    		    	console.log(response);

    		    	$( 'select#mc-wcb-product-select' ).prop( 'disabled', false );
    		    	$( '.mc-wcb-loader' ).fadeOut( 'slow' );
    		    	$( '.mc-wcb-export-result' ).fadeOut( 'slow' );
    		    	
    		    	if ( true === response.success ) {
    		    		$( '.mc-wcb-link' ).attr( 'href', response.data.file_url );
    		    		$( '.mc-wcb-download' ).fadeIn( 'slow' );
    		    	} else {

    		    	}
    		    },
    		    error: function( response )
    		    {
    		    	console.log(response);
    		    	$( '.mc-wcb-loader' ).fadeOut( 'slow' );
    		    	//$( '.mc-wcb-result' ).hide().html( '<span>' + response.message + '</span>' ).fadeIn( 'slow' );
    		    }
    		});
    	}
    });
});