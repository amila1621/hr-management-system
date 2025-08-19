@extends('partials.main')

@section('content')

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <h4 class="page-title">Extra Hours Request</h4>
                <p class="text-muted">Submit requests for additional working hours beyond scheduled tour times</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Select Date to View Tours</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="tour_date">Select Date:</label>
                                        <input type="date" id="tour_date" class="form-control" 
                                               value="{{ request('date', date('Y-m-d')) }}"
                                               max="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-primary form-control" onclick="loadTours()">
                                            <i class="fas fa-search"></i> Load Tours
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="toursSection" style="display: none;">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Tours for Selected Date</h5>
                            </div>
                            <div class="card-body">
                                <div id="toursContainer">
                                    <!-- Tours will be loaded here via AJAX -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="requestForm" style="display: none;">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Submit Extra Hours Request</h5>
                            </div>
                            <div class="card-body">
                                <form id="extraHoursForm" method="POST" action="{{ route('guide.extra-hours-request.submit') }}">
                                    @csrf
                                    
                                    <input type="hidden" id="selected_tour_id" name="tour_id">
                                    <input type="hidden" id="extra_hours_minutes" name="extra_hours_minutes">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Selected Tour:</label>
                                                <div id="selectedTourInfo" class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle"></i> No tour selected
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="actual_end_time">Actual End Time:</label>
                                                <input type="datetime-local" id="actual_end_time" name="actual_end_time" 
                                                       class="form-control" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="explanation">Explanation (Required):</label>
                                        <textarea id="explanation" name="explanation" class="form-control" rows="4" 
                                                  required placeholder="Please explain why you need extra hours..."></textarea>
                                    </div>

                                    <div id="calculationResult" class="alert alert-info" style="display: none;">
                                        <h6><i class="fas fa-calculator"></i> Calculation Result:</h6>
                                        <p id="calculationText"></p>
                                        <button type="button" class="btn btn-success" onclick="confirmSubmission()">
                                            <i class="fas fa-check"></i> Confirm & Submit
                                        </button>
                                        <button type="button" class="btn btn-secondary ml-2" onclick="cancelCalculation()">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>

                                    <div class="form-group" id="calculateSection">
                                        <button type="button" class="btn btn-primary" onclick="calculateExtraHours()">
                                            <i class="fas fa-calculator"></i> Calculate Extra Hours
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
let selectedTour = null;

function loadTours() {
    const date = document.getElementById('tour_date').value;
    if (!date) {
        alert('Please select a date first.');
        return;
    }

    // Show loading
    document.getElementById('toursContainer').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading tours...</div>';
    document.getElementById('toursSection').style.display = 'block';
    document.getElementById('requestForm').style.display = 'none';

    // Make AJAX request
    fetch(`{{ route('guide.extra-hours-request.tours') }}?date=${date}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayTours(data.tours);
            } else {
                document.getElementById('toursContainer').innerHTML = `<div class="alert alert-warning">${data.message || 'Unknown error occurred'}</div>`;
            }
        })
        .catch(error => {
            console.error('Detailed Error:', error);
            document.getElementById('toursContainer').innerHTML = `<div class="alert alert-danger">Error loading tours: ${error.message}. Please check console for details.</div>`;
        });
}

function displayTours(tours) {
    const container = document.getElementById('toursContainer');
    
    if (tours.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No tours found for the selected date.</div>';
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-striped table-bordered">';
    html += '<thead class="thead-dark"><tr>';
    html += '<th><i class="fas fa-route"></i> Tour Name</th><th><i class="fas fa-clock"></i> Start Time</th><th><i class="fas fa-clock"></i> End Time</th><th><i class="fas fa-info-circle"></i> Status</th><th><i class="fas fa-cogs"></i> Action</th>';
    html += '</tr></thead><tbody>';

    tours.forEach(tour => {
        const canRequest = tour.can_request_extra_hours;
        const statusBadge = canRequest ? 
            '<span class="badge badge-success"><i class="fas fa-check"></i> Available</span>' : 
            '<span class="badge badge-warning"><i class="fas fa-hourglass-half"></i> Already Requested</span>';
        
        html += `<tr>
            <td><strong>${tour.tour_name}</strong></td>
            <td>${tour.start_time}</td>
            <td>${tour.end_time}</td>
            <td>${statusBadge}</td>
            <td>
                ${canRequest ? 
                    `<button type="button" class="btn btn-sm btn-primary" onclick="selectTour(${tour.id}, '${tour.tour_name}', '${tour.full_end_time}', '${tour.formatted_end_time}')">
                        <i class="fas fa-plus"></i> Select
                    </button>` :
                    '<button type="button" class="btn btn-sm btn-secondary" disabled>Not Available</button>'
                }
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function selectTour(tourId, tourName, endTime, formattedEndTime) {
    selectedTour = {
        id: tourId,
        name: tourName,
        end_time: endTime
    };

    document.getElementById('selected_tour_id').value = tourId;
    document.getElementById('selectedTourInfo').innerHTML = `
        <i class="fas fa-check-circle text-success"></i> <strong>${tourName}</strong><br>
        <small class="text-muted"><i class="fas fa-clock"></i> Original End Time: ${formattedEndTime}</small>
    `;

    // Set minimum datetime for actual_end_time
    try {
        const endDateTime = new Date(endTime);
        if (!isNaN(endDateTime.getTime())) {
            endDateTime.setMinutes(endDateTime.getMinutes() + 1); // At least 1 minute after
            document.getElementById('actual_end_time').min = endDateTime.toISOString().slice(0, 16);
        }
    } catch (e) {
        console.error('Error setting minimum time:', e);
    }
    
    document.getElementById('actual_end_time').value = '';

    document.getElementById('requestForm').style.display = 'block';
    document.getElementById('calculationResult').style.display = 'none';
    document.getElementById('calculateSection').style.display = 'block';

    // Scroll to form
    document.getElementById('requestForm').scrollIntoView({ behavior: 'smooth' });
}

function calculateExtraHours() {
    if (!selectedTour) {
        alert('Please select a tour first.');
        return;
    }

    const actualEndTime = document.getElementById('actual_end_time').value;
    const explanation = document.getElementById('explanation').value.trim();

    if (!actualEndTime) {
        alert('Please enter the actual end time.');
        return;
    }

    if (!explanation) {
        alert('Please provide an explanation.');
        return;
    }

    const originalEnd = new Date(selectedTour.end_time);
    const actualEnd = new Date(actualEndTime);

    if (actualEnd <= originalEnd) {
        alert('Actual end time must be after the original end time.');
        return;
    }

    const diffMs = actualEnd - originalEnd;
    const diffMinutes = Math.ceil(diffMs / (1000 * 60));
    const hours = Math.floor(diffMinutes / 60);
    const minutes = diffMinutes % 60;

    let timeText = hours > 0 ? `${hours}:${minutes.toString().padStart(2, '0')}` : `0:${minutes.toString().padStart(2, '0')}`;

    document.getElementById('extra_hours_minutes').value = diffMinutes;
    document.getElementById('calculationText').innerHTML = `
        You are requesting an extra <strong>${timeText}</strong> hours for the tour "${selectedTour.name}".<br>
        <small class="text-muted">Original End: ${originalEnd.toLocaleString()}</small><br>
        <small class="text-muted">Requested End: ${actualEnd.toLocaleString()}</small>
    `;

    document.getElementById('calculationResult').style.display = 'block';
    document.getElementById('calculateSection').style.display = 'none';
}

function confirmSubmission() {
    document.getElementById('extraHoursForm').submit();
}

function cancelCalculation() {
    document.getElementById('calculationResult').style.display = 'none';
    document.getElementById('calculateSection').style.display = 'block';
}

// Load tours for today by default
document.addEventListener('DOMContentLoaded', function() {
    loadTours();
});
</script>

@endsection