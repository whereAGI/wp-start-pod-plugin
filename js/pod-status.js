jQuery(document).ready(function($) {
    var podId = pod_status_data.pod_id;
    var finalUrl = pod_status_data.final_url;
    var ajaxUrl = pod_status_data.ajax_url;
    console.log(pod_status_data);

    function updatePodStatus() {
        console.log('Making AJAX call...');
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_pod_status',
                pod_id: podId
            },
            success: function(response) {
                console.log('AJAX success:', response); 
                console.log(finalUrl);

                // Use === for strict comparison
                if (response === 'false') { 
                    console.log('Response is false');
                    $('#pod-status-container').text("Session Terminated"); 
                } else if (response === '"loading"') { 
                    console.log('Response is loading');
                    $('#pod-status-container').text("Your app is being set up ...");
                } else if (response === 'true') {
                    console.log('Response is true');
                    // Directly inject the finalUrl HTML
                    $('#pod-status-container').html(finalUrl);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('AJAX error:', textStatus, errorThrown); 
                $('#pod-status-container').html("Error checking pod status.");
            }
        });
    }

    // Initial status check
    updatePodStatus();

    // Update every 30 seconds
    setInterval(updatePodStatus, 30000); 
});