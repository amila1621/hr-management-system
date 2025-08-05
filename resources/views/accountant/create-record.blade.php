@extends('partials.main')
@section('content')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Create Record</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Accountant Report</a>
                                    </li>
                                    <li class="breadcrumb-item active">Create Record</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('success') }}
                                    </div>
                                @endif
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <form id="periodForm" action="{{ route('accountant.records.store') }}" method="GET" class="d-flex gap-2">
                                            <select name="month" class="form-control" style="width: 200px;">
                                                @foreach(range(1, 12) as $month)
                                                    <option value="{{ $month }}" {{ $selectedMonth == $month ? 'selected' : '' }}>
                                                        {{ Carbon\Carbon::create()->month($month)->format('F') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <select name="year" class="form-control" style="width: 120px;">
                                                @foreach($years as $year)
                                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                                        {{ $year }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-secondary">Filter</button>
                                        </form>
                                    </div>
                                    <div style="text-align: right;" class="col-md-6 text-end">
                                        <button type="submit" form="recordForm" class="btn btn-primary">Save Records</button>
                                    </div>
                                </div>
                                <style>
                                    .form-check-input {
                                        width: 15px;
                                        height: 15px;
                                        cursor: pointer;
                                    }
                                    .table td.text-center {
                                        vertical-align: middle;
                                    }
                                    .dataTables_filter {
                                        margin-bottom: 15px;
                                    }
                                    .dataTables_filter input {
                                        border-radius: 4px;
                                        padding: 5px 10px;
                                    }
                                    .checkbox-cell {
                                        padding: 0 !important;
                                        height: 45px;
                                    }
                                    .checkbox-label {
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        width: 100%;
                                        height: 100%;
                                        margin: 0;
                                        cursor: pointer;
                                        padding: 10px;
                                    }
                                    .checkbox-label:hover {
                                        background-color: rgba(0,0,0,0.05);
                                    }
                                    .small {
                                        font-size: 1em;
                                    }
                                    .badge-existing {
                                        white-space: normal;
                                        text-align: left;
                                        font-size: 0.9em;
                                        display: block;
                                        align-items: center;
                                        justify-content: space-between;
                                        margin-bottom: 5px;
                                        padding: 5px 10px;
                                        width: -webkit-fill-available;
                                        border-radius: 4px;
                                    }

                                    .bg-success-light {
                                        background-color: rgba(40, 167, 69, 0.15);
                                        color: #ffffff;
                                        border-left: 3px solid #28a745;
                                    }

                                    .bg-danger-light {
                                        background-color: rgba(220, 53, 69, 0.15);
                                        color: #ffffff;
                                        border-left: 3px solid #dc3545;
                                    }

                                    .delete-record {
                                        color: rgba(0, 0, 0, 0.5);
                                        opacity: 0.7;
                                        transition: opacity 0.2s;
                                        float: right;
                                        margin-top: 2px;
                                    }

                                    .delete-record:hover {
                                        opacity: 1;
                                        color: #dc3545;
                                    }
                                    .gap-2 {
                                        gap: 0.5rem;
                                    }
                                  .table-responsive {
                                        max-height: 80vh; /* Set a maximum height for the scrollable area */
                                        overflow-y: auto;
                                    }

                                    #recordsTable thead th {
                                        position: sticky;
                                        top: 0;
                                        background-color: #2c3749; /* Match your table header background */
                                        z-index: 10;
                                        box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1); /* Optional: adds slight shadow for visual separation */
                                    }

                                    /* Ensure DataTables doesn't interfere with our sticky headers */
                                    .dataTables_scrollHead {
                                        overflow: visible !important;
                                    }
                                    .dataTables_scrollBody {
                                        overflow: visible !important;
                                    }
                                    .expense-type-selector {
                                        display: flex;
                                        margin-bottom: 5px;
                                    }

                                    .expense-type-option {
                                        flex: 1;
                                        text-align: center;
                                        padding: 6px;
                                        cursor: pointer;
                                        border: 1px solid #dee2e6;
                                        background-color: #f8f9fa;
                                        transition: all 0.3s;
                                        font-weight: 500;
                                    }

                                    .expense-type-option:first-child {
                                        border-radius: 4px 0 0 4px;
                                        border-right: none;
                                    }

                                    .expense-type-option:last-child {
                                        border-radius: 0 4px 4px 0;
                                        border-left: none;
                                    }

                                    .expense-type-option.payback.active {
                                        background-color: #023604;
                                        color: white;
                                        border-color: #023604;
                                        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
                                    }

                                    .expense-type-option.deduct.active {
                                        background-color: #542a35;
                                        color: white;
                                        border-color: #542a35;
                                        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
                                    }

                                    .expense-type-option.payback:hover:not(.active) {
                                        background-color: rgba(40, 167, 69, 0.1);
                                        color: #28a745;
                                    }

                                    .expense-type-option.deduct:hover:not(.active) {
                                        background-color: rgba(220, 53, 69, 0.1);
                                        color: #dc3545;
                                    }

                                    .input-group-with-type {
                                        display: flex;
                                        flex-direction: column;
                                        width: 100%;
                                    }
                                </style>
                                <form action="{{ route('accountant.records.add') }}" method="POST" id="recordForm">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="recordsTable">
                                            <thead>
                                                <tr>
                                                    <th>Full Name</th>
                                                    <th>Work Name</th>
                                                    @foreach($accountingTypes as $type)
                                                        @if(auth()->user()->hasAccess(Str::snake($type->name)))
                                                            <th>{{ $type->name }} ({{ $type->unit }})</th>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($guides as $guide)
                                                    <tr>
                                                        <td>{{ $guide->full_name }}</td>
                                                        <td>{{ $guide->name }}</td>
                                                        @foreach($accountingTypes as $type)
                                                            @if(auth()->user()->hasAccess(Str::snake($type->name)))
                                                                <td>
                                                                    <div class="input-group-with-type">
                                                                        <div class="expense-type-selector">
                                                                            <div class="expense-type-option payback active" data-type="payback" data-guide-id="{{ $guide->id }}" data-record-type="{{ $type->name }}">
                                                                                <i class="fas fa-plus"></i> Pay
                                                                            </div>
                                                                            <div class="expense-type-option deduct" data-type="deduct" data-guide-id="{{ $guide->id }}" data-record-type="{{ $type->name }}">
                                                                                <i class="fas fa-minus"></i> Deduct
                                                                            </div>
                                                                        </div>
                                                                        <input type="text" 
                                                                               name="records[{{ $guide->id }}][{{ $type->name }}]" 
                                                                               class="form-control"
                                                                               data-guide-id="{{ $guide->id }}"
                                                                               data-record-type="{{ $type->name }}">
                                                                        <input type="hidden" 
                                                                               name="expense_types[{{ $guide->id }}][{{ $type->name }}]" 
                                                                               value="payback" 
                                                                               class="expense-type-input"
                                                                               id="expense_type_{{ $guide->id }}_{{ Str::slug($type->name) }}">
                                                                        @foreach($existingRecords->where('user_id', $guide->id)->where('record_type', $type->name)->sortByDesc('date') as $record)
                                                                            <div class="mt-2 small d-flex align-items-center justify-content-between">
                                                                                <span class="badge-existing {{ $record->expense_type == 'payback' ? 'bg-success-light' : 'bg-danger-light' }}">
                                                                                    {{ \Carbon\Carbon::parse($record->date)->format('m.Y') }} - 
                                                                                    {{ $record->amount }}
                                                                                    @if(auth()->user()->role === 'admin')
                                                                                        <small class="ms-1">
                                                                                            (by {{ $record->creator->name ?? 'Unknown' }})
                                                                                        </small>
                                                                                    @endif
                                                                                    <i class="fas fa-times delete-record" 
                                                                                       data-record-id="{{ $record->id }}" 
                                                                                       style="cursor: pointer; margin-left: 5px;"></i>
                                                                                </span>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </td>
                                                            @endif
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary">Save Records</button>
                                    </div>
                                </form>
                                

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#recordsTable').DataTable({
            searching: true,
            ordering: true,
            paging: false,
            info: false,
            scrollCollapse: true,
            order: [[0, 'asc']], // Sort by guide name by default
            columnDefs: [
                {
                    targets: '_all',
                    orderable: false
                },
                {
                    targets: [0],
                    orderable: true
                }
            ],
            fixedHeader: true // Enable fixed headers
        });

        // Initialize DataTable for existing records
        $('#existingRecordsTable').DataTable({
            searching: true,
            ordering: true,
            paging: true,
            pageLength: 10,
            order: [[3, 'desc']], // Sort by date by default
            columnDefs: [
                {
                    targets: '_all',
                    orderable: true
                }
            ]
        });

        // Handle delete icon clicks
        $('.delete-record').click(function() {
            const deleteIcon = $(this);
            const recordBadge = deleteIcon.closest('.mt-2');
            
            if(confirm('Are you sure you want to delete this record?')) {
                const recordId = deleteIcon.data('record-id');
                
                $.ajax({
                    url: '{{ route("accountant.records.delete") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        record_id: recordId
                    },
                    success: function(response) {
                        if(response.success) {
                            // Fade out and remove just the badge containing this record
                            recordBadge.fadeOut(300, function() {
                                $(this).remove();
                            });
                        }
                    },
                    error: function() {
                        alert('Error deleting record');
                    }
                });
            }
        });

        // Handle expense type selection
        $('.expense-type-option').click(function() {
            const selectedType = $(this).data('type');
            const guideId = $(this).data('guide-id');
            const recordType = $(this).data('record-type');
            
            // Update button UI
            $(this).siblings().removeClass('active');
            $(this).addClass('active');
            
            // Update hidden input value
            const hiddenInput = $(`#expense_type_${guideId}_${recordType.replace(/\s+/g, '-').toLowerCase()}`);
            hiddenInput.val(selectedType);
            
            // Optionally, add visual indication to the input field
            const inputField = $(this).closest('.input-group-with-type').find('input.form-control');
            
            // Add a subtle color indicator based on expense type
            if (selectedType === 'payback') {
                inputField.css('border-left', '4px solid #28a745');  // Green for payback
            } else {
                inputField.css('border-left', '4px solid #dc3545');  // Red for deduct
            }
        });
    });
</script>
@endsection
