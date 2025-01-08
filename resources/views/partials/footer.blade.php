<footer class="footer">
    <?php
    // Get the current year
    $currentYear = date('Y');
    ?>
    © <?php echo $currentYear; ?> NUT HR <span class="d-none d-sm-inline-block"> - Developed by <a target="_blank"
            href="javascript:;">Nordic Unique Travels</a></span>.
</footer>
</div>
<!-- ============================================================== -->
<!-- End Right content here -->
<!-- ============================================================== -->

</div>
<!-- END wrapper -->

<!-- jQuery  -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/metismenu.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.slimscroll.js') }}"></script>
<script src="{{ asset('assets/js/waves.min.js') }}"></script>

<script src="{{ asset('plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>

<!--Morris Chart-->
<script src="{{ asset('plugins/morris/morris.min.js') }}"></script>
<script src="{{ asset('assets/pages/morris.init.js') }}"></script>

<script src="{{ asset('plugins/raphael/raphael.min.js') }}"></script>

<script src="{{ asset('assets/pages/dashboard.init.js') }}"></script>
<!-- Required datatable js -->
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
<!-- Buttons examples -->
<script src="{{ asset('plugins/datatables/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/jszip.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/pdfmake.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/vfs_fonts.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.colVis.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/responsive.bootstrap4.min.js') }}"></script>

{{-- <script src="{{ asset('assets/pages/datatables.init.js?v=1.0') }}"></script> --}}
<script src="{{ asset('plugins/alertify/js/alertify.js') }}"></script>
<script src="{{ asset('assets/pages/alertify-init.js') }}"></script>

<!-- App js -->
<script src="{{ asset('assets/js/app.js') }}"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<style>
    .daterangepicker {
        background-color: #2b3940;
        border: 1px solid #444;
    }
    
    .daterangepicker .calendar-table {
        background-color: #2b3940;
        border: none;
    }
    
    .daterangepicker td.off, 
    .daterangepicker td.off.in-range, 
    .daterangepicker td.off.start-date, 
    .daterangepicker td.off.end-date {
        background-color: #223;
        color: #666;
    }
    
    .daterangepicker td.available:hover, 
    .daterangepicker th.available:hover {
        background-color: #375a7f;
    }
    
    .daterangepicker td.active, 
    .daterangepicker td.active:hover {
        background-color: #375a7f;
    }
    
    .daterangepicker td.in-range {
        background-color: #375a7f50;
        color: #fff;
    }
    
    .daterangepicker .calendar-table .next span, 
    .daterangepicker .calendar-table .prev span {
        border-color: #fff;
    }
    
    .daterangepicker .drp-buttons {
        border-top: 1px solid #444;
    }
    
    .daterangepicker select.hourselect, 
    .daterangepicker select.minuteselect, 
    .daterangepicker select.secondselect, 
    .daterangepicker select.ampmselect {
        background: #2b3940;
        border: 1px solid #444;
        color: #fff;
    }
    
    .daterangepicker td.available, 
    .daterangepicker th.available {
        color: #fff;
    }
    
    .daterangepicker .calendar-table .next,
    .daterangepicker .calendar-table .prev {
        background: transparent;
    }
</style>
<script>

@if(Auth::user()->role == 'admin')
$(document).ready(function() {
    $('#datatable').DataTable();

    //Buttons examples
    var table = $('#datatable-buttons').DataTable({
        lengthChange: true,
        searching: true,
        "pageLength": 100,
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print' 
        ],
        paging: false,
        ordering: false,
        info: false
    });

    table.buttons().container()
            .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
    } );
    
    @else

    $(document).ready(function() {
    $('#datatable').DataTable();

    //Buttons examples
    var table = $('#datatable-buttons').DataTable({
        lengthChange: true,
        searching: true,
        "pageLength": 100,
        paging: false,
        ordering: false,
        info: false
    });

    table.buttons().container()
            .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
    } );
    @endif
</script>
</body>

</html>
