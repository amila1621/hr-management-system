@extends('partials.main')
@section('content')

    <!-- Add Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <div class="content-page">
        <!-- Start content -->
        <div class="content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Create Manual Tour</h4>
                            <ol class="breadcrumb p-0 m-0">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item"><a href="#">Tours</a></li>
                                <li class="breadcrumb-item active">Create Manual Tour</li>
                            </ol>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Create a New Tour Manually</h4>
                                
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if (session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if (session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <form id="manualTourForm" action="{{ route('tours.store-manual') }}" method="POST" onsubmit="return validateShiftDurations()">
                                    @csrf
                                    <div class="form-group">
                                        <label for="tourName">Tour Name</label>
                                        <input type="text" name="tourName" id="tourName" class="form-control" required>
                                    </div>
                                    <div id="guideFieldsContainer">
                                        <!-- Guide entries will be added here dynamically -->
                                    </div>
                                    <button type="button" class="btn btn-secondary" onclick="addGuideEntry()">Add Guide</button>
                                    <button type="submit" class="btn btn-primary">Submit Tour</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- container-fluid -->

        </div>
        <!-- content -->

    <!-- Add Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            addGuideEntry(); // Add the first guide entry by default
            initFlatpickr(); // Initialize Flatpickr for existing inputs
        });

        function addGuideEntry() {
            var guideFieldsContainer = document.getElementById('guideFieldsContainer');
            var guideCount = guideFieldsContainer.getElementsByClassName('guide-entry').length;
            var newGuideEntry = `
                <div class="card mb-3 guide-entry">
                    <div class="card-body">
                        <h5 class="card-title">Guide ${guideCount + 1}</h5>
                        <div class="form-group">
                            <label for="guideName${guideCount}">Select Guide</label>
                            <select name="guides[${guideCount}][name]" id="guideName${guideCount}" class="form-control" required>
                                <option value="" disabled selected>Select Guide</option>
                                @foreach($guides as $guide)
                                    <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="startTime${guideCount}">Start Time</label>
                            <input type="text" name="guides[${guideCount}][startTime]" id="startTime${guideCount}" class="form-control flatpickr-datetime" >
                        </div>
                        <div class="form-group">
                            <label for="endTime${guideCount}">End Time</label>
                            <input type="text" name="guides[${guideCount}][endTime]" id="endTime${guideCount}" class="form-control flatpickr-datetime" >
                        </div>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeGuideEntry(this)">Remove Guide</button>
                    </div>
                </div>
            `;
            guideFieldsContainer.insertAdjacentHTML('beforeend', newGuideEntry);
            initFlatpickr();
        }

        function removeGuideEntry(button) {
            button.closest('.guide-entry').remove();
        }

        function initFlatpickr() {
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
            });

            flatpickr(".flatpickr-datetime", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true
            });
        }

        function validateShiftDurations() {
            const guideEntries = document.getElementsByClassName('guide-entry');
            let longShiftFound = false;

            for (let entry of guideEntries) {
                const startTime = new Date(entry.querySelector('[name*="[startTime]"]').value);
                const endTime = new Date(entry.querySelector('[name*="[endTime]"]').value);
                const guideName = entry.querySelector('[name*="[name]"]').options[
                    entry.querySelector('[name*="[name]"]').selectedIndex
                ].text;

                const hoursDiff = (endTime - startTime) / (1000 * 60 * 60);

                if (hoursDiff > 10) {
                    longShiftFound = true;
                    if (!confirm(`Warning: ${guideName} has a shift longer than 10 hours (${hoursDiff.toFixed(1)} hours). Do you want to continue?`)) {
                        return false;
                    }
                }
            }

            return true;
        }
    </script>

@endsection
