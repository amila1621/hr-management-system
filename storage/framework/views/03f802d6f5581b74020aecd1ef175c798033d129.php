<?php $__env->startSection('content'); ?>

<style>
    .table-warning > td {
        color: black;
        background-color: #fa974b;
    }

    .table-danger > td {
        color: black;
        background-color: #f03252;
    }
</style>

<!-- Add Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <?php if(Auth::user()->role == "admin"): ?>
                    <h4 class="page-title">Pending Guide Approvals</h4>
                <?php else: ?>
                    <h4 class="page-title">Pending Driver Approvals</h4>
                <?php endif; ?>
            </div>

            <!-- Add search bar -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" id="guideSearch" class="form-control" placeholder="Search by guide name...">
                        <div class="input-group-append">
                            <button class="btn btn-secondary" type="button" onclick="clearSearch()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-2"></div>
                <div class="col-md-4 ms-auto">
                    <form action="<?php echo e(route('reports.pending-approvals-date-update')); ?>" method="POST" class="d-flex">
                        <?php echo csrf_field(); ?>
                        <label class="me-2 align-self-center">
                            <strong>Update Until:</strong>
                        </label>
                        <div class="input-group" style="height: 35px">
                            <input type="text" 
                                   name="until_date" 
                                   class="form-control flatpickr-date" 
                                   placeholder="Select date..." 
                                   value="<?php echo e(DB::table('updatedate')->first()->until_date_pending_approvals ?? ''); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if(session('failed')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e(session('failed')); ?>

            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <?php if(Auth::user()->role == "admin"): ?>
                                <th>Guide Name</th>
                            <?php else: ?>
                                <th>Driver Name</th>
                            <?php endif; ?>
                            <th>Tour Date</th>
                            <th>Tour Name</th>
                            <?php if(Auth::user()->role == "admin"): ?>
                                <th>Guide Start Time</th>
                            <?php else: ?>
                                <th>Driver Start Time</th>
                            <?php endif; ?>
                            <?php if(Auth::user()->role == "admin"): ?>
                                <th>Guide End Time</th>
                            <?php else: ?>
                                <th>Driver End Time</th>
                            <?php endif; ?>
                            <th>Duration</th>
                            <?php if(Auth::user()->role == "admin"): ?>
                                <th>Guide Comment</th>
                            <?php else: ?>
                                <th>Driver Comment</th>
                            <?php endif; ?>
                            <?php if(Auth::user()->role == "admin"): ?>
                                <th>Guide Image</th>
                            <?php else: ?>
                                <th>Driver Image</th>
                            <?php endif; ?>
                            <th>Admin Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $pendingApprovals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $approval): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><a target="_blank" href="/get-guide-wise-reports?guide_id=<?php echo e($approval->tourGuide->id); ?>&start_date=<?php echo e(\Carbon\Carbon::parse($approval->guide_start_time)->format('Y-m-01')); ?>&end_date=<?php echo e(\Carbon\Carbon::parse($approval->guide_start_time)->endOfMonth()->format('Y-m-d')); ?>"><?php echo e($approval->tourGuide->name); ?></a></td>
                                <td>
                                    <?php echo e($approval->event && $approval->guide_start_time ? \Carbon\Carbon::parse($approval->guide_start_time)->format('d.m.Y') : 'N/A'); ?>

                                </td>
                                <td><?php echo e($approval->event->name); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($approval->guide_start_time)->format('d.m.Y H:i')); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($approval->guide_end_time)->format('d.m.Y H:i')); ?></td>
                                <td>
                                    <?php
                                        $start = \Carbon\Carbon::parse($approval->guide_start_time);
                                        $end = \Carbon\Carbon::parse($approval->guide_end_time);
                                        $duration = $end->diff($start);
                                        echo $duration->format('%H:%I');
                                    ?>
                                </td>
                                <td><?php echo e($approval->guide_comment ?? 'No comment'); ?></td>
                                <td>
                                    <?php if($approval->guide_image): ?>
                                        <a href="<?php echo e(asset('storage/' . $approval->guide_image)); ?>" target="_blank">
                                            <img src="<?php echo e(asset('storage/' . $approval->guide_image)); ?>" alt="Guide Image" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                                        </a>
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Approve button -->
                                    <button class="btn btn-success btn-sm" data-toggle="modal"
                                        data-target="#approveModal<?php echo e($approval->id); ?>">Approve</button>

                                    <!-- Adjust button -->
                                    <button class="btn btn-secondary btn-sm" data-toggle="modal"
                                        data-target="#adjustModal<?php echo e($approval->id); ?>">Adjust</button>

                                    <!-- Reject button -->
                                    <button class="btn btn-danger btn-sm" data-toggle="modal"
                                        data-target="#rejectModal<?php echo e($approval->id); ?>">Reject</button>

                                    <!-- Needs More Info button -->
                                    <button class="btn btn-warning btn-sm" data-toggle="modal"
                                        data-target="#needsInfoModal<?php echo e($approval->id); ?>">Needs More Info</button>
                                </td>
                            </tr>

                            <!-- Approve Modal -->
                            <div class="modal fade" id="approveModal<?php echo e($approval->id); ?>" tabindex="-1" role="dialog"
                                aria-labelledby="approveModalLabel<?php echo e($approval->id); ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="<?php echo e(route('admin.approve', $approval->id)); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <div class="modal-header">
                                                <h5 class="modal-title">Approve Hours</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <?php if(Auth::user()->role == "admin"): ?>
                                                    <p>Guide: <?php echo e($approval->tourGuide->name); ?></p>
                                                <?php else: ?>
                                                    <p>Driver: <?php echo e($approval->tourGuide->name); ?></p>
                                                <?php endif; ?>
                                                <p>Start Time: <?php echo e(\Carbon\Carbon::parse($approval->guide_start_time)->format('d.m.Y H:i')); ?></p>
                                                <p>End Time: <?php echo e(\Carbon\Carbon::parse($approval->guide_end_time)->format('d.m.Y H:i')); ?></p>
                                                <p>Comment: <?php echo e($approval->guide_comment ?? 'No comment'); ?></p>
                                                
                                                <?php if($approval->guide_image): ?>
                                                    <div class="form-group">
                                                        <?php if(Auth::user()->role == "admin"): ?>
                                                            <label>Guide Image:</label>
                                                        <?php else: ?>
                                                            <label>Driver Image:</label>
                                                        <?php endif; ?>
                                                        <img src="<?php echo e(asset('storage/' . $approval->guide_image)); ?>" alt="Guide Image" class="img-fluid">
                                                    </div>
                                                <?php endif; ?>

                                                <div class="form-group">
                                                    <label for="approval_comment">Approval Comment (Optional)</label>
                                                    <textarea class="form-control" name="approval_comment"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-success">Approve</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?php echo e($approval->id); ?>" tabindex="-1" role="dialog"
                                aria-labelledby="rejectModalLabel<?php echo e($approval->id); ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="<?php echo e(route('admin.reject', $approval->id)); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reject Hours</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <?php if(Auth::user()->role == "admin"): ?>
                                                    <p>Guide: <?php echo e($approval->tourGuide->name); ?></p>
                                                <?php else: ?>
                                                    <p>Driver: <?php echo e($approval->tourGuide->name); ?></p>
                                                <?php endif; ?>
                                                <p>Start Time: <?php echo e(\Carbon\Carbon::parse($approval->guide_start_time)->format('d.m.Y H:i')); ?></p>
                                                <p>End Time: <?php echo e(\Carbon\Carbon::parse($approval->guide_end_time)->format('d.m.Y H:i')); ?></p>
                                                <p>Comment: <?php echo e($approval->guide_comment ?? 'No comment'); ?></p>
                                                
                                                <?php if($approval->guide_image): ?>
                                                    <div class="form-group">
                                                        <?php if(Auth::user()->role == "admin"): ?>
                                                            <label>Guide Image:</label>
                                                        <?php else: ?>
                                                            <label>Driver Image:</label>
                                                        <?php endif; ?>
                                                        <img src="<?php echo e(asset('storage/' . $approval->guide_image)); ?>" alt="Guide Image" class="img-fluid">
                                                    </div>
                                                <?php endif; ?>

                                                <div class="form-group">
                                                    <label for="approval_comment">Rejection Comment (Optional)</label>
                                                    <textarea class="form-control" name="approval_comment"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-danger">Reject</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Adjust Modal -->
                            <div class="modal fade" id="adjustModal<?php echo e($approval->id); ?>" tabindex="-1" role="dialog"
                                aria-labelledby="adjustModalLabel<?php echo e($approval->id); ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="<?php echo e(route('admin.adjust', $approval->id)); ?>" method="POST" id="adjustForm<?php echo e($approval->id); ?>">
                                            <?php echo csrf_field(); ?>
                                            <div class="modal-header">
                                                <h5 class="modal-title">Adjust Hours</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <label for="rejection_comment">Adjustment Comment (Optional)</label>
                                                <textarea class="form-control" name="approval_comment"></textarea>

                                                <div class="form-group mt-3">
                                                    <label for="guide_start_time">Start Date & Time</label>
                                                    <input type="text" class="form-control flatpickr-datetime" 
                                                           name="guide_start_time" 
                                                           value="<?php echo e($approval->guide_start_time); ?>"
                                                           onchange="updateDuration(<?php echo e($approval->id); ?>)">
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label for="guide_end_time">End Date & Time</label>
                                                    <input type="text" class="form-control flatpickr-datetime" 
                                                           name="guide_end_time" 
                                                           value="<?php echo e($approval->guide_end_time); ?>"
                                                           onchange="updateDuration(<?php echo e($approval->id); ?>)">
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label>Duration:</label>
                                                    <span id="duration<?php echo e($approval->id); ?>"></span>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-warning" onclick="confirmAdjustment(<?php echo e($approval->id); ?>)">Adjust</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Needs More Info Modal -->
                            <div class="modal fade" id="needsInfoModal<?php echo e($approval->id); ?>" tabindex="-1" role="dialog"
                                aria-labelledby="needsInfoModalLabel<?php echo e($approval->id); ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="<?php echo e(route('admin.needs-info', $approval->id)); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <div class="modal-header">
                                                <h5 class="modal-title">Request More Info</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <label for="approval_comment">Additional Information Needed (Comment)</label>
                                                <textarea class="form-control" name="approval_comment"></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-warning">Request More Info</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initFlatpickr();
        
        // Add search functionality
        document.getElementById('guideSearch').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('table tbody tr');
            
            tableRows.forEach(row => {
                const guideName = row.querySelector('td:first-child').textContent.toLowerCase();
                if (guideName.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    function clearSearch() {
        document.getElementById('guideSearch').value = '';
        const tableRows = document.querySelectorAll('table tbody tr');
        tableRows.forEach(row => {
            row.style.display = '';
        });
    }

    function initFlatpickr() {
        flatpickr(".flatpickr-datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });

        flatpickr(".flatpickr-date", {
            dateFormat: "Y-m-d",
            defaultDate: document.querySelector('.flatpickr-date').value || "today"
        });
    }

    function updateDuration(approvalId) {
        const startTime = document.querySelector(`#adjustModal${approvalId} input[name="guide_start_time"]`).value;
        const endTime = document.querySelector(`#adjustModal${approvalId} input[name="guide_end_time"]`).value;
        
        if (startTime && endTime) {
            const start = new Date(startTime);
            const end = new Date(endTime);
            
            // Calculate duration in milliseconds
            const duration = end - start;
            
            // Convert to hours and minutes
            const hours = Math.floor(duration / (1000 * 60 * 60));
            const minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
            
            document.getElementById(`duration${approvalId}`).textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        }
    }

    function confirmAdjustment(approvalId) {
        const duration = document.getElementById(`duration${approvalId}`).textContent;
        const startTime = document.querySelector(`#adjustModal${approvalId} input[name="guide_start_time"]`).value;
        const endTime = document.querySelector(`#adjustModal${approvalId} input[name="guide_end_time"]`).value;
        
        const confirmMessage = `Please confirm the following adjustment:\n\n` +
            `Start Time: ${startTime}\n` +
            `End Time: ${endTime}\n` +
            `Duration: ${duration}\n\n` +
            `Are these details correct?`;
        
        if (confirm(confirmMessage)) {
            document.getElementById(`adjustForm${approvalId}`).submit();
        }
    }

    // Initialize duration on modal open
    document.querySelectorAll('[data-target^="#adjustModal"]').forEach(button => {
        button.addEventListener('click', function() {
            const approvalId = this.getAttribute('data-target').match(/\d+/)[0];
            setTimeout(() => updateDuration(approvalId), 100);
        });
    });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/reports/pending-approvals.blade.php ENDPATH**/ ?>