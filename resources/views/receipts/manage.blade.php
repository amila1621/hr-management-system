@extends('partials.main')

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Manage Receipts</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                            <li class="breadcrumb-item active">Manage Receipts</li>
                        </ol>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Receipt</th>
                                            <th>Note</th>
                                            <th>Status</th>
                                            <th>Submitted By</th>
                                            <th>Submitted Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($receipts as $receipt)
                                            <tr>
                                                <td>{{ $receipt->id }}</td>
                                                <td>
                                                    <a href="{{ Storage::url($receipt->receipt) }}" target="_blank">
                                                        <img src="{{ Storage::url($receipt->receipt) }}" 
                                                             alt="Receipt" 
                                                             class="img-thumbnail" 
                                                             style="max-width: 50px;">
                                                    </a>
                                                </td>
                                                <td>{{ $receipt->note }}</td>
                                                <td>
                                                    @if($receipt->status == 0)
                                                        <span class="badge badge-warning">Pending</span>
                                                    @elseif($receipt->status == 1)
                                                        <span class="badge badge-success">Approved</span>
                                                    @else
                                                        <span class="badge badge-danger">Rejected</span>
                                                    @endif
                                                </td>
                                                <td>{{ $receipt->user->name }}</td>
                                                <td>{{ $receipt->created_at->format('d.m.Y H:i') }}</td>
                                                <td>
                                                    <a href="{{ Storage::url($receipt->receipt) }}" 
                                                       class="btn btn-primary btn-sm" 
                                                       target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($receipt->status == 0 && (auth()->user()->role === 'admin' || auth()->id() === $receipt->user_id))
                                                        <form action="{{ route('receipts.destroy', $receipt->id) }}" 
                                                              method="POST" 
                                                              class="d-inline"
                                                              onsubmit="return confirm('Are you sure you want to delete this receipt?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">No receipts found</td>
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
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#datatable-buttons').DataTable({
            dom: 'Bfrtip',
            order: [[0, 'desc']]
        });
    });
</script>
@endsection

