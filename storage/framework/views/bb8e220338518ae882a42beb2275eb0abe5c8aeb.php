
<?php $__env->startSection('content'); ?>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="page-title">Display Schedule</h4>
                    </div>
                    <div class="col-md-4 text-right">
                        <input type="date" id="date-picker" class="form-control d-inline-block" style="width: auto; border-color: #2c3749;">
                    </div>
                </div>
            </div>

            <div class="card mb-3" style="border-top: 3px solid #2c3749;">
                <div class="card-body">
                    <div class="navigation-buttons d-flex align-items-center justify-content-between mb-3">
                        <button id="prev-week-btn" class="btn" style="background-color: #2c3749; color: white;">
                            <i class="fa fa-chevron-left mr-1"></i> Prev Week
                        </button>
                        <span id="week-label" class="mx-2 font-weight-bold"></span>
                        <button id="next-week-btn" class="btn" style="background-color: #2c3749; color: white;">
                            Next Week <i class="fa fa-chevron-right ml-1"></i>
                        </button>
                    </div>
                
                </div>
            </div>

            <div class="week-view-container">
                
                <div class="row" id="week-grid">
                    <!-- Day cards will be added here -->
                </div>
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
                            <p><strong>Type:</strong> <span id="event-type"></span></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" style="background-color: #2c3749; color: white;" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                /* Common styles */
                body {
                    font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }
                
                /* Date navigation */
                #week-label {
                    font-weight: bold;
                    font-size: 1.1em;
                    min-width: 220px;
                    text-align: center;
                }

                /* Staff legend */
                .staff-legend-item {
                    display: inline-flex;
                    align-items: center;
                    margin-right: 12px;
                    margin-bottom: 8px;
                }
                
                .staff-color {
                    width: 18px;
                    height: 18px;
                    border-radius: 4px;
                    margin-right: 6px;
                    border: 1px solid rgba(0,0,0,0.1);
                }
                
                /* Day cards */
                .day-card {
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                    margin-bottom: 20px;
                    border-radius: 8px;
                    overflow: hidden;
                    transition: box-shadow 0.2s ease;
                }
                
                .day-card:hover {
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }
                
                .day-card.current-day {
                    border-left: 4px solid #2c3749;
                }
                
                .day-card .card-header {
                    padding: 12px 15px;
                    background-color: #2c3749;
                    border-bottom: 1px solid #e0e0e0;
                    text-align: center;
                    color: white;
                }
                
                .day-card .card-body {
                    position: relative;
                    padding: 12px !important;
                }
                
                /* Time grid */
                .time-grid {
                    display: grid;
                    grid-template-columns: repeat(24, 1fr);
                    position: relative;
                    border-radius: 4px;
                    overflow: visible;
                    margin: 0 0 5px 0;
                    border: 1px solid #e0e0e0;
                    min-height: 150px;
                    background-color: #f9f9f9;
                }
                
                .time-period {
                    grid-column: span 1;
                    border-right: 1px solid rgba(0, 0, 0, 0.05);
                    padding: 4px 0;
                    text-align: center;
                    font-size: 0.65em;
                    color: #666;
                    height: 100%;
                    position: relative;
                }
                
                .time-period:nth-child(4n+1) {
                    border-right: 1px solid rgba(0, 0, 0, 0.1);
                }
                
                .hour-label {
                    position: absolute;
                    top: 4px;
                    left: 0;
                    right: 0;
                    text-align: center;
                    font-size: 2.2em;
                    color: #2c3749;
                    font-weight: 500;
                }
                
                .morning-time {
                    background: rgba(44, 55, 73, 0.05);
                }
                
                .day-time {
                    background: rgba(255, 255, 255, 0.5);
                }
                
                .evening-time {
                    background: rgba(44, 55, 73, 0.1);
                }
                
                /* Schedule container and bars */
                .schedule-container {
                    position: absolute;
                    top: 22px;
                    left: 0;
                    right: 0;
                    min-height: 100%;
                    pointer-events: none;
                }
                
                .schedule-bar {
                    position: absolute;
                    color: #000;
                    font-size: 0.75em;
                    padding: 4px 6px;
                    border-radius: 4px;
                    white-space: normal;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    cursor: pointer;
                    pointer-events: auto;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                    border: 1px solid rgba(0, 0, 0, 0.1);
                    font-weight: 500;
                    z-index: 1;
                    line-height: 1.2;
                    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
                    min-height: 24px;
                    opacity: 0.95;
                }
                
                .schedule-bar .time-display {
                    font-size: 0.9em;
                    opacity: 0.8;
                }
                
                .schedule-bar:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
                    z-index: 2;
                    opacity: 1;
                }
                
                /* Info sections */
                .info-section {
                    background-color: #f8f9fb;
                    border-left: 3px solid #2c3749;
                    border-radius: 6px;
                    padding: 10px 12px;
                    margin: 10px 0;
                }
                
                .bottom-info {
                    background-color: #f8f9fb;
                    border-left: 3px solid #2c3749;
                    border-radius: 6px;
                    padding: 10px 12px;
                    margin-top: 10px;
                }
                
                .bottom-info div {
                    margin-bottom: 6px;
                }
                
                .bottom-info div:last-child {
                    margin-bottom: 0;
                }
                
                /* Specific class for sick leave */
                .schedule-bar.sick-leave {
                    background-image: repeating-linear-gradient(
                        45deg,
                        rgba(255, 255, 255, 0.2),
                        rgba(255, 255, 255, 0.2) 10px,
                        rgba(255, 255, 255, 0.3) 10px,
                        rgba(255, 255, 255, 0.3) 20px
                    );
                }
                
                /* Tooltip styles */
                .tooltip-inner {
                    max-width: 200px;
                    padding: 6px 10px;
                    background-color: #2c3749;
                    border-radius: 4px;
                    font-size: 0.85rem;
                }
                
                /* Responsive styles */
                @media (max-width: 768px) {
                    .col-md-6 {
                        width: 100%;
                    }
                    
                    .day-card {
                        margin-bottom: 15px;
                    }
                    
                    .schedule-bar {
                        font-size: 0.75em;
                        padding: 4px 6px;
                    }
                    
                    #week-label {
                        font-size: 1em;
                        min-width: 180px;
                    }
                }
            </style>
            
            <script>
                $(document).ready(function() {
                    // Bootstrap 5 modal close handler
                    $('.btn-close, .modal button[data-bs-dismiss="modal"]').click(function() {
                        $('#eventDetailsModal').modal('hide');
                    });
                    
                    let currentDate = new Date("<?php echo e($weekStart->format('Y-m-d')); ?>");
                    let staffMembers = <?php echo json_encode($staffMembers, 15, 512) ?>;
                    let tooltipEnabled = true;
                    
                    // Initialize tooltips
                    function initTooltips() {
                        if (tooltipEnabled) {
                            $('.schedule-bar').tooltip({
                                title: function() {
                                    return $(this).data('staff') + ': ' + $(this).data('hours');
                                },
                                placement: 'top',
                                container: 'body'
                            });
                        }
                    }
                    
                    // Time period formatter - modified to remove the :00
                    function formatTimePeriod(hour) {
                        // Pad with leading zero if needed and just return the hour
                        return hour.toString().padStart(2, '0');
                    }

                    function getMonday(date) {
                        const day = date.getDay();
                        const diff = date.getDate() - day + (day === 0 ? -6 : 1);
                        return new Date(date.setDate(diff));
                    }

                    function formatDate(date) {
                        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        const months = ['January', 'February', 'March', 'April', 'May', 'June',
                            'July', 'August', 'September', 'October', 'November', 'December'
                        ];
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
                    
                    function isToday(date) {
                        const today = new Date();
                        return date.getDate() === today.getDate() && 
                               date.getMonth() === today.getMonth() && 
                               date.getFullYear() === today.getFullYear();
                    }

                    function renderWeekView(startDate, staffHours, receptionData, midnightPhoneStaff) {
                        const weekGrid = $('#week-grid');
                        weekGrid.empty();
                        
                                                    // Calculate weekly stats
                        let totalHours = 0;
                        let uniqueStaff = new Set();
                        let daysOffCount = 0;
                        let sickLeaveCount = 0;
                        
                        // Process data for stats
                        for (const dateKey in staffHours) {
                            for (const staffId in staffHours[dateKey]) {
                                uniqueStaff.add(staffId);
                                staffHours[dateKey][staffId].forEach(hour => {
                                    if (hour.type === 'X' || hour.type === 'V') {
                                        daysOffCount++;
                                    } else if (hour.type === 'SL') {
                                        sickLeaveCount++;
                                    } else if (hour.start_time && hour.end_time) {
                                        const start = timeToGridPosition(hour.start_time);
                                        const end = timeToGridPosition(hour.end_time);
                                        totalHours += (end - start);
                                    }
                                });
                            }
                        }

                        for (let i = 0; i < 7; i++) {
                            const currentDate = new Date(startDate);
                            currentDate.setDate(startDate.getDate() + i);
                            const dateString = currentDate.toISOString().split('T')[0];
                            const isCurrentDay = isToday(currentDate);
                            const dayNumber = currentDate.getDate();

                            // Day card with compact modern design
                            const dayCard = $(`
                                <div class="col-md-4 px-1 mb-0">
                                    <div class="card day-card position-relative ${isCurrentDay ? 'current-day' : ''}" >
                                        <div class="date-indicator p-2 text-center">
                                            ${dayNumber} ${['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][currentDate.getDay()]}
                                        </div>
                                        <div class="card-body p-2">
                                            ${receptionData[dateString] ? `
                                                <div class="info-badge mb-2" style="background-color: rgba(44, 55, 73, 0.1); padding: 3px 6px; border-radius: 4px; font-size: 0.8rem;">
                                                    <i class="fa fa-building mr-1"></i> ${receptionData[dateString]}
                                                </div>
                                            ` : ''}
                                            <div class="time-grid" style="height: 230px; margin-bottom: 0;"><div class="schedule-container"></div></div>
                                            ${(midnightPhoneStaff[dateString] || getOffStaffList(staffHours[dateString]) || getSickLeaveStaffList(staffHours[dateString])) ? `
                                                <div class="bottom-indicators mt-2">
                                                    ${midnightPhoneStaff[dateString] ? `
                                                        <div class="info-badge" style="background-color: rgba(44, 55, 73, 0.1); padding: 3px 6px; border-radius: 4px; font-size: 0.8rem; margin-bottom: 4px;">
                                                            <i class="fa fa-phone mr-1"></i> ${midnightPhoneStaff[dateString]}
                                                        </div>
                                                    ` : ''}
                                                    ${getOffStaffList(staffHours[dateString]) ? `
                                                        <div class="info-badge" style="background-color: rgba(44, 55, 73, 0.1); padding: 3px 6px; border-radius: 4px; font-size: 0.8rem; margin-bottom: 4px;">
                                                            <i class="fa fa-calendar-times mr-1"></i> ${getOffStaffList(staffHours[dateString])}
                                                        </div>
                                                    ` : ''}
                                                    ${getSickLeaveStaffList(staffHours[dateString]) ? `
                                                        <div class="info-badge" style="background-color: rgba(44, 55, 73, 0.1); padding: 3px 6px; border-radius: 4px; font-size: 0.8rem; margin-bottom: 4px;">
                                                            <i class="fa fa-medkit mr-1"></i> ${getSickLeaveStaffList(staffHours[dateString])}
                                                        </div>
                                                    ` : ''}
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            `);

                            // Add time periods - only show key hours to save space
                            const timeGrid = dayCard.find('.time-grid');
                            for (let hour = 0; hour < 24; hour++) {
                                const displayHour = hour % 3 === 0 ? formatTimePeriod(hour) : '';
                                const periodClass =
                                    hour < 8 ? 'morning-time' :
                                    hour < 18 ? 'day-time' : 'evening-time';

                                timeGrid.append(`
                                    <div class="time-period ${periodClass}">
                                        ${displayHour ? `<div class="hour-label" style="font-size: 0.7rem;">${displayHour}</div>` : ''}
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
                                            const cleanStartTime = hour.start_time.replace(/^SL[: ]*/i, '');
                                            const left = calculateLeft(cleanStartTime);
                                            
                                            const adjustedStartTime = hour.start_time.replace('SL ', '');
                                            const adjustedEndTime = hour.end_time.replace('SL ', '');
                                            
                                            // Determine if this is a sick leave entry
                                            const isSickLeave = hour.type === 'SL';
                                            
                                            scheduleContainer.append(`
                                                <div class="schedule-bar ${isSickLeave ? 'sick-leave' : ''}" 
                                                     style="background-color: ${staff.color || '#ccc'};
                                                            left: ${left}%;
                                                            width: ${width}%;
                                                            top: ${index * 28}px;"
                                                     data-date="${dateString}"
                                                     data-staff="${staff.name}"
                                                     data-hours="${hour.start_time}-${hour.end_time}"
                                                     data-type="${hour.type || 'Regular'}">
                                                    <div style="font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        ${staff.name.split(' ')[0]} 
                                                        <span class="time-display">${hour.start_time.substring(0,5)}-${hour.end_time.substring(0,5)}</span>
                                                        ${isSickLeave ? ' <i class="fa fa-medkit"></i>' : ''}
                                                    </div>
                                                </div>
                                            `);
                                        }
                                    });
                                });
                            }

                            weekGrid.append(dayCard);
                        }

                        // Update stats display
                        $('#total-hours').text(Math.round(totalHours) + ' hrs');
                        $('#staff-count').text(uniqueStaff.size + ' staff');
                        $('#days-off-count').text(daysOffCount);
                        $('#sick-leave-count').text(sickLeaveCount);
                        
                        initTooltips();
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

                        const startFormatted = `${startDate.getDate()} ${startDate.toLocaleString('default', { month: 'short' })}`;
                        const endFormatted = `${endDate.getDate()} ${endDate.toLocaleString('default', { month: 'short' })}`;

                        $('#week-label').text(`${startFormatted} - ${endFormatted}, ${startDate.getFullYear()}`);
                    }

                    function loadWeekData(date) {
                        const mondayDate = getMonday(date);
                        $.ajax({
                            url: "<?php echo e(route('supervisor.week-data')); ?>",
                            method: 'GET',
                            data: {
                                week_start: mondayDate.toISOString().split('T')[0]
                            },
                            success: function(response) {
                                renderWeekView(mondayDate, response.staffHours, response.receptionData, response.midnightPhoneStaff);
                                updateWeekLabel(mondayDate);
                                // Update date picker value
                                $('#date-picker').val(mondayDate.toISOString().split('T')[0]);
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
                    
                    // Date picker handler
                    $('#date-picker').change(function() {
                        const selectedDate = new Date($(this).val());
                        currentDate = selectedDate;
                        loadWeekData(selectedDate);
                    });

                    // Event click handler
                    $(document).on('click', '.schedule-bar', function() {
                        const $event = $(this);
                        
                        // Destroy tooltip if it exists to prevent it from staying open
                        if (tooltipEnabled) {
                            $event.tooltip('hide');
                        }
                        
                        $('#event-date').text(formatDate(new Date($event.data('date'))));
                        $('#event-staff').text($event.data('staff'));
                        $('#event-hours').text($event.data('hours'));
                        $('#event-type').text($event.data('type'));
                        $('#eventDetailsModal').modal('show');
                    });

                    // Add console logging for event data
                    $(document).on('click', '.schedule-bar', function() {
                        const $event = $(this);
                        console.log({
                            date: $event.data('date'),
                            staff: $event.data('staff'), 
                            hours: $event.data('hours'),
                            type: $event.data('type')
                        });
                    });
                    
                    // Show current time indicator if viewing current week
                    function showCurrentTimeIndicator() {
                        const now = new Date();
                        const monday = getMonday(currentDate);
                        const nowMonday = getMonday(now);
                        
                        // Only show for current week
                        if (monday.getTime() !== nowMonday.getTime()) return;
                        
                        const dayOfWeek = now.getDay(); // 0 is Sunday
                        const dayIndex = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Convert to 0-indexed Monday-based
                        
                        const hours = now.getHours();
                        const minutes = now.getMinutes();
                        const timePosition = hours + (minutes / 60);
                        const leftPos = (timePosition / 24) * 100;
                        
                        // Find the time grid in the current day
                        const timeGrids = $('.time-grid');
                        if (dayIndex < timeGrids.length) {
                            const timeGrid = $(timeGrids[dayIndex]);
                            timeGrid.append(`
                                <div class="current-time-indicator" style="
                                    position: absolute;
                                    left: ${leftPos}%;
                                    top: 0;
                                    bottom: 0;
                                    width: 2px;
                                    background-color: red;
                                    z-index: 10;
                                "></div>
                            `);
                        }
                    }

                    
                    
                    // Call on initial load and after navigation
                    showCurrentTimeIndicator();

                    // Initial load
                    loadWeekData(currentDate);
                });

                function adjustTimeGridHeight() {
                    $('.time-grid').each(function() {
                        const scheduleContainer = $(this).find('.schedule-container');
                        const scheduleItems = scheduleContainer.find('.schedule-bar');
                        if (scheduleItems.length > 0) {
                            const lastItem = scheduleItems.last();
                            const bottomPosition = lastItem.position().top + lastItem.outerHeight() + 30;
                            $(this).css('height', Math.max(180, bottomPosition) + 'px');
                        }
                    });
                    
                    // Re-show current time indicator if needed
                    // showCurrentTimeIndicator();
                }
            </script>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('partials.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/nordpzbm/hr.nordictravels.tech/resources/views/supervisor/display-schedule.blade.php ENDPATH**/ ?>