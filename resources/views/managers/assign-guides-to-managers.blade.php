@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Assign Guides to Managers</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Assign Guides</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                @if (session()->has('failed'))
                    <div class="alert alert-danger">
                        {{ session()->get('failed') }}
                    </div>
                @endif

                @if (session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Assign Guides to Managers</h4>
                                
                                <form action="{{ route('managers.assign-guides-store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <!-- Left side - Selected Guides -->
                                        <div class="col-md-8">
                                            <div class="form-group mb-4">
                                                <label for="manager_id">Select Bus Driver Supervisor</label>
                                                <select name="manager_id" id="manager_id" class="form-control" required>
                                                    <option value="">Choose a Bus Driver Supervisor</option>
                                                    @foreach($managers as $manager)
                                                        <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="selected-guides-section">
                                                <h5>Selected Guides</h5>
                                                <div class="selected-guides">
                                                    <!-- Selected guides will appear here -->
                                                    <div class="empty-state">
                                                        No guides selected. Click on guides from the right panel to assign them.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-primary">Save Assignments</button>
                                            </div>
                                        </div>

                                        <!-- Right side - Available Guides -->
                                        <div class="col-md-4">
                                            <div class="available-guides-section">
                                                <h5>Available Guides</h5>
                                                <div class="search-box mb-3">
                                                    <input type="text" class="form-control" id="guideSearch" placeholder="Search guides...">
                                                </div>
                                                <div class="available-guides">
                                                    @foreach($tourGuides as $guide)
                                                        <div class="guide-card" data-guide-id="{{ $guide->id }}">
                                                            <div class="guide-info">
                                                                <div class="guide-name">{{ $guide->name }}</div>
                                                                <small class="guide-role">Tour Guide</small>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="guide_ids" id="selectedGuideIds">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Current Assignments</h4>
                                <div class="table-responsive">
                                    <table class="table table-centered table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Manager</th>
                                                <th>Assigned Guides</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($currentAssignments as $managerId => $assignments)
                                                <tr>
                                                    <td>{{ $assignments->first()->manager->name }}</td>
                                                    <td>
                                                        @foreach($assignments as $assignment)
                                                            <span class="badge badge-info mr-1">
                                                                {{ $assignment->guide->name }}
                                                            </span>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center">No assignments found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   
@endsection

<style>
.available-guides-section {
    border-radius: 8px;
    padding: 15px;
    height: 100%;
}

.available-guides {
    max-height: 500px;
    overflow-y: auto;
}

.guide-card {
    display: flex;
    align-items: center;
    padding: 10px;

    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.guide-card:hover {
    transform: translateX(-3px);
}

.guide-card.selected {
    background: #38455C;
    border-color: #90caf9;
}

.guide-info {
    flex: 1;
}

.guide-name {
    font-weight: 500;
}

.guide-role {
    font-size: 0.85em;
}

.selected-guides-section {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    min-height: 200px;
}

.selected-guides {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.selected-guide-badge {
    display: inline-flex;
    align-items: center;
    background: #38455C;
    padding: 8px 15px;
    border-radius: 25px;
    margin-bottom: 8px;
}

.selected-guide-badge img {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    margin-right: 8px;
}

.remove-guide {
    margin-left: 8px;
    color: #dc3545;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
}

.empty-state {
    width: 100%;
    text-align: center;
    color: #666;
    padding: 20px;
    font-style: italic;
}

#guideSearch {
    border-radius: 20px;
    padding-left: 15px;
    padding-right: 15px;
}
</style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            let selectedGuides = new Set();

            // Manager selection change handler
            $('#manager_id').on('change', function() {
                const managerId = $(this).val();
                if (managerId) {
                    // Clear current selections
                    selectedGuides.clear();
                    $('.selected-guides').empty();
                    $('.guide-card').removeClass('selected');

                    // Fetch existing assignments for this manager
                    $.ajax({
                        url: `/admin/get-manager-guides/${managerId}`,
                        method: 'GET',
                        success: function(response) {
                            if (response.guides && response.guides.length > 0) {
                                response.guides.forEach(guide => {
                                    addGuideToSelection(guide.id);
                                });
                            } else {
                                // Show empty state if no guides assigned
                                $('.selected-guides').html(`
                                    <div class="empty-state">
                                        No guides selected. Click on guides from the right panel to assign them.
                                    </div>
                                `);
                            }
                        }
                    });
                }
            });

            // Helper function to add a guide to selection
            function addGuideToSelection(guideId) {
                const guideCard = $(`.guide-card[data-guide-id="${guideId}"]`);
                const guideName = guideCard.find('.guide-name').text();
                
                if (!selectedGuides.has(guideId)) {
                    selectedGuides.add(guideId);
                    guideCard.addClass('selected');
                    
                    // Remove empty state if present
                    $('.empty-state').remove();
                    
                    // Create and append the selected guide badge
                    const selectedBadge = `
                        <div class="selected-guide-badge" data-guide-id="${guideId}">
                            <span>${guideName}</span>
                            <span class="remove-guide">&times;</span>
                        </div>
                    `;
                    $('.selected-guides').append(selectedBadge);
                    updateHiddenInput();
                }
            }

            // Guide selection
            $('.guide-card').on('click', function() {
                const guideId = $(this).data('guide-id');
                const guideName = $(this).find('.guide-name').text();
                
                if (!selectedGuides.has(guideId)) {
                    selectedGuides.add(guideId);
                    $(this).addClass('selected');
                    
                    // Remove empty state if present
                    $('.empty-state').remove();
                    
                    // Create and append the selected guide badge
                    const selectedBadge = `
                        <div class="selected-guide-badge" data-guide-id="${guideId}">
                            <span>${guideName}</span>
                            <span class="remove-guide">&times;</span>
                        </div>
                    `;
                    $('.selected-guides').append(selectedBadge);
                    updateHiddenInput();
                }
            });

            // Remove guide from selection
            $(document).on('click', '.remove-guide', function(e) {
                e.stopPropagation();
                const badge = $(this).closest('.selected-guide-badge');
                const guideId = badge.data('guide-id');
                
                selectedGuides.delete(guideId);
                badge.remove();
                $(`.guide-card[data-guide-id="${guideId}"]`).removeClass('selected');
                
                // Show empty state if no guides are selected
                if (selectedGuides.size === 0) {
                    $('.selected-guides').html(`
                        <div class="empty-state">
                            No guides selected. Click on guides from the right panel to assign them.
                        </div>
                    `);
                }
                
                updateHiddenInput();
            });

            // Search functionality
            $('#guideSearch').on('input', function() {
                const searchTerm = $(this).val().toLowerCase().trim();
                
                $('.guide-card').each(function() {
                    const guideName = $(this).find('.guide-name').text().toLowerCase();
                    $(this).toggle(guideName.includes(searchTerm));
                });
            });

            // Update hidden input with selected guide IDs
            function updateHiddenInput() {
                $('#selectedGuideIds').val(Array.from(selectedGuides).join(','));
            }
        });
    </script>
