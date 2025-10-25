    <!-- CoreUI and necessary plugins-->
    <script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@4.2.0/dist/js/coreui.bundle.min.js"></script>
    
    <!-- Simplebar for scrollbars -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@5.3.8/dist/simplebar.min.js"></script>
    
    <!-- jQuery (if needed for DataTables or other plugins) -->
    <?php if (isset($useJQuery) && $useJQuery || isset($useDataTables) && $useDataTables): ?>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <?php endif; ?>
    
    <!-- DataTables JS (if needed) -->
    <?php if (isset($useDataTables) && $useDataTables): ?>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('table.dataTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search..."
                }
            });
        });
    </script>
    <?php endif; ?>
    
    <!-- Additional page-specific JS -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Custom JS -->
    <script>
        // Global functions
        
        // Show toast notification
        function showToast(message, type = 'info') {
            const toastHTML = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-coreui-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toastElement = toastContainer.lastElementChild;
            const toast = new coreui.Toast(toastElement);
            toast.show();
            
            toastElement.addEventListener('hidden.coreui.toast', () => {
                toastElement.remove();
            });
        }
        
        // Confirm delete action
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
        
        // Format date
        function formatDate(dateString, format = 'dd/mm/yyyy') {
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            
            if (format === 'dd/mm/yyyy') {
                return `${day}/${month}/${year}`;
            } else if (format === 'yyyy-mm-dd') {
                return `${year}-${month}-${day}`;
            }
            return dateString;
        }
        
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new coreui.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-coreui-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new coreui.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
