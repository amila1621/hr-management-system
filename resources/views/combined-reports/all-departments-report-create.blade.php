{{-- filepath: c:\Users\it\Desktop\Projects\-nuthr\resources\views\combined-reports\all-departments-report-create.blade.php --}}
@extends('partials.main')

@section('content')
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">All Departments & Guides Monthly Reports</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">All Departments & Guides Monthly Reports</li>
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
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="mt-0 header-title">Select a Month</h4>
                                <form id="reportForm" action="{{ route('combined-reports.all-departments.monthly') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="month">Month and Year</label>
                                        <input type="month" value="{{ date('Y-m') }}" name="month" class="form-control" required onclick="this.showPicker()">
                                    </div>
                                    
                                    <button type="button" onclick="calculateAndFetch()" class="btn btn-success waves-effect waves-light ml-2">Calculate & Fetch</button>
                                    <button type="button" onclick="fetchLastMonth()" class="btn btn-secondary waves-effect waves-light ml-2">Fetch Last Month</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calculateAndFetch() {
            const monthInput = document.querySelector('input[name="month"]').value;
            if (!monthInput) {
                alert('Please select a month first');
                return;
            }
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Calculating...';
            button.disabled = true;
            
            calculateMonthHours(monthInput).then(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                document.getElementById('reportForm').submit();
            }).catch(error => {
                alert('Error calculating hours: ' + error);
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
        
        async function calculateMonthHours(monthYear) {
            const [year, month] = monthYear.split('-');
            const daysInMonth = new Date(year, month, 0).getDate();
            
            for (let day = 1; day <= daysInMonth; day++) {
                const dateString = `${year}-${month.padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                
                try {
                    const response = await fetch(`/calculateSalaryHours/${dateString}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin'
                    });
                    
                    if (!response.ok) {
                        console.warn(`Failed to calculate for ${dateString}: ${response.status}`);
                        continue;
                    }
                    
                    console.log(`Calculated hours for ${dateString}`);
                } catch (error) {
                    console.error(`Error calculating ${dateString}:`, error);
                }
            }
        }

                function fetchLastMonth() {
            const now = new Date();
            const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const yearMonth = lastMonth.getFullYear() + '-' + String(lastMonth.getMonth() + 1).padStart(2, '0');
            document.querySelector('input[name="month"]').value = yearMonth;
            
            // Show loading on the button
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Calculating...';
            button.disabled = true;
            
            // Calculate for last month first, then fetch
            calculateMonthHours(yearMonth).then(() => {
                // Reset button state
                button.innerHTML = originalText;
                button.disabled = false;
                // Submit the form properly
                document.getElementById('reportForm').submit();
            }).catch(error => {
                alert('Error calculating last month hours: ' + error);
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    </script>
@endsection