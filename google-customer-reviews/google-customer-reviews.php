<?php
/**
 * Plugin Name:		Google Customer Reviews for WooCommerce
 * Plugin URI:		https://github.com/Scaarus/Google-Customer-Reviews-For-WooCommerce
 * Description:		Adds Google customer reviews to the checkout page, and a rating badge to all pages
 * Version:			1.1.0
 * Author:			Michael Leone
 * Author URI:		https://github.com/Scaarus
 * License:			GPL v3
 * License URI:		https://github.com/Scaarus/Google-Customer-Reviews-For-WooCommerce/blob/main/LICENSE
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function add_gcr() {
	if ( !class_exists( 'WooCommerce' ) ) return;
	if ( !is_wc_endpoint_url( 'order-received' ) ) return;

	$merchant_id = get_option( 'gcr_merchant_id' );
	if ( empty( $merchant_id ) ) return;

	global $wp;

	// Get the order ID
	$order_id  = absint( $wp->query_vars['order-received'] );

	if ( empty($order_id) || $order_id == 0 )
	    return; // Exit;

	$order = wc_get_order($order_id);
	$email = $order->get_billing_email();
	$delivery_date = date('Y-m-d', strtotime("+3 day"));

	$products = "[";
	if ( get_option( 'gcr_provide_gtin', 'yes' ) == 'no' )
	{
		foreach ($order->get_items() as $item) {
			$sku = $order->get_product_from_item( $item )->get_sku();
			if ( ! empty( $sku ) )
			{
				$products = $products . "{\"gtin\":\"" . $sku . "\"},";
			}
		}
	}
	$products = rtrim($products, ",") . "]";
	?>

	<!-- BEGIN GCR Opt-in Module Code -->

	<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
	<script>
	window.renderOptIn = function() {
		window.gapi.load('surveyoptin', function() {
			window.gapi.surveyoptin.render({
			"merchant_id": "<?php echo $merchant_id; ?>",
			"order_id": "<?php echo $order_id; ?>",
			"email": "<?php echo $email; ?>",
			"delivery_country": "<?php echo $order->shipping_country; ?>",
			"estimated_delivery_date": "<?php echo $delivery_date; ?>",
			"opt_in_style": "<?php echo get_option( 'gcr_opt_in_style', 'CENTER_DIALOG' ); ?>",
			"products": <?php echo $products; ?>
			});
		});
	}
	</script>

	<!-- END GCR Opt-in Module Code -->

	<?php
}
add_action( 'wp_head', 'add_gcr' );

function add_gcr_badge() {
	if ( is_admin() ) return;
	if ( get_option( 'gcr_display_badge', 'no' ) == 'no' ) return;
	$merchant_id = get_option( 'gcr_merchant_id' );
	if ( empty( $merchant_id ) ) return;
	?>

	<!-- BEGIN GCR Badge Code -->
	<script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer>
	</script>
	<script>
	  window.renderBadge = function() {
	    var ratingBadgeContainer = document.createElement("div");
	      document.body.appendChild(ratingBadgeContainer);
	      window.gapi.load('ratingbadge', function() {
	        window.gapi.ratingbadge.render(
	          ratingBadgeContainer, {
	            // REQUIRED
	            "merchant_id": "<?php echo $merchant_id; ?>",
	            // OPTIONAL
	            "position": "<?php echo get_option( 'gcr_badge_position', 'BOTTOM_RIGHT' ); ?>"
	          });           
	     });
	  }
	</script>
	<!-- END GCR Badge Code -->
	<!-- BEGIN GCR Language Code -->
	<script>
	  window.___gcfg = {
	    lang: 'en-US'
	  };
	</script>
	<!-- END GCR Language Code -->

	<?php
}

add_action( 'wp_head', 'add_gcr_badge' );

add_action( 'woocommerce_settings_tabs_integration', 'gcr_settings' );
add_action( 'woocommerce_update_options_integration', 'update_settings' );
function gcr_settings() {
	woocommerce_admin_fields( gcr_get_settings() );
}

function update_settings() {
    woocommerce_update_options( gcr_get_settings() );
}

function gcr_get_settings() {
	$settings = array(
  		'section_title' => array(
  			'name'     => __( 'Google Customer Reviews' ),
  			'type'     => 'title',
  			'desc'     => '',
  			'id'       => 'gcr_section_title'
  		),
  		'gcr_merchant_id' => array(
  			'name' => __( 'Merchant ID' ),
  			'type' => 'text',
  			'desc' => __( 'This can be found in <a href="https://merchants.google.com/mc/overview">Google Merchant Center</a>' ),
  			'id'   => 'gcr_merchant_id'
  		),
  		'gcr_display_badge' => array(
  			'name' => __( 'Display Reviews Badge' ),
  			'type' => 'checkbox',
  			'desc' => __( 'Display a badge with your current rating' ),
  			'id'   => 'gcr_display_badge'
  		),
  		'gcr_badge_position' => array(
  			'name' => __( 'Badge Display Location' ),
  			'type' => 'select',
  			'options' => array(
  				'BOTTOM_RIGHT' => 'Bottom Right',
  				'BOTTOM_LEFT' => 'Bottom Left',
  			),
  			'desc' => __( 'Where the rating badge should display on a page' ),
  			'id'   => 'gcr_badge_position'
  		),
  		'gcr_provide_gtin' => array(
  			'name' => __( 'Provide Product SKUs' ),
  			'type' => 'checkbox',
  			'desc' => __( 'Enabling this will send the SKUs of purchased products to Google as part of the review' ),
  			'id'   => 'gcr_provide_gtin'
  		),
  		'gcr_opt_in_style' => array(
  			'name' => __( 'Review Opt In Display Location' ),
  			'type' => 'select',
  			'options' => array(
  				'CENTER_DIALOG' => 'Center of Screen',
  				'BOTTOM_RIGHT_DIALOG' => 'Bottom Right',
  				'BOTTOM_LEFT_DIALOG' => 'Bottom Left',
  				'TOP_RIGHT_DIALOG' => 'Top Right',
  				'TOP_LEFT_DIALOG' => 'Top Left',
  				'BOTTOM_TRAY' => 'Bottom',
  			),
  			'desc' => __( 'Where the review opt in dialog should display on the order confirmation page' ),
  			'id'   => 'gcr_opt_in_style'
  		),
  		'section_end' => array(
  			'type' => 'sectionend',
  			'id' => 'gcr_integrations_settings_tabs_section_end'
  		)
  	);

  	return apply_filters( 'wc_integration_settings', $settings );
}
