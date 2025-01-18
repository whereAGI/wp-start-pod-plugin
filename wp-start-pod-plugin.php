<?php
/*
Plugin Name: WP Start Pod Plugin
Description: Plugin to start pods on RunPod.io after WooCommerce payment confirmation.
Version: 1.0
Author: Your Name
Text Domain: wp-start-pod-plugin
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//---------------------------------------------------------------------------------------------------------------------------------------------------------

function call_api_after_successful_payment($order_id) {
    if (!$order_id) return;

    // Load the order
    $order = wc_get_order($order_id);

    // Check if order is loaded
    if (!$order) return;

    // Get items from the order
    $items = $order->get_items();

    foreach ($items as $item_id => $item) {
        // Get the product
        $product = $item->get_product();

        // Check if product is loaded
        if (!$product) continue;

        // Get the mutation attribute, assume it is a product attribute
        $mutation_payload = $product->get_attribute('mutation');

        // Get the mutation attribute, assume it is a product attribute
        $web_app_url = $product->get_attribute('URL');
        update_post_meta($order_id, '_web_app_url', $web_app_url);  // Save the link to start app from each product into post meta
		
		//SET Product price to be able to calculate refunds in case of error
        $product_price = $order->get_total(); // This gets the total value of the order
		update_post_meta($order_id, '_product_price_og', $product_price);  // Save order price to retrive later
		
        // If the mutation attribute is not empty, proceed with the API call
        if (!empty($mutation_payload)) {
            // Decode the mutation payload since we expect it to be a JSON string
            $payload = $mutation_payload;

            // Define the endpoint
            $endpoint = 'https://api.runpod.io/graphql?api_key=RWGXQAE0LJ773QF4SQGFAM824TASKULPDNT8IXL6';

            // Perform the API call
            $response = wp_remote_post($endpoint, array(
                'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                'body' => $payload,
                'method' => 'POST',
                'data_format' => 'body',
            ));

			// Check for WP error or a custom error in the API response
			if (is_wp_error($response)) {
                // Handle a WordPress-specific error (WP_Error object)
                $error_message = $response->get_error_message();
                update_post_meta($order_id, '_api_response_error', $error_message);
                update_post_meta($order_id, '_api_call', $payload);
            
            } else {
                // No WP_Error, proceed to parse the API response body
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
            
                // Check for errors within the decoded JSON response
                if (isset($data['data']) && $data['data']['podFindAndDeployOnDemand'] === null) {
                    $api_error = "Unfortunately due to high demand your selected instance is unavailable, the amount deducted will be refunded instantly. Please try again later";
                    update_post_meta($order_id, '_api_response_error', $api_error);
                    $order->update_status('wc-failed', 'Server was not available');
                    woo_wallet()->wallet->credit($user_id,$product_price);                    
                    break;
                } elseif (isset($data['data']['podFindAndDeployOnDemand']['id'])) {
                    // Successful response - extract the pod ID
                    $pod_id = $data['data']['podFindAndDeployOnDemand']['id'];
                    update_post_meta($order_id, '_pod_id', $pod_id);

                    // Server start time
                    $time_plus_ten_minutes = date('Y-m-d H:i:s', strtotime('+10 minutes', current_time('timestamp')));
                    update_post_meta($order_id, '_server_start_time', $time_plus_ten_minutes);
            
                    // Save Last billed time
                    $time_plus_sixty_minutes = date('Y-m-d H:i:s', strtotime('+70 minutes', current_time('timestamp')));
                    update_post_meta($order_id, '_last_bill_time', $time_plus_sixty_minutes);
                    
                    // Set initial server status
                    $current_time = "Your server is active";
                    update_post_meta($order_id, '_server_stop_time', $current_time);
                } else {
                    update_post_meta($order_id, '_api_response_error', 'Unexpected API response');
                    $order->update_status('wc-failed', 'Server was not available');
                    woo_wallet()->wallet->credit($user_id,$product_price);
                }
            
                update_post_meta($order_id, '_api_response', $body); 
                update_post_meta($order_id, '_api_call', $payload); 
            }
            break;
        }
    }
}
add_action('woocommerce_payment_complete', 'call_api_after_successful_payment');

// Rest of your functions here...
