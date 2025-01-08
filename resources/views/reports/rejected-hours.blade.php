@extends('partials.main')

@section('content')

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
                <h4 class="page-title">Rejected Hours</h4>
                <p>Below are the guide hours that have been rejected. Admins can review and update the status if needed.</p>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Guide Name</th>
                            <th>Tour Date</th>
                            <th>Tour Name</th>
                            <th>Guide Start Time</th>
                            <th>Guide End Time</th>
                            <th>Guide Comment</th>
                            <th>Admin Comment</th>
                            <th>Guide Image</th>
                            <th>Admin Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rejectedEventSalaries as $rejection)
                            <tr class="">
                                <td>{{ $rejection->tourGuide->name }}</td>
                                <td>
                                    {{ $rejection->event && $rejection->event->start_time ? \Carbon\Carbon::parse($rejection->event->start_time)->format('d.m.Y') : 'N/A' }}
                                </td>
                                <td>{{ $rejection->event->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($rejection->guide_start_time)->format('d.m.Y H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($rejection->guide_end_time)->format('d.m.Y H:i') }}</td>
                                <td>{{ $rejection->guide_comment ?? 'No comment' }}</td>
                                <td>{{ $rejection->admin_comment ?? 'No comment' }}</td>
                                <td>
                                    @if($rejection->guide_image)
                                        <a href="{{ asset('storage/' . $rejection->guide_image) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $rejection->guide_image) }}" alt="Guide Image" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                                        </a>
                                    @else
                                        No image
                                    @endif
                                </td>
                                <td>
                                    <!-- Reject button -->
                                    <button class="btn btn-danger btn-sm" data-toggle="modal"
                                        data-target="#rejectModal{{ $rejection->id }}">Modify Time</button>
                                </td>
                            </tr>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal{{ $rejection->id }}" tabindex="-1" role="dialog"
                                aria-labelledby="rejectModalLabel{{ $rejection->id }}" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.modify-time', $rejection->id) }}" method="POST">
                                            @csrf
                                            @method('POST')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Modify Hours</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="admin_comment">Modify Comment</label>
                                                    <textarea class="form-control" name="approval_comment">{{ $rejection->admin_comment }}</textarea>
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label for="guide_start_time">Start Date & Time</label>
                                                    <input type="text" class="form-control flatpickr-datetime" name="guide_start_time" value="{{ $rejection->guide_start_time }}">
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label for="guide_end_time">End Date & Time</label>
                                                    <input type="text" class="form-control flatpickr-datetime" name="guide_end_time" value="{{ $rejection->guide_end_time }}">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-danger">Confirm</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
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
    });

    function initFlatpickr() {
        flatpickr(".flatpickr-datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });
    }
</script>

@endsection
