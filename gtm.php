<?php
class WooCommerce_GTM_Conversion_Pixel  {
	/**
	 * Constructor, sets up all the actions
	 */
	public function __construct() {
		// add the tracking pixel to all pages in the frontend
		add_action( 'wp_head', array( &$this, 'gtm_tracking_pixel') );
		// add to cart event
		add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'add_to_cart' ) ); // single product
		#add_action( 'woocommerce_pagination', array( &$this, 'loop_add_to_cart' ) ); // loop
	}

	/**
	 * Event tracking for product page add to cart
	 */
	public function add_to_cart() {
		global $post;
		$product = wc_get_product( $post->ID );
		$params = array();
		$params['content_name'] = $product->get_title();
		$params['content_ids'] = array( $product->id );
		$params['content_type'] = 'product';
		$params['value'] = floatval( $product->get_price() );
		$params['currency'] = get_woocommerce_currency();
		$this->gtm_track_event( 'AddToCart', '.button.alt', $params );
	}
	/**
	 * Event tracking for loop add to cart
	 */
	/*public function loop_add_to_cart() {
		$this->gtm_track_event( 'AddToCart', '.button.add_to_cart_button' );
	} */
	/**
	 * Print the tracking pixel to wp_head
	 */
	public function gtm_tracking_pixel() {
		// only show the pixel if a tracking ID is defined
		if( is_singular( 'product' ))  :
			global $post;
		$product = wc_get_product( $post->ID );
		$params = array();
		$params['content_name'] = $product->get_title();
		$params['content_ids'] = array( $product->get_sku() ? $product->get_sku() : $product->id );
		$params['content_type'] = 'product';
		$params['value'] = floatval( $product->get_price() );
		$params['currency'] = get_woocommerce_currency();
		# fbq('track', 'ViewContent', <?php echo json_encode( $params ); );
		echo '<script>
// The GTM code.
dataLayer.push({
  "event": "'. esc_js( 'ViewContent' ) .'",
      "products": [
	  '.json_encode( $params ).'
      ]
});</script>';
		endif;
		if( is_order_received_page()  ) :


			global $wp;
		$params = array();
		$order_id = isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : 0;
		if( $order_id ) {
			$params['order_id'] = $order_id;
			$order = new WC_Order( $order_id );
			if( $order->get_items() ) {
				$productids = array();
				foreach ( $order->get_items() as $item ) {
					$product = $order->get_product_from_item( $item );
					$productids[] = $product->get_sku() ? $product->get_sku() : $product->id;
				}
				$params['content_ids'] = $productids;
			}
			$params['content_type'] = 'product';
			$params['value'] = $order->get_total();
			$params['currency'] = get_woocommerce_currency();
		}

		echo '<script>
// The GTM code.
dataLayer.push({
  "event": "'. esc_js( 'Purchase' ) .'",
      "products": [
	  '.json_encode( $params ).'
      ]
});</script>';




		elseif( is_checkout()  ) :
			// get $cart to params
			$cart = WC()->cart->get_cart();
		$productids = array();
		foreach($cart as $id => $item) {
			$product_id = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
			$product = new WC_Product( $product_id );
			$productids[] = $product->get_sku() ? $product->get_sku() : $product->id;
		}
		$params = array();
		$params['num_items'] = WC()->cart->cart_contents_count;
		$params['value'] = WC()->cart->total;
		$params['currency'] = get_woocommerce_currency();
		$params['content_ids'] = $productids;
		echo '<script>
// The GTM code.
dataLayer.push({
  "event": "'. esc_js( 'InitiateCheckout' ) .'",
      "products": [
	  '.json_encode( $params ).'
      ]
});</script>';

		endif;

	}
	/**
	 * Output inline javascript to bind an fbq event to the $.click() event of a selector
	 */
	public function gtm_track_event( $name, $selector, $params = array() ) {

?>
<script>
(function($) {
  $('<?php echo esc_js( $selector ); ?>').click(function() {
<?php if( !empty( $params ) ) : ?>
    var params = <?php echo json_encode( $params ); ?>;
<?php else : ?>
    var params = {};
<?php endif; ?>
// The GTM code.
dataLayer.push({
  "event": "<?php echo esc_js( $name ); ?>",
      "products": [
	  params
      ]
});
  });
})(jQuery);
</script>

<?php
	}

}