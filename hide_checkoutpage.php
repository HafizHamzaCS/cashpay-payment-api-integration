<?php
add_filter('woocommerce_order_button_html', 'remove_place_order_button_for_specific_payments' );
function remove_place_order_button_for_specific_payments( $button ) {
    global $woocommerce;
    // HERE define your targeted payment(s) method(s) in the array
    $targeted_payments_methods = array('cashpay');
    $chosen_payment_method     = WC()->session->get('chosen_payment_method'); // The chosen payment

    // For matched payment(s) method(s), we remove place order button (on checkout page)
    if( in_array( $chosen_payment_method, $targeted_payments_methods ) && ! is_wc_endpoint_url() ) {
        $button = ''; 
    }
   
    return $button;
}

// jQuery - Update checkout on payment method change
add_action( 'wp_footer', 'custom_checkout_jquery_script' );
function custom_checkout_jquery_script() {
    if ( is_checkout() && ! is_wc_endpoint_url() ) :
    ?>
    <script type="text/javascript">
    jQuery( function($){
        $('form.checkout').on('change', 'input[name="payment_method"]', function(){
            $(document.body).trigger('update_checkout');
        });
    });
    </script>
    <?php
    endif;
}