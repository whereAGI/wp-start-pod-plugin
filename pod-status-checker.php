<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include WordPress functionality
require_once('../../../wp-load.php');

// Move the get_pod_status function and related AJAX actions here
function get_pod_status() {
    check_ajax_referer('wp_rest', 'nonce');
    $pod_id = isset($_POST['pod_id']) ? sanitize_text_field($_POST['pod_id']) : '';
    
    if (empty($pod_id)) {
        wp_send_json_error('No pod ID provided');
    }

    // Make API call to RunPod to check status
    $query = "query Pod {
        pod(input: {podId: \"$pod_id\"}) {
            id
            name
            desiredStatus
            runtime {
                uptimeInSeconds
                ports {
                    ip
                    isIpPublic
                    privatePort
                    publicPort
                }
            }
        }
    }";

    $response = wp_remote_post('https://api.runpod.io/graphql?api_key=RWGXQAE0LJ773QF4SQGFAM824TASKULPDNT8IXL6', array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode(['query' => $query]),
        'method' => 'POST',
        'data_format' => 'body',
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Failed to contact RunPod API');
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['data']['pod'])) {
        wp_send_json_success($data['data']['pod']);
    } else {
        wp_send_json_error('Failed to retrieve pod status');
    }
}
add_action('wp_ajax_get_pod_status', 'get_pod_status');
add_action('wp_ajax_nopriv_get_pod_status', 'get_pod_status');