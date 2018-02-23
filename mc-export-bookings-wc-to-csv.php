<?php 
/**
 * @package MC_Export_Bookings_CV_to_CSV
 * @version 1.0.0
 */
/*
* Plugin Name: MC Export Bookings WC to CSV
* Plugin URI: https://github.com/MarieComet/mc-export-bookings-wc-to-csv/
* Version: 1.0.0
* Description: MC Export Bookings WC to CSV provides user ability to Export WooCommerce Bookings to CSV
* Author: Marie Comet
* Author URI: http://mariecomet.fr
* License: GPL2
* Text-domain: export-bookings-to-csv
*/

/**
*
* Escape is someone tries to access directly
*
**/
if ( ! defined( 'ABSPATH') ) {
    exit;
}


/**
*
* Call the translation file
*
**/
add_action('init', 'mcebcsv_load_translation_file');

function mcebcsv_load_translation_file() {
    // relative path to WP_PLUGIN_DIR where the translation files will sit:
    $plugin_path = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
    load_plugin_textdomain( 'export-bookings-to-csv', false, $plugin_path );
}

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class MC_Export_Bookings {
	
	/**
	 * Class contructor
	 *
	 * @since 0.1
	 **/
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		add_action( 'admin_init', array( $this, 'generate_csv' ) );
	}

	/**
	 * Add administration menus
	 *
	 * @since 0.1
	 **/
	public function add_admin_pages() {
		add_submenu_page( 
            'woocommerce', 
            __( 'Exporter réservations', 'export-bookings-to-csv' ),
            __( 'Exporter réservations', 'export-bookings-to-csv' ),
            'manage_options', 
            'export-bookings-to-csv', 
            array( $this,'export_bookings_to_csv') 
        );
	}
	
	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	 public function export_bookings_to_csv(){		
		global $wpdb;
		
		$args = array(
			    'post_type' => 'product',
			    'posts_per_page' => -1,
			    'tax_query' => array(
		    		array(
		    			'taxonomy' => 'product_type',
		    			'field'    => 'slug',
		    			'terms'    => 'booking',
		    		),
		    	),
		);
		$products = get_posts($args);
		// Query all products for display them in the select in the backoffice
	?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Exporter les réservations' , 'export-bookings-csv' ); ?></h2>
			<form method="post" name="csv_exporter_form" action="" enctype="multipart/form-data">
				<?php wp_nonce_field( 'export-bookings-bookings_export', '_wpnonce-export-bookings-bookings_export' ); ?>
				<h3><?php _e( 'Choisissez quel type d\'évènement vous souhaitez exporter', 'export-bookings-csv' ); ?>:</h3>
				
				<label><?php esc_html_e( 'Evènement', 'export-bookings-csv' ); ?>:</label>
				<select name="resource" id="resource">
					<option value=""><?php esc_html_e( 'Selectionner un évènement', 'export-bookings-csv' ); ?></option>
					<?php foreach($products as $product) {?>
						<option value="<?php echo  $product->ID;?>" name="event"><?php echo $product->post_title; ?></option>
					<?php }?>
				</select>
				
				<h2><?php esc_html_e( 'Cliquez pour sauvegardez l\'export.', 'export-bookings-csv' ); ?>.</h2>
				
				<p class="submit"><input type="submit" name="Submit" value="Exporter" /></p>
			</form>
		</div>
		<?php 
	}


	
	public function generate_csv(){

		if ( isset( $_POST['_wpnonce-export-bookings-bookings_export'] ) ) {
			if ( isset( $_POST ) && !empty( $_POST) && isset( $_POST['resource'] ) && !empty( $_POST['resource'] ) ) {

				global $wpdb;

				$product_id_select = $_POST['resource']; // the value selected in the dropdown in back office = product id
				$product_slug = get_post_field( 'post_name', $product_id_select );
				$file_name = $product_slug . '-' . date('d-m-Y-h-i');

				$args = array(
				    'post_type' => 'shop_order',
	 				'post_status' => 'wc-completed',
				    'posts_per_page' => -1,
				   
				);
				$orders = get_posts($args);
				// Query all orders which are completed
				if ( $orders ) :

					$data = array();
					foreach( $orders as $o ) :

						if ( ! class_exists( 'WC_Booking_Data_Store' ) ) {
						    return;
						}

					    $order_id = $o->ID;
					    $order = new WC_Order($order_id);
					
					    foreach( $order->get_items() as $item ):

					    	$product_id = $item['product_id'];

					    	if ( $product_id_select == $product_id ) {
						    	$product = $item['name']; // product name

						    	$booking_data = new WC_Booking_Data_Store();
						    	$booking_ids = $booking_data->get_booking_ids_from_order_item_id( $item->get_id() );

						    	if ( $booking_ids ) {
							    	foreach ( $booking_ids as $booking_id ) {
							    	    $booking  = new WC_Booking( $booking_id );
							    	    $status = $booking->get_status();
					    	        	/* here we are querying informations in the postmeta table */
					    	        	$ressource_id = get_post_meta($booking_id, '_booking_resource_id', true); // get the resource id. if you're not using resources remove this

					    	        	$booking_ressource = get_the_title($ressource_id); // get the resource name

					    	    		$start_date_long = get_post_meta( $booking_id, '_booking_start', true ); 
					    	    		$start_date_timestamp = DateTime::createFromFormat('YmdHis', $start_date_long);
					    	    		$start_date = $start_date_timestamp->format('d-m-Y h:i');

					    	    		$end_date_long = get_post_meta( $booking_id, '_booking_end', true );
					    	    		$end_date_timestamp = DateTime::createFromFormat('YmdHis', $end_date_long);
					    	    		$end_date = $end_date_timestamp->format('d-m-Y h:i');

					    	    		$customer_name = get_post_meta( $order_id, '_billing_first_name', true );
					    	    		$customer_last_name = get_post_meta( $order_id, '_billing_last_name', true );
					    	    		$customer_mail = get_post_meta( $order_id, '_billing_email', true);
					    	    		$customer_phone = get_post_meta( $order_id, '_billing_phone', true);

					    	    		$price = get_post_meta($order_id, '_order_total', true);

					    	        	if($start_date && $end_date){ // check if there are a start date and end date
					    	    			$data[] = array($booking_id, $product, $start_date, $end_date, $booking_ressource, $customer_name, $customer_last_name, $customer_mail, $customer_phone, $price);
					    	    			// here we construct the array to pass informations to export CSV
					    	    		}

							    	}
							    }
							}
					   endforeach;
					endforeach;	
					if ( $data && !empty( $data ) ) {
						$this->array_to_csv_download( $data, $file_name ); // pass $data to array_to_csv_download function
					}

				endif;
				exit;
			}
		}
	}
	
	function array_to_csv_download($data, $filename, $delimiter=",") {
		// echo 'here';
		ob_start();
		// open raw memory as file so no temp files needed, you might run out of memory though
		$f = fopen('php://output', 'w'); 
		$header = array( 
            __( 'No Resa', 'export-bookings-to-csv' ), 
            __( 'Evenenement', 'export-bookings-to-csv' ), 
            __( 'Debut', 'export-bookings-to-csv' ), 
            __( 'Fin', 'export-bookings-to-csv' ), 
            __( 'Ressource', 'export-bookings-to-csv' ), 
            __( 'Nom', 'export-bookings-to-csv' ), 
            __( 'Prenom', 'export-bookings-to-csv' ), 
            __( 'Mail', 'export-bookings-to-csv' ), 
            __( 'Telephone', 'export-bookings-to-csv' ), 
            __( 'Prix paye', 'export-bookings-to-csv' )
        );
		fputcsv($f, $header, ';');
		// loop over the input array
		foreach ($data as $line) { 
			// generate csv lines from the inner arrays
			fputcsv($f, $line, ';'); 
		}
		fclose($f);
		// rewrind the "file" with the csv lines
		// fseek($f, 0);
		header("Content-Type: application/csv");    
		header("Content-Disposition: attachment; filename=" . $filename . ".csv");  
		// Disable caching
		header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
		header("Pragma: no-cache"); // HTTP 1.0
		header("Expires: 0"); // Proxies
	}
	 public function pre($arr){
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
	 }
}
new MC_Export_Bookings;