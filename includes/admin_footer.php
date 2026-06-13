    </div> <!-- main-content -->
</div> <!-- dashboard-wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Custom JS -->
<script src="../assets/js/script.js"></script>

<script>
// Initialize DataTables
$(document).ready(function() {
    $('.data-table').DataTable({
        "pageLength": 10,
        "ordering": true,
        "searching": true,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });
});

// Confirm delete
function confirmDelete(name) {
    return confirm('Are you sure you want to delete ' + name + '?');
}
</script>

</body>
</html>