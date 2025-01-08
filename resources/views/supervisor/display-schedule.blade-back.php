@extends('partials.main')
@section('content')

<style>
    .calendar-container {
        margin: 20px 0;
        overflow-x: auto;
        
    }

    .calendar-view {
        display: none;
        min-width: 800px;
    }

    .calendar-view.active {
        display: block;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
    }

    .calendar-cell {
        border: 1px solid #ccc;
        padding: 5px;
        height: 240px; /* Height for date cells */
        position: relative;
        overflow: hidden;
    }

    .calendar-cell-day {
        height: 40px; /* Smaller height for day-of-week cells */
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .days-of-the-week {
        font-weight: 900;
        text-align: center;
        font-size: 0.8rem;
    }

    .date {
        font-weight: bold;
        margin-bottom: 5px;
    }

    .time-slot {
        position: absolute;
        font-size: 12px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        padding: 0 2px;
        
        color: black;
        box-sizing: border-box;
    }

    .navigation-buttons {
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .calendar-cell-info {
        height: auto;
        padding: 5px;
        font-size: 0.8rem;
        border-bottom: 1px solid #ccc;
        text-align: center;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .info-reception {
        font-weight: bold;
    }

    .info-midnight {
        font-style: italic;
    }

    .shift-indicator {
        font-weight: bold;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 30px; /* Adjust this value to make the cells smaller */
        font-size: 0.8rem;
    }

    @media (max-width: 768px) {
        .calendar-container {
            margin: 10px 0;
        }

        .calendar-cell {
            height: 180px;
        }

        .time-slot {
            font-size: 12px;
        }
    }

    #week-label {
        font-weight: bold;
        font-size: 1.1em;
        min-width: 200px;
        text-align: center;
    }
</style>

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
<div class="row">
    <div class="col-12">
    <div class="card">
        <div class="card-body">
            <div class="calendar-container">
                 
                <div class="row mb-3">
                    <div class="col">
                        <button id="week-view-btn" class="btn btn-primary">Week View</button>
                    </div>
                </div>
                <div id="week-view" class="calendar-view active">
                    <div class="navigation-buttons">
                        <button id="prev-week-btn" class="btn btn-secondary">&lt; Prev Week</button>
                        <span id="week-label" class="mx-2"></span>
                        <button id="next-week-btn" class="btn btn-secondary">Next Week &gt;</button>
                    </div>
                    <div class="calendar-grid" id="week-grid">
                        <!-- Week grid content will be dynamically generated -->
                    </div>
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
                            <h5 id="event-title"></h5>
                            <p><strong>Date:</strong> <span id="event-date"></span></p>
                            <p><strong>Time:</strong> <span id="event-time"></span></p>
                            <p><strong>Office Worker:</strong> <span id="event-staff"></span></p>
                            <p><strong>Hours:</strong> <span id="event-hours"></span></p>
                        </div>
                    </div>
                </div>

            <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

            <script>
            $(document).ready(function() {
                let currentDate = new Date("{{ $weekStart->format('Y-m-d') }}");
                let staffMembers = @json($staffMembers);

                function loadWeekData(date) {
                    console.log("Requesting data for week starting:", date.toISOString().split('T')[0]);
                    $.ajax({
                        url: "{{ route('supervisor.week-data') }}",
                        method: 'GET',
                        data: { week_start: date.toISOString().split('T')[0] },
                        success: function(response) {
                            console.log("Received data:", response);
                            renderWeekView(date, response.staffHours, response.receptionData, response.midnightPhoneStaff);
                            updateWeekLabel(date);
                        },
                        error: function(xhr, status, error) {
                            console.error("Error loading week data:", error);
                            console.error("Response text:", xhr.responseText);
                            alert("Error loading week data. Please check the console and try again.");
                        }
                    });
                }

                function renderWeekView(startDate, staffHours, receptionData, midnightPhoneStaff) {
                    console.log('Rendering week view for:', startDate);
                    const weekGrid = $('#week-grid');
                    weekGrid.empty();

                    const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                    // Add day of week labels
                    daysOfWeek.forEach(day => {
                        weekGrid.append(`<div class="calendar-cell calendar-cell-day"><strong class="days-of-the-week">${day}</strong></div>`);
                    });

                    // Add reception info cells
                    daysOfWeek.forEach((day, index) => {
                        const currentDate = new Date(startDate);
                        currentDate.setDate(startDate.getDate() + index);
                        const dateString = currentDate.toISOString().split('T')[0];
                        
                        weekGrid.append(`
                            <div class="calendar-cell calendar-cell-info">
                                <div class="info-reception">Reception: ${receptionData[dateString] || ''}</div>
                            </div>
                        `);
                    });

                    for (let i = 0; i < 7; i++) {
                        const currentDate = new Date(startDate);
                        currentDate.setDate(startDate.getDate() + i);
                        const dateString = currentDate.toISOString().split('T')[0];
                        const dayOfMonth = currentDate.getDate();

                        console.log(`Rendering day: ${dateString}, Day of month: ${dayOfMonth}`);

                        const dayCell = $(`<div class="calendar-cell" data-date="${dateString}">
                            <div class="date">${dayOfMonth}</div>
                        </div>`);

                        const dayEvents = [];

                        // Collect all events for the day
                        if (staffHours[dateString]) {
                            Object.entries(staffHours[dateString]).forEach(([staffId, hours]) => {
                                hours.forEach(hour => {
                                    if (hour.start_time && hour.end_time) {
                                        const startMinutes = timeToMinutes(hour.start_time);
                                        const endMinutes = timeToMinutes(hour.end_time);
                                        dayEvents.push({
                                            staff: staffMembers.find(s => s.id == staffId),
                                            startMinutes: startMinutes,
                                            endMinutes: endMinutes,
                                            hours: `${hour.start_time}-${hour.end_time}`
                                        });
                                    }
                                });
                            });
                        }

                        console.log(`Total events for ${dateString}:`, dayEvents.length);

                        // Sort events by start time
                        dayEvents.sort((a, b) => a.startMinutes - b.startMinutes);

                        // Handle overlapping events
                        const columns = [];
                        dayEvents.forEach(event => {
                            let column = 0;
                            while (columns[column] && columns[column] > event.startMinutes) {
                                column++;
                            }
                            columns[column] = event.endMinutes;

                            const width = 100 / (column + 1);
                            const left = column * width;

                            const eventElement = $(`
                                <div class="time-slot" 
                                     style="top: ${event.startMinutes / 6}px; 
                                            height: ${(event.endMinutes - event.startMinutes) / 6}px; 
                                            background-color: ${event.staff.color || '#ccc'};
                                            left: ${left}%;
                                            width: ${width}%;
                                            color: black;">
                                ${event.staff.name} <br> ${event.hours}
                                </div>
                            `);
                            dayCell.append(eventElement);
                        });

                        weekGrid.append(dayCell);
                    }

                    // Add midnight phone cells at the bottom
                    daysOfWeek.forEach((day, index) => {
                        const currentDate = new Date(startDate);
                        currentDate.setDate(startDate.getDate() + index);
                        const dateString = currentDate.toISOString().split('T')[0];

                        weekGrid.append(`
                            <div class="calendar-cell calendar-cell-info">
                                <div class="info-midnight">Midnight Phone: ${midnightPhoneStaff[dateString] || ''}</div>
                            </div>
                        `);
                    });

                    // Add OFF cells
                    daysOfWeek.forEach((day, index) => {
                        const currentDate = new Date(startDate);
                        currentDate.setDate(startDate.getDate() + index);
                        const dateString = currentDate.toISOString().split('T')[0];

                        const offStaff = [];
                        if (staffHours[dateString]) {
                            Object.entries(staffHours[dateString]).forEach(([staffId, hours]) => {
                                if (hours.some(hour => hour.type === 'X' || hour.type === 'V')) {
                                    offStaff.push(staffMembers.find(s => s.id == staffId).name);
                                }
                            });
                        }

                        const offCell = $('<div class="calendar-cell calendar-cell-info"></div>');
                        offCell.append('<div>Off:</div>');
                        if (offStaff.length > 0) {
                            offCell.append(`<div>${offStaff.join(', ')}</div>`);
                        }
                        weekGrid.append(offCell);
                    });
                }

                function updateWeekLabel(startDate) {
                    const endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + 6);
                    
                    const startFormatted = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const endFormatted = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    
                    $('#week-label').text(`${startFormatted} - ${endFormatted}`);
                }

                function timeToMinutes(timeString) {
                    if (!timeString) return 0;
                    const [hours, minutes] = timeString.split(':').map(Number);
                    return hours * 60 + (minutes || 0);
                }

                $('#prev-week-btn').click(function() {
                    currentDate.setDate(currentDate.getDate() - 7);
                    loadWeekData(currentDate);
                });

                $('#next-week-btn').click(function() {
                    currentDate.setDate(currentDate.getDate() + 7);
                    loadWeekData(currentDate);
                });

                // Initial load
                loadWeekData(currentDate);
            });
            </script>
        </div>
    </div>
</div>
    </div>
</div>
</div>
</div>
</div>

@endsection
