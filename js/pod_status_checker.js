jQuery(document).ready(function($) {
    var orderId = podStatusData.orderId;
    var finalUrl = podStatusData.finalUrl;
    var podId = podStatusData.podId;
    var checkCount = 0;
    var maxChecks = 40; // 20 minutes (30 seconds * 40)
    
    function checkPodStatus() {
        if (checkCount >= maxChecks) {
            $('#pod-status-container').html('<p>Status check timed out. Please refresh the page or contact support.</p>');
            clearInterval(podStatusInterval);
            return;
        }
        
        checkCount++;
        $.ajax({
            url: podStatusData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_pod_status',
                pod_id: podId,
                nonce: podStatusData.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateStatusDisplay(response.data);
                } else {
                    console.error('Status check failed:', response);
                    $('#pod-status-container').html('<p>Error checking pod status. Will retry...</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                $('#pod-status-container').html('<p>Connection error. Will retry...</p>');
            }
        });
    }

    function updateStatusDisplay(pod) {
        var container = $('#pod-status-container');
        
        if (!pod || pod.desiredStatus === 'TERMINATED') {
            container.html('<p>Pod has been terminated</p>');
            clearInterval(podStatusInterval);
            return;
        }

        if (!pod.runtime) {
            container.html(`
                <p>Pod is starting up...</p>
                <small>This may take a few minutes</small>
            `);
            return;
        }

        var portsReady = pod.runtime.ports && pod.runtime.ports.some(p => p.isIpPublic);
        if (!portsReady) {
            container.html(`
                <p>Configuring network access...</p>
                <small>Almost there!</small>
            `);
            return;
        }

        container.html(`
            <div class="pod-ready">
                <p>Your application is ready!</p>
                <a href="${finalUrl}" class="button" target="_blank">Launch Application</a>
                <small>Uptime: ${Math.floor(pod.runtime.uptimeInSeconds / 60)} minutes</small>
            </div>
        `);
        
        clearInterval(podStatusInterval);
    }

    var podStatusInterval = setInterval(checkPodStatus, 30000);
    checkPodStatus(); // Initial check
});