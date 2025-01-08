<?php $__env->startSection('content'); ?>
    <!-- Add Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Error Log</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Errors</a>
                                    </li>
                                    <li class="breadcrumb-item active">Display Errors</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date Filter Form -->
                <form action="<?php echo e(route('errors.filter')); ?>" method="GET" class="form-inline mb-4">
                    <div class="form-group mr-2">
                        <label for="fromDate" class="mr-2">From:</label>
                        <input type="text" name="fromDate" id="fromDate" class="form-control flatpickr-date" required>
                    </div>
                    <div class="form-group mr-2">
                        <label for="toDate" class="mr-2">To:</label>
                        <input type="text" name="toDate" id="toDate" class="form-control flatpickr-date" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                <?php if(!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php $__currentLoopData = $errors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <?php
                                        $errorLines = explode("\n", $error['desc']);
                                        $date = '';
                                        $tourName = '';
                                        $errorMessage = '';
                                        foreach ($errorLines as $line) {
                                            if (strpos($line, 'Date:') === 0) {
                                                $date = trim(substr($line, 5));
                                            } elseif (strpos($line, 'Tour Name:') === 0) {
                                                $tourName = trim(substr($line, 10));
                                            } elseif (strpos($line, 'Error:') === 0) {
                                                $errorMessage = trim(substr($line, 6));
                                            }
                                        }
                                    ?>
                                    <strong>Date:</strong> <?php echo e($date); ?><br>
                                    <strong>Tour Name:</strong> <?php echo e($tourName); ?><br>
                                    <strong>Error:</strong> <?php echo e($errorMessage); ?><br>
                                    <button class="btn btn-warning btn-sm ignore-event" data-event-id="<?php echo e($error['eventId']); ?>">Ignore</button>
                                    <button class="btn btn-primary btn-sm open-manual-entry" data-event-id="<?php echo e($error['eventId']); ?>">Add Manually</button>
                                </li>
                                <br>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        No errors to display.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Manual Entry Modal -->
    <div class="modal fade" id="manualEntryModal" tabindex="-1" role="dialog" aria-labelledby="manualEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualEntryModalLabel">Add Guides Manually</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="manualEntryForm" action="<?php echo e(route('salary.manual-calculation')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="eventId" id="manualEntryEventId">
                        <p style="color: red;font-size:16px">*To add a chore, leave the start and end time empty.*</p>
                        <div id="guideFieldsContainer">
                            <!-- Guide entries will be added here -->
                        </div>
                        <button type="button" class="btn btn-secondary" id="addGuideButton">Add Another Guide</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initFlatpickr();

        function ignoreError(eventId) {
            if (confirm('Are you sure you want to ignore this event?')) {
                $.ajax({
                    url: '/events/ignore',
                    method: 'POST',
                    data: {
                        _token: '<?php echo e(csrf_token()); ?>',
                        eventId: eventId
                    },
                    success: function(response) {
                        alert('Event has been ignored successfully.');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred while ignoring the event.');
                        console.error(xhr.responseText);
                    }
                });
            }
        }

        function openManualEntryModal(eventId) {
            document.getElementById('manualEntryEventId').value = eventId;
            $('#manualEntryModal').modal('show');
        }

        function addGuideEntry() {
            var guideFieldsContainer = document.getElementById('guideFieldsContainer');
            var guideCount = guideFieldsContainer.getElementsByClassName('guide-entry').length;
            var newGuideEntry = `
                <div class="card mb-3 guide-entry">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="guideName${guideCount}">Select Guide</label>
                            <select name="guides[${guideCount}][name]" id="guideName${guideCount}" class="form-control" required>
                                <option value="" disabled selected>Select Guide</option>
                                <?php $__currentLoopData = $guides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($guide->id); ?>"><?php echo e($guide->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="startTime${guideCount}">Start Time</label>
                            <input type="text" name="guides[${guideCount}][startTime]" id="startTime${guideCount}" class="form-control flatpickr-datetime">
                        </div>
                        <div class="form-group">
                            <label for="endTime${guideCount}">End Time</label>
                            <input type="text" name="guides[${guideCount}][endTime]" id="endTime${guideCount}" class="form-control flatpickr-datetime">
                        </div>
                    </div>
                </div>
            `;
            guideFieldsContainer.insertAdjacentHTML('beforeend', newGuideEntry);
            initFlatpickr();
        }

        function initFlatpickr() {
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
            });

            flatpickr(".flatpickr-datetime", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true
            });
        }

        // Attach event listeners using event delegation
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('ignore-event')) {
                event.preventDefault();
                ignoreError(event.target.getAttribute('data-event-id'));
            } else if (event.target.classList.contains('open-manual-entry')) {
                event.preventDefault();
                openManualEntryModal(event.target.getAttribute('data-event-id'));
            }
        });

        // Add event listener for the "Add Another Guide" button
        var addGuideButton = document.getElementById('addGuideButton');
        if (addGuideButton) {
            addGuideButton.addEventListener('click', addGuideEntry);
        }

        // Add initial guide entry when modal is shown
        $('#manualEntryModal').on('show.bs.modal', function () {
            var guideFieldsContainer = document.getElementById('guideFieldsContainer');
            guideFieldsContainer.innerHTML = ''; // Clear existing entries
            addGuideEntry(); // Add initial guide entry
        });
    });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/notifications/errors.blade.php ENDPATH**/ ?>