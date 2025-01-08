<?php $__env->startSection('content'); ?>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="page-title">Display Schedule</h4>
                    </div>
                </div>
            </div>

            <div class="navigation-buttons mb-3">
                <button id="prev-week-btn" class="btn btn-secondary">&lt; Prev Week</button>
                <span id="week-label" class="mx-2"></span>
                <button id="next-week-btn" class="btn btn-secondary">Next Week &gt;</button>
            </div>

            <div class="row" id="week-grid">
               
            </div>

            <!-- Event Details Modal -->
            <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="eventDetailsModalLabel">Event Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Date:</strong> <span id="event-date"></span></p>
                            <p><strong>Staff:</strong> <span id="event-staff"></span></p>
                            <p><strong>Hours:</strong> <span id="event-hours"></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <style>
               
                .time-grid {
    display: grid;
    grid-template-columns: repeat(24, 1fr);
    position: relative;
    border-radius: 4px;
    overflow: visible;
    margin: 10px 0;
    border: 1px solid #e0e0e0;
    min-height: 150px; 
}

                .time-period {
                    grid-column: span 1;
                    border-right: 1px solid rgba(0, 0, 0, 0.05);
                    padding: 4px 0;
                    text-align: center;
                    font-size: 0.4em;
                    color: #666;
                    height: 100%;
                    position: relative;
                }

                .hour-label {
                    position: absolute;
                    top: 2px;
                    left: 0;
                    right: 0;
                    text-align: center;
                    font-size: 0.85em;
                    color: #999;
                }

                .morning-time {
                    background: rgba(200, 227, 255, 0.1);
                }

                .day-time {
                    
                }

                .evening-time {
                    background: rgba(200, 200, 255, 0.2);
                }

                .schedule-container {
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    min-height: 100%;
    pointer-events: none;
}

.schedule-bar {
    position: absolute;
    
    color: #000;
    font-size: 0.85em;
    padding: 3px 6px;
    border-radius: 4px;
    white-space: normal; 
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    pointer-events: auto;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.1);
    font-weight: 500;
    z-index: 1;
    line-height: 1.2; /
}

                .day-card {
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    margin-bottom: 20px;
                }

                .card-header {
                    
                    
                    padding: 0.75rem 1.25rem;
                    text-align: center;
                }

                .info-section {
                    
                    border: 1px solid #e0e0e0;
                    border-radius: 4px;
                    padding: 8px;
                    margin: 10px 0;
                }

                .bottom-info {
                    /* background: #f8f9fa; */
                    border: 1px solid #e0e0e0;
                    border-radius: 4px;
                    padding: 8px;
                    margin-top: 10px;
                }

                #week-label {
                    font-weight: bold;
                    font-size: 1.1em;
                    min-width: 200px;
                    text-align: center;
                }

                .card-body {
                    position: relative;
                    padding: 8px !important;
                }
                .card-header{
                    padding: 0;
                    margin: 5px;

                }
            </style>

            <script>
                $(document).ready(function() {
                    let currentDate = new Date("<?php echo e($weekStart->format('Y-m-d')); ?>");
                    let staffMembers = <?php echo json_encode($staffMembers, 15, 512) ?>;

                    function getMonday(date) {
                        const day = date.getDay();
                        const diff = date.getDate() - day + (day === 0 ? -6 : 1);
                        return new Date(date.setDate(diff));
                    }

                    function formatDate(date) {
                        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                    'July', 'August', 'September', 'October', 'November', 'December'];
                        return `${date.getDate()} ${months[date.getMonth()]} (${days[date.getDay()]})`;
                    }

                    function timeToGridPosition(time) {
                        const [hours, minutes] = time.split(':').map(Number);
                        return hours + (minutes / 60);
                    }

                    function calculateWidth(startTime, endTime) {
                        const start = timeToGridPosition(startTime);
                        const end = timeToGridPosition(endTime);
                        return ((end - start) / 24) * 100;
                    }

                    function calculateLeft(startTime) {
                        const start = timeToGridPosition(startTime);
                        return (start / 24) * 100;
                    }

                    function renderWeekView(startDate, staffHours, receptionData, midnightPhoneStaff) {
    const weekGrid = $('#week-grid');
    weekGrid.empty();

    for (let i = 0; i < 7; i++) {
        const currentDate = new Date(startDate);
        currentDate.setDate(startDate.getDate() + i);
        const dateString = currentDate.toISOString().split('T')[0];

        const dayCard = $(`
    <div class="col-md-4 px-1">
        <div class="card day-card">
            <div class="card-header">
                <h5 class="card-title mb-0">${formatDate(currentDate)}</h5>
            </div>
            <div class="card-body">
                ${receptionData[dateString] ? `
                    <div class="info-section">
                        <strong>Reception:</strong> ${receptionData[dateString]}
                    </div>
                ` : ''}
                <div class="time-grid"><div class="schedule-container"></div></div>
                ${(midnightPhoneStaff[dateString] || getOffStaffList(staffHours[dateString]) || getSickLeaveStaffList(staffHours[dateString])) ? `
                    <div class="bottom-info">
                        ${midnightPhoneStaff[dateString] ? `
                            <div><strong>Midnight Phone:</strong> ${midnightPhoneStaff[dateString]}</div>
                        ` : ''}
                        ${getOffStaffList(staffHours[dateString]) ? `
                            <div><strong>DayOff:</strong> ${getOffStaffList(staffHours[dateString])}</div>
                        ` : ''}
                        ${getSickLeaveStaffList(staffHours[dateString]) ? `
                            <div><strong>Sick Leave:</strong> ${getSickLeaveStaffList(staffHours[dateString])}</div>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        </div>
    </div>
`);

        // Add time periods
        const timeGrid = dayCard.find('.time-grid');
        for (let hour = 0; hour < 24; hour++) {
            const displayHour = hour.toString().padStart(2, '0') + ':00';
            const periodClass = 
                hour < 8 ? 'morning-time' : 
                hour < 18 ? 'day-time' : 'evening-time';
            
            timeGrid.append(`
                <div class="time-period ${periodClass}">
                    <div class="hour-label">${displayHour}</div>
                </div>
            `);
        }

        // Add schedule bars
        const scheduleContainer = dayCard.find('.schedule-container');
        if (staffHours[dateString]) {
            Object.entries(staffHours[dateString]).forEach(([staffId, hours], index) => {
                const staff = staffMembers.find(s => s.id == staffId);
                hours.forEach(hour => {
                    if (hour.start_time && hour.end_time) {
                        const width = calculateWidth(hour.start_time, hour.end_time);
                        const left = calculateLeft(hour.start_time);
                        scheduleContainer.append(`
                            <div class="schedule-bar" 
                                 style="background-color: ${staff.color || '#ccc'};
                                        left: ${left}%;
                                        width: ${width}%;
                                        top: ${index *30}px;"
                                 data-date="${dateString}"
                                 data-staff="${staff.name}"
                                 data-hours="${hour.start_time}-${hour.end_time}">
                                <div style="font-weight: bold;">${staff.name} -${hour.start_time} - ${hour.end_time}</div>
                                
                            </div>
                        `);
                    }
                });
            });
        }

        weekGrid.append(dayCard);
    }
    
    setTimeout(adjustTimeGridHeight, 100);
}

                    function getOffStaffList(dayStaffHours) {
                        if (!dayStaffHours) return '';
                        const offStaff = [];
                        Object.entries(dayStaffHours).forEach(([staffId, hours]) => {
                            if (hours.some(hour => hour.type === 'X' || hour.type === 'V')) {
                                const staff = staffMembers.find(s => s.id == staffId);
                                if (staff) offStaff.push(staff.name);
                            }
                        });
                        return offStaff.join(', ');
                    }

                    function getSickLeaveStaffList(dayStaffHours) {
                        if (!dayStaffHours) return '';
                        const sickStaff = [];
                        Object.entries(dayStaffHours).forEach(([staffId, hours]) => {
                            if (hours.some(hour => hour.type === 'SL')) {
                                const staff = staffMembers.find(s => s.id == staffId);
                                if (staff) sickStaff.push(staff.name);
                            }
                        });
                        return sickStaff.join(', ');
                    }

                    function updateWeekLabel(startDate) {
                        const endDate = new Date(startDate);
                        endDate.setDate(startDate.getDate() + 6);
                        
                        const startFormatted = formatDate(startDate);
                        const endFormatted = formatDate(endDate);
                        
                        $('#week-label').text(`${startFormatted} - ${endFormatted}`);
                    }

                    function loadWeekData(date) {
                        const mondayDate = getMonday(date);
                        $.ajax({
                            url: "<?php echo e(route('supervisor.week-data')); ?>",
                            method: 'GET',
                            data: { week_start: mondayDate.toISOString().split('T')[0] },
                            success: function(response) {
                                renderWeekView(mondayDate, response.staffHours, response.receptionData, response.midnightPhoneStaff);
                                updateWeekLabel(mondayDate);
                            },
                            error: function(xhr, status, error) {
                                console.error("Error loading week data:", error);
                                alert("Error loading week data. Please try again.");
                            }
                        });
                    }

                    // Navigation handlers
                    $('#prev-week-btn').click(function() {
                        currentDate.setDate(currentDate.getDate() - 7);
                        loadWeekData(currentDate);
                    });

                    $('#next-week-btn').click(function() {
                        currentDate.setDate(currentDate.getDate() + 7);
                        loadWeekData(currentDate);
                    });

                    // Event click handler
                    $(document).on('click', '.schedule-bar', function() {
                        const $event = $(this);
                        $('#event-date').text(formatDate(new Date($event.data('date'))));
                        $('#event-staff').text($event.data('staff'));
                        $('#event-hours').text($event.data('hours'));
                        $('#eventDetailsModal').modal('show');
                    });

                    // Initial load
                    loadWeekData(currentDate);
                });


                function adjustTimeGridHeight() {
    $('.time-grid').each(function() {
        const scheduleContainer = $(this).find('.schedule-container');
        const scheduleItems = scheduleContainer.find('.schedule-bar');
        if (scheduleItems.length > 0) {
            const lastItem = scheduleItems.last();
            const bottomPosition = lastItem.position().top + lastItem.outerHeight() + 40;
            $(this).css('height', Math.max(150, bottomPosition) + 'px');
        }
    });
}
            </script>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/supervisor/display-schedule.blade.php ENDPATH**/ ?>