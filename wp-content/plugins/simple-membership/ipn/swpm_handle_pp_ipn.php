<?php

include_once('swpm_handle_subsc_ipn.php');

class swpm_paypal_ipn_handler {

    var $last_error;                 // holds the last error encountered
    var $ipn_log = false;                    // bool: log IPN results to text file?
    var $ipn_log_file;               // filename of the IPN log
    var $ipn_response;               // holds the IPN response from paypal
    var $ipn_data = array();         // array contains the POST values for IPN
    var $fields = array();           // array holds the fields to submit to paypal
    var $sandbox_mode = false;

    function swpm_paypal_ipn_handler()
    {
        $this->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
      	$this->last_error = '';
      	$this->ipn_log_file = 'ipn_handle_debug_swpm.log';
      	$this->ipn_response = '';
    }

    function swpm_validate_and_create_membership()
    {
        // Check Product Name , Price , Currency , Receivers email ,
        $error_msg = "";

        // Read the IPN and validate
        $gross_total = $this->ipn_data['mc_gross'];
        $transaction_type = $this->ipn_data['txn_type'];
        $txn_id = $this->ipn_data['txn_id'];        
    	$payment_status = $this->ipn_data['payment_status'];
        
        //Check payment status
    	if (!empty($payment_status))
    	{
            if ($payment_status == "Denied") {
                $this->debug_log("Payment status for this transaction is DENIED. You denied the transaction... most likely a cancellation of an eCheque. Nothing to do here.", false);
                return false;
            }
            if ($payment_status == "Canceled_Reversal") {
                $this->debug_log("This is a dispute closed notification in your favour. The plugin will not do anyting.", false);
                return true;
            }
            if ($payment_status != "Completed" && $payment_status != "Processed" && $payment_status != "Refunded" && $payment_status != "Reversed")
            {
                $error_msg .= 'Funds have not been cleared yet. Transaction will be processed when the funds clear!';
                $this->debug_log($error_msg,false);
                return false;
            }
    	}

        //Check txn type
        if ($transaction_type == "new_case") {
            $this->debug_log('This is a dispute case. Nothing to do here.', true);
            return true;
        }
        
        $custom = $this->ipn_data['custom'];
        $delimiter = "&";
        $customvariables = array();
        $namevaluecombos = explode($delimiter, $custom);
        foreach ($namevaluecombos as $keyval_unparsed)
        {
            $equalsignposition = strpos($keyval_unparsed, '=');
            if ($equalsignposition === false)
            {
                $customvariables[$keyval_unparsed] = '';
                continue;
            }
            $key = substr($keyval_unparsed, 0, $equalsignposition);
            $value = substr($keyval_unparsed, $equalsignposition + 1);
            $customvariables[$key] = $value;
        }
        
        //Handle refunds
        if ($gross_total < 0)
        {
            // This is a refund or reversal
            $this->debug_log('This is a refund notification. Refund amount: '.$gross_total,true);
            swpm_handle_subsc_cancel_stand_alone($this->ipn_data,true);            
            return true;
        }
        if (isset($this->ipn_data['reason_code']) && $this->ipn_data['reason_code'] == 'refund'){
            $this->debug_log('This is a refund notification. Refund amount: '.$gross_total,true);
            swpm_handle_subsc_cancel_stand_alone($this->ipn_data,true);            
            return true;            
        }

        if (($transaction_type == "subscr_signup"))
        {
            $this->debug_log('Subscription signup IPN received... nothing to do here(handled by the subscription IPN handler)',true);
            // Code to handle the signup IPN for subscription
            $subsc_ref = $customvariables['subsc_ref'];

            if (!empty($subsc_ref))
            {
                $this->debug_log('swpm integration is being used... creating member account...',true);
                $swpm_id = $customvariables['swpm_id'];
                swpm_handle_subsc_signup_stand_alone($this->ipn_data,$subsc_ref,$this->ipn_data['subscr_id'],$swpm_id);
                //Handle customized subscription signup
            }
            return true;
        }
        else if (($transaction_type == "subscr_cancel") || ($transaction_type == "subscr_eot") || ($transaction_type == "subscr_failed"))
        {
            // Code to handle the IPN for subscription cancellation
            swpm_handle_subsc_cancel_stand_alone($this->ipn_data);
            $this->debug_log('Subscription cancellation IPN received... nothing to do here(handled by the subscription IPN handler)',true);
            return true;
        }
        else
        {
            $cart_items = array();
            $this->debug_log('Transaction Type: Buy Now/Subscribe',true);
            $item_number = $this->ipn_data['item_number'];
            $item_name = $this->ipn_data['item_name'];
            $quantity = $this->ipn_data['quantity'];
            $mc_gross = $this->ipn_data['mc_gross'];
            $mc_currency = $this->ipn_data['mc_currency'];

            $current_item = array(
            'item_number' => $item_number,
            'item_name' => $item_name,
            'quantity' => $quantity,
            'mc_gross' => $mc_gross,
            'mc_currency' => $mc_currency,
            );

            array_push($cart_items, $current_item);
        }

        $counter = 0;
        foreach ($cart_items as $current_cart_item)
        {
            $cart_item_data_num = $current_cart_item['item_number'];
            $cart_item_data_name = trim($current_cart_item['item_name']);
            $cart_item_data_quantity = $current_cart_item['quantity'];
            $cart_item_data_total = $current_cart_item['mc_gross'];
            $cart_item_data_currency = $current_cart_item['mc_currency'];
            if(empty($cart_item_data_quantity)){
                $cart_item_data_quantity = 1;
            }            
            $this->debug_log('Item Number: '.$cart_item_data_num,true);
            $this->debug_log('Item Name: '.$cart_item_data_name,true);
            $this->debug_log('Item Quantity: '.$cart_item_data_quantity,true);
            $this->debug_log('Item Total: '.$cart_item_data_total,true);
            $this->debug_log('Item Currency: '.$cart_item_data_currency,true);

            //Get the button id
            $button_id = $cart_item_data_num;//Button id is the item number.

            //*** Handle Membership Payment ***
            //--------------------------------------------------------------------------------------
            // ========= Need to find the (level ID) in the custom variable ============
            $subsc_ref = $customvariables['subsc_ref'];//Membership level ID
            $this->debug_log('Membership payment paid for membership level ID: '.$subsc_ref,true);
            if (!empty($subsc_ref))
            {
                $swpm_id = "";
                if(isset($customvariables['swpm_id'])){
                    $swpm_id = $customvariables['swpm_id'];
                }
                if ($transaction_type == "web_accept")
                {
                    $this->debug_log('swpm integration is being used... creating member account...',true);
                    swpm_handle_subsc_signup_stand_alone($this->ipn_data,$subsc_ref,$this->ipn_data['txn_id'],$swpm_id);
                }
                else if($transaction_type == "subscr_payment"){
                    //swpm_update_member_subscription_start_date_if_applicable($this->ipn_data);
                }
            }
            else
            {
                $this->debug_log('Membership level ID is missing in the payment notification! Cannot process this notification.',false);
            }
            //== End of Membership payment handling ==
            $counter++;
        }

        /*** Do Post payment operation and cleanup ***/
        //Save the transaction data
        $this->debug_log('Saving transaction data to the database table.', true);
        $this->ipn_data['gateway'] = 'paypal';
        $this->ipn_data['status'] = $this->ipn_data['payment_status'];
        SwpmTransactions::save_txn_record($this->ipn_data, $cart_items);
        $this->debug_log('Transaction data saved.', true);
        
        do_action('swpm_paypal_ipn_processed', $this->ipn_data);
        return true;
    }

    function swpm_validate_ipn()
    {
        //Generate the post string from the _POST vars aswell as load the _POST vars into an arry
        $post_string = '';
        foreach ($_POST as $field=>$value) {
            $this->ipn_data["$field"] = $value;
            $post_string .= $field.'='.urlencode(stripslashes($value)).'&';
        }

        $this->post_string = $post_string;
        $this->debug_log('Post string : '. $this->post_string,true);

        //IPN validation check
        if($this->validate_ipn_using_remote_post()){
            //We can also use an alternative validation using the validate_ipn_using_curl() function
            return true;
        } else {
            return false;
        }
        
   }

    function validate_ipn_using_remote_post(){
        $this->debug_log( 'Checking if PayPal IPN response is valid', true);
        
        // Get received values from post data
        $validate_ipn = array( 'cmd' => '_notify-validate' );
        $validate_ipn += wp_unslash( $_POST );

        // Send back post vars to paypal
        $params = array(
                'body'        => $validate_ipn,
                'timeout'     => 60,
                'httpversion' => '1.1',
                'compress'    => false,
                'decompress'  => false,
                'user-agent'  => 'Simple Membership Plugin',
        );

        // Post back to get a response.
        $connection_url = $this->sandbox_mode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
        $this->debug_log('Connecting to: ' . $connection_url, true);
        $response = wp_safe_remote_post( $connection_url, $params );

        //The following two lines can be used for debugging
        //$this->debug_log( 'IPN Request: ' . print_r( $params, true ) , true);
        //$this->debug_log( 'IPN Response: ' . print_r( $response, true ), true);

        // Check to see if the request was valid.
        if ( ! is_wp_error( $response ) && strstr( $response['body'], 'VERIFIED' ) ) {
            $this->debug_log('IPN successfully verified.', true);
            return true;
        }

        // Invalid IPN transaction. Check the log for details.
        $this->debug_log('IPN validation failed.', false);
        if ( is_wp_error( $response ) ) {
            $this->debug_log('Error response: ' . $response->get_error_message(), false);
        }
        return false;        
    }
    
    function debug_log($message,$success,$end=false)
    {
        SwpmLog::log_simple_debug($message, $success, $end);
    }
}

// Start of IPN handling (script execution)

$ipn_handler_instance = new swpm_paypal_ipn_handler();

$settings = SwpmSettings::get_instance();
$debug_enabled = $settings->get_value('enable-debug');
if(!empty($debug_enabled))//debug is enabled in the system
{
	$debug_log = "log.txt"; // Debug log file name
	echo 'Debug logging is enabled. Check the '.$debug_log.' file for debug output.';
	$ipn_handler_instance->ipn_log = true;
	$ipn_handler_instance->ipn_log_file = $debug_log;
	if(empty($_POST))
	{
            $ipn_handler_instance->debug_log('This debug line was generated because you entered the URL of the ipn handling script in the browser.',true,true);
            exit;
	}
}

$sandbox_enabled = $settings->get_value('enable-sandbox-testing');
if(!empty($sandbox_enabled)) // Sandbox testing enabled
{
    $ipn_handler_instance->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    $ipn_handler_instance->sandbox_mode = true;
}

$ipn_handler_instance->debug_log('Paypal Class Initiated by '.$_SERVER['REMOTE_ADDR'],true);

// Validate the IPN
if ($ipn_handler_instance->swpm_validate_ipn())
{
    $ipn_handler_instance->debug_log('Creating product Information to send.',true);

    if(!$ipn_handler_instance->swpm_validate_and_create_membership()){
        $ipn_handler_instance->debug_log('IPN product validation failed.',false);
    }
}
$ipn_handler_instance->debug_log('Paypal class finished.',true,true);
