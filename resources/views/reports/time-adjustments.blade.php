@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Time Adjustment History</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event</th>
                    <th>Guide</th>
                    <th>Original End Time</th>
                    <th>Added Time</th>
                    <th>New End Time</th>
                    <th>Adjusted By</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustments as $adjustment)
                <tr>
                    <td>{{ $adjustment->created_at->format('d.m.Y H:i') }}</td>
                    <td>{{ $adjustment->event->name }}</td>
                    <td>{{ $adjustment->guide->name }}</td>
                    <td>{{ $adjustment->original_end_time->format('d.m.Y H:i') }}</td>
                    <td>{{ $adjustment->added_time }}</td>
                    <td>{{ $adjustment->new_end_time->format('d.m.Y H:i') }}</td>
                    <td>{{ $adjustment->adjuster->name }}</td>
                    <td>{{ $adjustment->note }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $adjustments->links() }}
    </div>
</div>
@endsection 