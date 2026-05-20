</div> <!-- End of container-fluid (opened in header.php) -->

        <!-- Footer -->
        <footer class="footer mt-auto py-3 bg-white border-top no-print">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    &copy; <?= date('Y'); ?> <strong>Liquor Store RW</strong>. All Rights Reserved.
                </span>
                <span class="text-muted small d-none d-md-inline">
                    System Version 1.2 | <i class="fas fa-shield-alt text-success"></i> Secure Session
                </span>
            </div>
        </footer>

    </div> <!-- End of #content (opened in header.php) -->
</div> <!-- End of .wrapper (opened in header.php) -->

<!-- Scripts -->

<!-- 1. Bootstrap 5 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- 2. Chart.js (Loaded globally for reports and dashboard) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- 3. jQuery (Optional, but useful for some legacy plugins) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- 4. Global System Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // Sidebar Toggle Functionality
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');

        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function() {
                // Toggle 'active' class on sidebar and content
                sidebar.classList.toggle('active');
                content.classList.toggle('active');
            });
        }

        // Initialize all Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

    });

    /**
     * Utility Function to format numbers as RWF
     * Usage: formatCurrency(5000) -> 5,000 RWF
     */
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-RW').format(amount) + ' RWF';
    }
</script>

</body>
</html>