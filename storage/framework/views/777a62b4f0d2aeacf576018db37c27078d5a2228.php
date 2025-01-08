<?php $__env->startSection('content'); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <h4 class="page-title">Monthly Report for <?php echo e(\Carbon\Carbon::parse($monthYear)->format('F Y')); ?></h4>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table">
                                    <table id="datatable-buttons" class="table table-striped table-bordered"
                                        style="border-collapse: collapse; border-spacing: 0; width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Full Name</th>
                                                <th>Guide Name</th>
                                                <th>Work Period</th>
                                                <th>Work Hours</th>
                                                <th>Work hours ONLY with holiday extras</th>
                                                <th>Night supplement hours(20-6)</th>
                                                <th>Night supplement ONLY with holiday extras</th>
                                                <th>Special Holiday Bonus - Day</th>
                                                <th>Special Holiday Bonus with holiday extras - Day</th>
                                                <th>Special Holiday Bonus - Night</th>
                                                <th>Special Holiday Bonus with holiday extras - Night</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($result['tourGuide']->full_name); ?></td>
                                                    <td><?php echo e($result['tourGuide']->name); ?></td>
                                                    <td><?php echo e($result['period']); ?></td>
                                                    <td><?php echo e(number_format($result['totalNormalHours'], 2)); ?></td>
                                                    <td><?php echo e(number_format($result['totalHolidayHours'], 2)); ?></td>
                                                    <td><?php echo e(number_format($result['totalNormalNightHours'], 2)); ?></td>
                                                    <td><?php echo e(number_format($result['totalHolidayNightHours'], 2)); ?></td>
                                                    <td><?php echo e(number_format($result['bonus3Hours'], 2)); ?></td>
                                                    <td><?php echo e(number_format($result['specialHolidayDay'], 2)); ?></td>
                                                    <td><?php echo e(number_format($result['bonus5Hours'], 2)); ?></td>
                                                    <td><?php echo e(number_format($result['specialHolidayNight'], 2)); ?></td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                        
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
    <!-- Include the DataTables and Buttons scripts -->
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.flash.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            $('#datatable-buttons').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    
                    // Function to sum HH.MM format
                    function sumTime(times) {
                        var totalMinutes = 0;
                        times.forEach(function(time) {
                            if (time) {
                                var parts = time.toString().split('.');
                                var hours = parseInt(parts[0]);
                                var minutes = parts[1] ? parseInt(parts[1]) : 0;
                                totalMinutes += (hours * 60) + minutes;
                            }
                        });
                        
                        var hours = Math.floor(totalMinutes / 60);
                        var minutes = totalMinutes % 60;
                        return hours + '.' + (minutes < 10 ? '0' : '') + minutes;
                    }

                    // Update footer totals for all numeric columns (3 through 10)
                    for(var i = 3; i <= 10; i++) {
                        var columnData = api.column(i).data();
                        var total = sumTime(columnData);
                        $(api.column(i).footer()).html(total);
                    }
                }
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/reports/monthly-christmas.blade.php ENDPATH**/ ?>