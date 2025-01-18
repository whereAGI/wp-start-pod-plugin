<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Include WordPress functionality
require_once('../../../wp-load.php');

// Function to handle the billing process
function handle_billing_for_deployed_servers() {
    $args = array(
        'status' => 'wc-processing',
        'limit' => -1,
    );
    $orders = wc_get_orders($args);
    
    $wallet_balance_currency = get_woocommerce_currency();
    echo '<p>Wallet currency: ' . esc_html($wallet_balance_currency) . '</p>';

    foreach ($orders as $order) {
        $order_id = $order->get_id();
        $last_bill_time = get_post_meta($order_id, '_last_bill_time', true);
        $current_time = current_time('timestamp');
        $order_currency = $order->get_currency();
        
        $product_price = $order->get_total();
        $product_price_og = get_post_meta($order_id, '_product_price_og', true);

        if($order_currency == 'INR'){
            $product_price = $product_price / 83;
            $product_price_og = $product_price_og / 83;
        }

        $current_time = strtotime(current_time('mysql'));
        $last_bill_time = strtotime($last_bill_time);
        $server_usage_minutes = max(0, ($current_time - $last_bill_time) / 60);
        
        $session_price = $server_usage_minutes * ($product_price_og / 60);

        $user_id = $order->get_user_id(); 
        $wallet_balance = woo_wallet()->wallet->get_wallet_balance($user_id, false);
        
        if($wallet_balance_currency == 'INR'){
            $session_price = $session_price * 83;
        }

        if ($wallet_balance >= 2 * $session_price) {
            woo_wallet()->wallet->debit($user_id,$session_price);

            if($order_currency == 'USD'){
                $session_price = $session_price / 83;
            }
            $order->set_total($order->get_total() + $session_price);
            $order->save();
            
            if ($server_usage_minutes >= 1) {
                update_post_meta($order_id, '_last_bill_time', date('Y-m-d H:i:s', $current_time));
            }
            
        } else {
            // Terminate server via API call
            $pod_id = get_post_meta($order_id, '_pod_id', true);
            $payload = json_encode([
                "query" => "mutation { podTerminate(input: {podId: \"$pod_id\"}) }"
            ]);

            $response = wp_remote_post('https://api.runpod.io/graphql?api_key=RWGXQAE0LJ773QF4SQGFAM824TASKULPDNT8IXL6', array(
                'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                'body' => $payload,
                'method' => 'POST',
                'data_format' => 'body',
            ));

            $current_time = current_time('mysql');
            update_post_meta($order_id, '_server_stop_time', $current_time);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['data']['podTerminate']) || $data['data']['podTerminate'] === null) {
                    $order->update_status('wc-cancelled', 'Server terminated due to insufficient funds.');
                }
            }
        }
    }
}

// Run the billing function
handle_billing_for_deployed_servers();
