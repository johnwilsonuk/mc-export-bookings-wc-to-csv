<?php 
/**
 * @package MC_Export_Bookings_CV_to_CSV
 * @version 1.0.0
 */
/*
Plugin Name: MC Export Bookings WC to CSV

*/

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
		add_submenu_page( 'edit.php?post_type=wc_booking', __( 'Exporter réservations', 'export-bookings-to-csv' ), __( 'Exporter réservations', 'export-bookings-to-csv' ), 'manage_options', 'export-bookings-to-csv', array( $this,'export_bookings_to_csv') );
	}
	
	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	 public function export_bookings_to_csv(){
		echo '<h1>Exporter les réservations</h1>';
		
		global $wpdb;
		
		$args = array(
			    'post_type' => 'product',
			    'posts_per_page' => -1,  
		);
		$products = get_posts($args);
		// Query all products for display them in the select in the backoffice
	?>
		<div class="wrap">
			<h2>Exporter les réservations</h2>
			<form method="post" name="csv_exporter_form" action="" enctype="multipart/form-data">
				<?php wp_nonce_field( 'export-bookings-bookings_export', '_wpnonce-export-bookings-bookings_export' ); ?>
				<p><h3>Choisissez quel type d'évènement vous souhaitez exporter:</h3></p>
				
				<label>Evènement:</label>
				<select name="resource" id="resource">
					<option value="">Selectionner un évènement</option>
					<?php foreach($products as $product) {?>
						<option value="<?php echo  $product->ID;?>" name="event"><?php echo  $product->post_title; ?></option>
					<?php }?>
				</select>
				
				<h3>Cliquez pour sauvegardez l'export. Si vous faites plusieurs exports, renommez-les en fonction.</h3>
				
				<p class="submit"><input type="submit" name="Submit" value="Exporter" /></p>
			</form>
		</div>
		<?php 
	}


	
	public function generate_csv(){
		if ( isset( $_POST['_wpnonce-export-bookings-bookings_export'] ) ) {

			global $wpdb;
			$event_name = $_POST['resource']; // the value selected in the dropdown in back office = product id
			
			$args = array(
			    'post_type' => 'shop_order',
 				'post_status' => 'wc-completed',
			    'posts_per_page' => -1,
			   
			);
			$orders = get_posts($args);
			// Query all orders which are completed
			foreach($orders as $o):

			    $order_id = $o->ID;
			    $order = new WC_Order($order_id);
			
			    foreach( $order->get_items() as $item ):

			    $event_id = $item['product_id']; // product_id = meta_key for the product id attached to the booking in the woocommerce_order_itemmeta table
				$product_id = $item['Booking ID']; // Booking id = meta_key for the booking id in the woocommerce_order_itemmeta table
				if($event_id == $event_name && !empty($product_id)): // check if the selected product in the back office is equal to product_id in database AND product_id not empty
			    	
			    	$product = $item['name']; // product name

			    	/* here we are querying informations in the postmeta table */
			    	$ressource_id = get_post_meta($product_id, '_booking_resource_id', true); // get the resource id. if you're not using resources remove this

			    	$booking_ressource = get_the_title($ressource_id); // get the resource name

					$start_date_long = get_post_meta( $product_id, '_booking_start', true ); 
					$start_date_timestamp = DateTime::createFromFormat('YmdHis', $start_date_long);
					$start_date = $start_date_timestamp->format('d-m-Y h:i');

					$end_date_long = get_post_meta( $product_id, '_booking_end', true );
					$end_date_timestamp = DateTime::createFromFormat('YmdHis', $end_date_long);
					$end_date = $end_date_timestamp->format('d-m-Y h:i');

					$customer_name = get_post_meta( $order_id, '_billing_first_name', true );
					$customer_last_name = get_post_meta( $order_id, '_billing_last_name', true );
					$customer_mail = get_post_meta( $order_id, '_billing_email', true);
					$customer_phone = get_post_meta( $order_id, '_billing_phone', true);

					$price = get_post_meta($order_id, '_order_total', true);

			    	if($start_date && $end_date){ // check if there are a start date and end date
						$data[] = array($product_id, $product, $start_date, $end_date, $booking_ressource, $customer_name, $customer_last_name, $customer_mail, $customer_phone, $price);
						// here we construct the array to pass informations to export CSV
					}
					endif;
			   endforeach;
			endforeach;	
			
			$this->array_to_csv_download($data); // pass $data to array_to_csv_download function
			exit;
		}
	}
	
	function array_to_csv_download($data, $filename = "export.csv", $delimiter=",") {
		// echo 'here';
		ob_start();
		// open raw memory as file so no temp files needed, you might run out of memory though
		$f = fopen('php://output', 'w'); 
		$header = array('No Resas', 'Evenenements', 'Debut', 'Fin', 'Ressource', 'Nom', 'Prenom', 'Mail', 'Telephone', 'Prix paye');
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
		header("Content-Disposition: attachment; filename=".$filename);  
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