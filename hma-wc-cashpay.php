<?php

/*
  Plugin Name: CashPay
  Plugin URI: https://www.linkedin.com/in/hafiz-hamza-wordpress-developer-b3792b161/
  Description: Integrate CashPay payment geteway with woocommerce
  Author: Hafiz H Javed
  Author URL: https://www.linkedin.com/in/hafiz-hamza-wordpress-developer-b3792b161/
  Version: 1.0.0
 */
// include('hide_checkoutpage.php');
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'cashpay_add_gateway_class');
function cashpay_add_gateway_class($gateways)
{
    $gateways[] = 'WC_CashPayGateway'; // your class name is here
    return $gateways;
}
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'cashpay_init_gateway_class');


function cashpay_init_gateway_class()
{
    class WC_CashPayGateway extends WC_Payment_Gateway
    {
       

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct()
        {

            $this->id = 'cashpay'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom CashPay form
            $this->method_title = 'CashPay Gateway';
            $this->method_description = 'Description of CashPay payment gateway'; // will be displayed on the options page

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->apiurl = "https://www.tamkeen.com.ye:33291/CashPG/api";
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');


            $this->encPassword = $this->testmode ? $this->get_option('test_encPassword') : $this->get_option('live_encPassword');
            $this->CurrencyId = $this->testmode ? $this->get_option('test_CurrencyId') : $this->get_option('live_CurrencyId');
            $this->UserName = $this->testmode ? $this->get_option('test_UserName') : $this->get_option('live_UserName');           
            $this->to_SpId = $this->testmode ? $this->get_option('test_to_SpId') : $this->get_option('live_to_SpId');

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // We need custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
        }
        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable CashPay Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'CashPay',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay with your CashPay via our super-cool payment gateway.',
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),

                'test_encPassword' => array(
                    'title'       => 'Test Enc Password',
                    'type'        => 'text'
                ),
                'test_CurrencyId' => array(
                    'title'       => 'Test Currency Id',
                    'type'        => 'text'
                ),
                'test_UserName' => array(
                    'title'       => 'Test User Name',
                    'type'        => 'text'
                ),
                'test_to_SpId' => array(
                    'title'       => 'Test Sp Id',
                    'type'        => 'text'
                ),
                'live_encPassword' => array(
                    'title'       => 'Live Enc Password',
                    'type'        => 'text'
                ),
                'live_CurrencyId' => array(
                    'title'       => 'Live Currency Id',
                    'type'        => 'text'
                ),
                'live_UserName' => array(
                    'title'       => 'Live User Name',
                    'type'        => 'text'
                ),
                'live_to_SpId' => array(
                    'title'       => 'Live Sp Id',
                    'type'        => 'text'
                ),
            );
        }
        /**
         * You will need it if you want your custom CashPay form, Step 4 is about it
         */
        public function payment_fields()
        {

            // ok, let's display some description before the payment form
            if ($this->description) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ($this->testmode) {
                    // $this->description  = trim($this->description) . 'use 772261000 as TargetMSISDN and 555 as CustomerCashPayCode ';
                    $this->description  = 'ادفع عن طريق محفظة كاش'. 'use 772261000 as TargetMSISDN and 555 as CustomerCashPayCode ';
                }
                // display the description with <p> tags etc.
                echo wpautop(wp_kses_post($this->description));
            }

            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

            // Add this action hook if you want your custom payment gateway to support it
            do_action('woocommerce_credit_card_form_start', $this->id);

            // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
            // $error_code = hma_cashpay_init_pyment_action();



?>
            <div class="form-row form-row-wide">
                <p><?php echo $error_message; ?></p>
                <label>ادخل رقم التلفون <span class="required">*</span></label>
                <input name="TargetMSISDN" id="TargetMSISDN"  class='sb_validation' type="text" autocomplete="off" style="width: 100%;" required="required">
                <img src="<?php echo plugin_dir_url(__FILE__) ?>images/loader.gif" class="hma_cashpay_confirm_account_ajax_loader">
                <span class="hma_cashpay_confirm_account_msg"></span>
            </div>
            <div class="clear"></div>
            <div class="form-row form-row-wide form-row-CustomerCashPayCode">
                <label>كود شراء الاونلاين <span class="required">*</span></label>
                <input name="CustomerCashPayCode" class='sb_validation' id="CustomerCashPayCode" type="text" autocomplete="off" style="width: 100%;" required="required">
                <p>ستصلك رساله نصيه الى رقم جوالكم.</p>
                <input type="button" value="Send OTP">
            </div>

            <div class="clear"></div>
            <div class='sb_parent' style='display:none'>
                <div class="form-row form-row-wide form-row-CustomerCashPayCode">
                    <label>OTP <span class="required">*</span></label>
                    <input name="OTP" id="OTP" type="text" autocomplete="off" style="width: 100%;" required="required">

                </div>
                <div class="clear"></div>
                <div class="form-row form-row-wide form-row-CustomerCashPayCode">
                   <!--  <button type="button" class="button" id="verify_otp_place_order" data-response=''>Verify OTP & Place Order</button> -->
                    <input type="button" class="button" id="verify_otp_place_order" data-response='' value="Verify OTP & Place Order">
                </div>
            </div>

            <div class="clear"></div>
<?php
            do_action('woocommerce_credit_card_form_end', $this->id);

            echo '<div class="clear"></div></fieldset>';
        }
        /*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom CashPay form
		 */
        public function payment_scripts()
        {
            wp_enqueue_script('cashpay', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery'], wp_rand(), true);
            wp_localize_script('cashpay', 'cashpay', [
                'ajax_url' => admin_url('admin-ajax.php'),
            ]);
            wp_enqueue_style('cashpay', plugin_dir_url(__FILE__) . 'css/style.css', [], wp_rand(), 'all');
        }

        /*
		 * We're processing the payments here, everything about it is in Step 5
		 */
        public function process_payment($order_id)
        {

            // if (isset($_POST['CustomerCashPayCode']) && $_POST['CustomerCashPayCode'] == "") {
            //     wc_add_notice("Confirm code is required", 'error');
            // }
            global $woocommerce;

            // we need it to get any order detailes
            $order = wc_get_order($order_id);

            /*
              * Array with parameters for API interaction
             */
            $args = array();

            /*
             * Your API interaction could be built with wp_remote_post()
            */


            $status = get_user_meta(get_current_user_id(), 'payment_status', true);
            if($status == 200){   
                echo 'status is 200';
                update_post_meta($order_id, 'cashpay_payment_response', $payment);
                // we received the payment
                $order->payment_complete();
                $order->reduce_order_stock();
                // some notes to customer (replace true with false to make it private)
                $order->add_order_note('Hey, your order is paid! Thank you!', true);
                // Empty cart
                $woocommerce->cart->empty_cart();

                // Redirect to the thank you page
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
                
            }
            else {
                wc_add_notice($payment->msg, 'error');
                return;
            }     
        }

        /*
		* In case you need a webhook, like PayPal IPN etc
		*/
        public function webhook()
        {

            $order = wc_get_order($_GET['id']);
            $order->payment_complete();
            $order->reduce_order_stock();
            update_option('webhook_debug', $_GET);
        }

        
        public function hma_cashpay_errors($error_code = 0)
        {
            $errors = [
                6000 => "Sys permission",
                6001 => "duplicated timestamp",
                6002 => "obsolete timestamp",
                6003 => "You have crossed the threshold",
                6004 => "This process cannot be completed",
                6005 => "Verification code not sent",
                6006 => "The customer not allowed to pay online",
                6007 => "invalid customer",
                6008 => "you must initial payment first, (Either the previous step-1 has expired or you did not perform step 1 at all).",
                6009 => "exceeded the number of attempts",
                6010 => "invalid input (OTP), you only have {n} attempt till the process will be cancelled",
                6011 => "the customer is not authorized to do this type of operation",
                6012 => "unauthorized request",
                6013 => "There is no operation with the entered number (timeout cases)",
                6014 => "you are not authorized to perform this operation",
                6015 => "invalid MD5",
                6016 => "invalid CustomerCashPayCode",
                6017 => "invalid encryption format(encPassword)",
                6018 => "duplicated RequestId",
                6019 => "Invalid a mandatory header key",
                6020 => "The currencyId not allowed.",
                6021 => "Exceeding the allowed limit for the payment process",
                6022 => "Expired password, you should change password",
                6023 => "Invalid credentials(username/password) for requester",
                9999 => "Some other error",
            ];
            if ($error_code > 0) {
                return $errors[$error_code];
            } else {
                return $errors;
            }
        }
    }
}
add_filter('https_ssl_verify', '__return_false');
add_action('wp_ajax_nopriv_hma_cashpay_init_pyment_action',  'hma_cashpay_init_pyment_action');
add_action('wp_ajax_hma_cashpay_init_pyment_action', 'hma_cashpay_init_pyment_action');

function hma_cashpay_init_pyment_action()
{
    if (isset($_POST['TargetMSISDN']) && $_POST['TargetMSISDN'] !== "" && isset($_POST['CustomerCashPayCode']) && $_POST['CustomerCashPayCode'] !== "") {
        $WC_CashPayGateway = new WC_CashPayGateway();
        $unixtimestamp = round(microtime(true) * 1000);
        $requestId = mt_rand(1,999999999999999);
        $TargetMSISDN = $_POST['TargetMSISDN'];
        $CustomerCashPayCode = $_POST['CustomerCashPayCode'];
        $args = array(
            "method" => 'POST',
            "headers" => array(
                "encPassword" => $WC_CashPayGateway->encPassword,
                "unixtimestamp" => $unixtimestamp,
            ),
            "body" => array(
                "RequestID" => "$requestId",
                "UserName"  => $WC_CashPayGateway->UserName,
                "SpId"      => $WC_CashPayGateway->to_SpId,
                "MDToken"   => md5($WC_CashPayGateway->to_SpId . $WC_CashPayGateway->UserName . $unixtimestamp),
                "TargetMSISDN" => $TargetMSISDN,
                "CustomerCashPayCode" => $CustomerCashPayCode,
                "Amount" => floatval(WC()->cart->total),
                "CurrencyId" => $WC_CashPayGateway->CurrencyId,
                "Desc" =>  "pay",
            ),
            'sslverify' => false,
            'timeout' => 60,
        );

        $response = wp_remote_request($WC_CashPayGateway->apiurl . '/CashPay/InitPayment', $args);
        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response));
            if(!empty($body->TransactionRef)){
                $trans_ref = $body->TransactionRef;
                $error_array = array(
                    'res' => 200,
                    'res_msg' => 'success',
                    'trans_code' => $trans_ref
                );
            }
            else{
                $error_code = $body->ResultMessage;
                $error_message =$WC_CashPayGateway->hma_cashpay_errors($body->ResultMessage);
                $error_array = array(
                    'res' => 201,
                    'error_code' => $error_code,
                    'res_msg' => $error_message,
                );
            }
        }
        else{
            $error_array = array(
                'res' => 201,
                'error_code' => 6002,
                'res_msg' => 'API Call issue 6002',

            );
        }
    }
    else{
        $error_array = array(
            'res' => 201,
            'error_code' => 6003,
            'res_msg' => 'API Call issue 6003',
        );
    }
    echo json_encode($error_array, true);
    exit();
}
add_action('wp_ajax_nopriv_hma_cashpay_confirm_pyment_action',  'hma_cashpay_confirm_pyment_action');
add_action('wp_ajax_hma_cashpay_confirm_pyment_action', 'hma_cashpay_confirm_pyment_action');

function hma_cashpay_confirm_pyment_action(){
    $TransactionRef = $_POST['response'];
    $otp = $_POST['otp'];
    $WC_CashPayGateway = new WC_CashPayGateway();
    $unixtimestamp = round(microtime(true) * 1000);
    $requestId = mt_rand(1,999999999999999);
    $args = array(
        "method" => 'POST',
        "headers" => array(
            "encPassword" => $WC_CashPayGateway->encPassword,
            "unixtimestamp" => $unixtimestamp,
        ),
        "body" => array(
            "RequestID" => "$requestId",
            "UserName"  => $WC_CashPayGateway->UserName,
            "SpId"      => $WC_CashPayGateway->to_SpId,
            "MDToken"   => md5($WC_CashPayGateway->to_SpId . $WC_CashPayGateway->UserName . $unixtimestamp),
            "TransactionRef" => $TransactionRef,
            "TRCode" => md5($TransactionRef.$otp),
        )
    );
    $response = wp_remote_request($WC_CashPayGateway->apiurl . '/CashPay/ConfirmPayment', $args);

    $res = json_decode($response['body'], true);
    if($res['ResultCode'] == 1 || $res['ResultCode'] == '1'){
        echo '<pre>';
        print_r($res);
        echo '</pre>';

        echo 200;
        update_user_meta(get_current_user_id(),'payment_status', 200);
    }
    else{
        echo '<pre>';
        print_r($res);
        echo '</pre>';
        update_user_meta(get_current_user_id(),'payment_status', 201);
        echo 201;
        echo 'payment-status-error';
    }
    die();
}