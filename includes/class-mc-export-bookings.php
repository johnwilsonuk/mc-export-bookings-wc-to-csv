<?php 
/**
 * @package MC_Export_Bookings_WC_to_CSV
 * @version 1.0.2
 */

/**
*
* Escape is someone tries to access directly
*
**/
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

/**
* Main plugin class
*
* @since 1.0
**/
if ( !class_exists( 'MC_Export_Bookings' ) ) {
	class MC_Export_Bookings {
		
		/**
		* Class contructor
		*
		* @since 1.0
		**/
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mc_wcb_csv_register_script' ) );
			add_action( 'wp_ajax_mc_wcb_find_booking', array( $this, 'mc_wcb_find_booking' ) );
			add_action( 'wp_ajax_mc_wcb_export', array( $this, 'mc_wcb_export' ) );		
		}

		public function mc_wcb_csv_register_script( $hook ) {
			// Load only on export bookings pages
	        if( $hook != 'wc_booking_page_export-bookings-to-csv' ) {
	            return;
	        }

			wp_register_script( 'mc-wcb-script', MC_WCB_CSV . 'assets/mc-wcb-script.js', array( 'jquery' ), '1.0', true );
			wp_enqueue_script( 'mc-wcb-script' );
			wp_localize_script( 'mc-wcb-script', 'mc_wcb_params', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'security' => wp_create_nonce( 'mc-wcb-nonce' ) ) );
			
			wp_register_style('mc-wcb-css', MC_WCB_CSV . 'assets/mc-wcb-css.css');
			wp_enqueue_style( 'mc-wcb-css' );
		}

		/**
		* Add administration menus
		*
		* @since 0.1
		**/
		public function add_admin_pages() {
			add_submenu_page( 
	            'edit.php?post_type=wc_booking', 
	            __( 'Export bookings', 'export-bookings-to-csv' ),
	            __( 'Export bookings', 'export-bookings-to-csv' ),
	            'manage_options', 
	            'export-bookings-to-csv', 
	            array( $this,'mc_wcb_main_screen') 
	        );
		}

		/**
		* Main plugin screen 
		*/
		public function mc_wcb_main_screen() {		
		
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
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Export WooCommerce Bookings' , 'export-bookings-csv' ); ?></h1>
				<div class="mc-wcb-export-box postbox">
					<form method="post" name="csv_exporter_form" action="" enctype="multipart/form-data">
						<?php wp_nonce_field( 'export-bookings-bookings_export', '_wpnonce-export-bookings' ); ?>
						<h2>1. <?php esc_html_e( 'Select from which product to export bookings :', 'export-bookings-csv' ); ?></h2>
						
						<label><?php esc_html_e( 'Product : ', 'export-bookings-csv' ); ?></label>
						<select name="mc-wcb-product-select" id="mc-wcb-product-select">
							<option value=""><?php esc_html_e( 'Select a product', 'export-bookings-csv' ); ?></option>
							<?php foreach($products as $product) {?>
								<option value="<?php echo $product->ID;?>" name="event"><?php echo $product->post_title; ?></option>
							<?php }?>
						</select>
						<div class="mc-wcb-response">
							<img src="<?php echo MC_WCB_CSV ?>img/loader.svg" class="mc-wcb-loader"/>
							<div class="mc-wcb-result"></div>
						</div>
						<div class="mc-wcb-export">
							<h2>2. <?php esc_html_e( 'Click on "export" to generate CSV file :', 'export-bookings-csv' ); ?></h2>
							<input type="submit" name="mc-wcb-submit" id="mc-wcb-submit" class="button button-primary" value="<?php esc_html_e( 'Export', 'export-bookings-csv' ); ?>" />
						</div>
						<div class="mc-wcb-export-result">
							<p><?php esc_html_e( 'Be patient, export is in progress, please do not close this page.' , 'export-bookings-csv' ); ?></p>
							<p><?php esc_html_e( 'A download link will be displayed below at the end of the process.' , 'export-bookings-csv' ); ?></p>
						</div>
						<div class="mc-wcb-download">
							<h2>3. <?php esc_html_e( 'Download your file :', 'export-bookings-csv' ); ?></h2>
							<a href="#" class="mc-wcb-link"><?php _e( 'Download', 'export-bookings-csv' ); ?></a>
						</div>
					</form>
				</div>
				<?php
				$exports_list = $this->mc_wcb_list_exports();
				if ( $exports_list ) {
				?>
					<div class="mc-wcb-exports-list postbox">
						<?php 					
						$upload_dir = wp_upload_dir();
						echo '<h2>' . __( 'Your previous exports :', 'export-bookings-csv' ) . '</h2>';
						echo '<ul>';
						foreach ( $exports_list as $file ) {
							echo '<li><a href="' . $upload_dir['baseurl'] . '/woocommerce-bookings-exports/' . $file . '" class="mc-wcb-link"><span class="dashicons dashicons-download"></span>' . $file . '</a></li>';
						}
						echo '</ul>';
						?>
					</div>
				<?php } ?>
			</div>
			<?php 
		}

		/**
		* mc_wcb_list_exports
		* List exports in uploads/woocommerce-bookings-exports/ folder
		* @since 1.0.2
		*/
		public function mc_wcb_list_exports() {
			$upload_dir = wp_upload_dir();
			$files  = @scandir( $upload_dir['basedir'] . '/woocommerce-bookings-exports' );

			$result = array();

			if ( ! empty( $files ) ) {

				foreach ( $files as $key => $value ) {

					if ( ! in_array( $value, array( '.', '..' ) ) ) {
						if ( ! is_dir( $value ) && strstr( $value, '.csv' ) ) {
							$result[ sanitize_title( $value ) ] = $value;
						}
					}
				}
			}

			return $result;
		}

		/**
		* Get bookings by product id
		* @since 1.0.2
		* @param $product_id int
		* @return $bookinds_ids array
		*/
		public function mc_wcb_get_bookings( $product_id ) {
			if ( $product_id ) {

				$booking_data = new WC_Booking_Data_Store();

				$bookings_ids = $booking_data->get_booking_ids_by( array(
					'object_id'   => $product_id,
					'object_type' => 'product',
					'order_by' => 'start_date',
					'status'      => array( 'confirmed', 'paid', 'complete' ),
					'limit'        => -1,
				) );

				return $bookings_ids;
			}

			return false;
		}
		
		/**
		* mc_wcb_find_booking
		* Find booking when select a product
		* @since 1.0.2
		**/
		public function mc_wcb_find_booking() {
			$query_data = $_GET;

			$data = array();

			// verify nonce
			if ( ! wp_verify_nonce( $_GET['security'], 'mc-wcb-nonce' ) ) {
			    $error = -1;
			    wp_send_json_error( $error );
			    exit;
			}

			if ( isset( $_GET['selected_product_id'] ) && !empty( $_GET['selected_product_id'] ) ) {
				$product_id = $_GET['selected_product_id'];

				if ( ! class_exists( 'WC_Booking_Data_Store' ) ) {
					$error = 0;
					$error['message'] = __( 'Can\'t found WC_Booking_Data_Store class.', 'export-bookings-to-csv' );
					wp_send_json_error( $error );
					exit;
				}

				$bookings_ids = $this->mc_wcb_get_bookings( $product_id );
				
				if ( $bookings_ids ) {
					$booking_count = count( $bookings_ids );
					$data['message'] =  sprintf( __( '<b>%d</b> booking(s) found.', 'export-bookings-to-csv' ), $booking_count );
					wp_send_json_success( $data );
				} else {
					$data['message'] =  __( 'No booking(s) found for this product.', 'export-bookings-to-csv' );
					wp_send_json_error( $data );
				}
			} else {
				$error['code'] = 1;
				$error['message'] =  __( 'Please select product.', 'export-bookings-to-csv' );
				wp_send_json_error( $error );
				exit;
			}

			wp_die();
		}

		/**
		* mc_wcb_export
		* Contruct PHP data array for CSV export
		*
		* @since 0.1
		**/		
		public function mc_wcb_export(){

			// verify nonce
			if ( ! wp_verify_nonce( $_GET['security'], 'mc-wcb-nonce' ) ) {
			    $error = -1;
			    wp_send_json_error( $error );
			    exit;
			}

			if ( isset( $_GET['selected_product_id'] ) && !empty( $_GET['selected_product_id'] ) ) {
		

				$product_id = $_GET['selected_product_id'];

				$product_slug = get_post_field( 'post_name', $product_id );
				$file_name = $product_slug . '-' . date('d-m-Y-h-i');

				if ( ! class_exists( 'WC_Booking_Data_Store' ) ) {
					$error = 0;
					$error['message'] = __( 'Can\'t found WC_Booking_Data_Store class.', 'export-bookings-to-csv' );
					wp_send_json_error( $error );
					exit;
				}

				$bookings_ids = $this->mc_wcb_get_bookings( $product_id );
				
				if ( $bookings_ids ) {

					$json = array();

					$data = array();

					foreach ( $bookings_ids as $booking_id ) {
						$booking = new WC_Booking( $booking_id );

						$product_name = $booking->get_product()->get_title();

				    	$resource = $booking->get_resource();
				    	if ( $booking->has_resources() && $resource ) {
				    		$booking_ressource = $resource->post_title;
				    	} else {
				    		$booking_ressource = 'N/A';
				    	}

						$start_date_timestamp = $booking->get_start();
						if ( $start_date_timestamp ) {
							$start_date = date( 'd-m-Y H:i', $start_date_timestamp );
						} else {
							$start_date = 'N/A';
						}

						$end_date_timestamp = $booking->get_end();
						if ( $end_date_timestamp ) {
							$end_date = date( 'd-m-Y H:i', $end_date_timestamp );
						} else {
							$end_date = 'N/A';
						}

						$order = $booking->get_order();
						if ( $order ) {

							$customer_name = ( $order->get_billing_first_name() ? $order->get_billing_first_name() : 'N/A' );
							$customer_last_name = ( $order->get_billing_last_name() ? $order->get_billing_last_name() : 'N/A' );
							$customer_mail = ( $order->get_billing_email() ? $order->get_billing_email() : 'N/A' );
							$customer_phone = ( $order->get_billing_phone() ? $order->get_billing_phone() : 'N/A' );

							$price = ( $order->get_total() ? $order->get_total() : 'N/A' );
						}

				    	if ( $start_date && $end_date ) { // check if there are a start date and end date
							$data[] = array($booking_id, $product_name, $start_date, $end_date, $booking_ressource, $customer_name, $customer_last_name, $customer_mail, $customer_phone, $price);
							// here we construct the array to pass informations to export CSV
						}
					}

					if ( $data && is_array( $data ) && !empty( $data ) ) {
						$file_url = $this->array_to_csv_download( $data, $file_name ); // pass $data to array_to_csv_download function

						if ( $file_url ) {
							error_log(print_r($file_url, true));
							$json['file_url'] = $file_url;
							wp_send_json_success( $json );
						}
					}
				}
			}

			wp_die();
		}
		
		/**
		* array_to_csv_download
		* Process PHP array to CSV file
		* @param $data array
		* @param $filename string
		* @param $delimiter string
		* @return $file_url string
		* @since 1.0.0
		*/
		function array_to_csv_download( $data, $filename, $delimiter="," ) {

			ob_start();
			$upload_dir = wp_upload_dir();
			//$f = fopen( 'php://output', 'w');
			$f = fopen( $upload_dir['basedir'] . '/woocommerce-bookings-exports/' . $filename . '.csv', 'w' );
			$header = array( 
	            __( 'No Resa', 'export-bookings-to-csv' ), 
	            __( 'Produit', 'export-bookings-to-csv' ), 
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

			$file_url = $upload_dir['baseurl'] . '/woocommerce-bookings-exports/' . $filename . '.csv';

			return $file_url;
			

			// rewrind the "file" with the csv lines
			// fseek($f, 0);
			/*header("Content-Type: application/csv");    
			header("Content-Disposition: attachment; filename=" . $filename . ".csv");  
			// Disable caching
			header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
			header("Pragma: no-cache"); // HTTP 1.0
			header("Expires: 0"); // Proxies*/
		}
	}
	
	global $mc_wcb_csv;
	
	if ( ! isset( $mc_wcb_csv ) ) {

	    $mc_wcb_csv = new MC_Export_Bookings();

	}
}