@extends('partials.main')

<link href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" rel="stylesheet">
<style>
/* Reset and base styles */
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}

/* Wrapper styles */
#wrapper {
    min-height: 100vh;
    position: relative;
}

/* Sidebar styles */
.left.side-menu {
    position: fixed;
    top: 70px; /* Match your header height */
    left: 0;
    width: 240px;
    height: calc(100vh - 70px);
    overflow-y: auto;
    z-index: 100;
}

/* Main content styles - KEY CHANGES HERE */
.content-page {
    position: relative;
    height: 100vh;
    overflow: hidden;
    margin-left: 240px; /* Match sidebar width */
}

.content {
    height: calc(100vh - 70px); /* Adjust based on your header height */
    overflow-y: auto;
    padding: 20px;
    padding-bottom: 60px; /* Add padding for footer */
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

/* Container styles */
.container-fluid {
    padding: 15px;
}

/* Card styles */
.card {
    margin-bottom: 20px;
    background: #fff;
}

/* Footer styles */
.footer {
    position: fixed;
    bottom: 0;
    right: 0;
    left: 240px; /* Match sidebar width */
    padding: 15px;
    background: #fff;
    z-index: 100;
}

/* Keep existing modal and other styles below */
.staff-table tbody td {
    padding: 15px;
    vertical-align: middle;
}

.color-dot {
    width: 70px;
    height: 30px;
    border-radius: 3%;
    display: inline-block;
    margin-right: 10px;
    transition: transform 0.2s;
}

.btn-update-color:hover {
    background: #2e59d9;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#colorPickerModal .modal-footer {
    padding: 15px 25px;
    border-top: 1px solid #eee;
}

.staff-email {
    color: #666;
}

.breadcrumb-item, 
.breadcrumb-item.active, 
.breadcrumb-item a {
    color: rgba(255, 255, 255, 0.8);
}

.breadcrumb-item + .breadcrumb-item::before {
    color: rgba(255, 255, 255, 0.5);
}

.btn-close {
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/0.8em auto no-repeat;
    opacity: .5;
    padding: 1rem;
}

.btn-close:hover {
    opacity: .75;
}

.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1060;
}

.toast {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    min-width: 250px;
}

.toast.success {
    border-left: 4px solid #28a745;
}

.toast.error {
    border-left: 4px solid #dc3545;
}

@keyframes slideIn {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

.toast {
    animation: slideIn 0.3s ease-out;
}

.toast-container {
    --bs-toast-zindex: 1060;
    --bs-toast-padding-x: 0.75rem;
    --bs-toast-padding-y: 0.5rem;
    --bs-toast-spacing: 1.5rem;
    --bs-toast-max-width: 350px;
}

.toast {
    opacity: 0;
    transition: opacity 0.15s linear;
}

.toast.showing {
    opacity: 1;
}

.toast.show {
    opacity: 1;
}

.toast.hide {
    display: none;
}

.btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.toast .toast-body {
    padding: 0.75rem;
    font-size: 0.875rem;
}

.toast.success {
    background-color: #28a745 !important;
}

.toast.error {
    background-color: #dc3545 !important;
}

#colorPickerModal .modal-body {
    padding: 20px;
    min-height: 300px;
}

#colorPicker {
    margin-top: 20px;
    min-height: 50px;
}

.pickr {
    display: flex;
    justify-content: center;
}

.picker-container {
    width: 100%;
    height: 40px;
    margin-bottom: 20px;
}

/* Ensure color picker is visible */
.pcr-app {
    position: fixed;
    z-index: 10000;
    position: fixed !important;
}

.pcr-button {
    width: 100%;
    height: 40px;
}
</style>

@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
        <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="page-title-box">
                                <h4 class="page-title">Staff Color Management</h4>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Colour Management</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

            @if (session()->has('success'))
                <div class="alert alert-success">
                    {{ session()->get('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-danger">
                    {{ session()->get('error') }}
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title">Staff Color Management</h4>
                            
                            @if($staffMembers->isEmpty())
                                <div class="alert alert-info">
                                    No staff members found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover staff-table">
                                        <thead>
                                            <tr>
                                                <th></th> <!-- Drag handle column -->
                                                <th>Staff Details</th>
                                                <th>Contact</th>
                                                <th>Current Color</th>
                                                <th>Actions</th>
                                                <th>Visible</th> <!-- New column -->
                                            </tr>
                                        </thead>
                                        <tbody id="sortable-staff">
                                            @foreach($staffMembers as $staff)
                                            <tr data-id="{{ $staff->id }}">
                                                <td class="drag-handle text-center" style="cursor:move;">
                                                    <i class="fas fa-bars"></i>
                                                </td>
                                                <td>
                                                    <div class="staff-name">{{ $staff->user->name }}</div>
                                                </td>
                                                <td>
                                                    <div class="staff-email">{{ $staff->user->email }}</div>
                                                    <small>{{ $staff->phone_number ?? 'No phone' }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="color-dot" 
                                                              style="background-color: {{ $staff->color ?? '#808080' }}">
                                                        </span>
                                                        <small class="text-muted">{{ $staff->color ?? '#808080' }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn btn-update-color"
                                                            style="background: #4e73df; color: white; padding: 8px 16px; border-radius: 6px; border: none;"
                                                            onclick="openColorPicker(this)"
                                                            data-staff-id="{{ $staff->id }}"
                                                            data-staff-name="{{ $staff->user->name }}"
                                                            data-current-color="{{ $staff->color ?? '#808080' }}">
                                                        <i class="fas fa-palette me-1"></i>
                                                        Update Color
                                                    </button>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input staff-visibility-toggle"
                                                               type="checkbox"
                                                               data-staff-id="{{ $staff->id }}"
                                                               {{ $staff->hide ? '' : 'checked' }}>
                                                        <label class="form-check-label">
                                                            {{ $staff->hide ? 'Hidden' : 'Visible' }}
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            <button id="save-order" class="btn btn-primary mt-3">Save Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Color Picker Modal -->
<div class="modal fade" id="colorPickerModal" tabindex="-1" role="dialog" aria-labelledby="colorPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="colorPickerModalLabel">
                    <i class="fas fa-palette me-2"></i>
                    Update Staff Color
                </h5>
            </div>
            <div class="modal-body">
                <p class="mb-3">Updating color for: <strong><span id="staffName"></span></strong></p>
                <div id="colorPicker"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveColor">
                    Save Color
                </button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>


<script>

$(function() {
    $('#sortable-staff').sortable({
        placeholder: "ui-state-highlight",
        handle: ".drag-handle"
    });

    $('#save-order').click(function() {
        let order = [];
        $('#sortable-staff tr').each(function() {
            order.push($(this).data('id'));
        });

        $.ajax({
            url: '{{ route("supervisor.staff.reorder") }}',
            method: 'POST',
            data: {
                order: order,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                showToast('Order saved!');
                location.reload();
            },
            error: function() {
                showToast('Failed to save order.', 'error');
            }
        });
    });
});

let currentPickr = null;
let currentStaffId = null;

// Replace the openColorPicker function with this updated version
function openColorPicker(button) {
    // Reset the color picker container first
    const pickerContainer = document.getElementById('colorPicker');
    pickerContainer.innerHTML = '<div class="picker-container"></div>';

    const staffId = button.getAttribute('data-staff-id');
    const staffName = button.getAttribute('data-staff-name');
    const currentColor = button.getAttribute('data-current-color');
    
    currentStaffId = staffId;
    document.getElementById('staffName').textContent = staffName;

    // Show modal using jQuery (Bootstrap 4)
    $('#colorPickerModal').modal('show');
    
    // Initialize color picker after modal is shown
    $('#colorPickerModal').one('shown.bs.modal', function() {
        try {
            if (currentPickr) {
                currentPickr.destroyAndRemove();
                currentPickr = null;
            }
            
            currentPickr = Pickr.create({
                el: '.picker-container',
                theme: 'classic',
                default: currentColor || '#000000',
                swatches: [
                    '#1abc9c', '#2ecc71', '#3498db', '#9b59b6',
                    '#34495e', '#16a085', '#27ae60', '#2980b9',
                    '#8e44ad', '#2c3e50', '#f1c40f', '#e67e22',
                    '#e74c3c', '#ecf0f1', '#95a5a6', '#f39c12'
                ],
                components: {
                    preview: true,
                    opacity: false,
                    hue: true,
                    interaction: {
                        hex: true,
                        rgba: false,
                        input: true,
                        clear: false,
                        save: true
                    }
                }
            });
        } catch (e) {
            console.error('Picker creation error:', e);
        }
    });
}

// Update the modal cleanup handler
$('#colorPickerModal').on('hidden.bs.modal', function() {
    if (currentPickr) {
        try {
            currentPickr.destroyAndRemove();
        } catch (e) {
            console.log('Cleanup error:', e);
        }
        currentPickr = null;
    }
    currentStaffId = null;
    
    // Reset the color picker container
    const pickerContainer = document.getElementById('colorPicker');
    pickerContainer.innerHTML = '';
});

// Update the close button handlers to use jQuery
$(document).ready(function() {
    // For modal close buttons
    $('#colorPickerModal [data-dismiss="modal"]').on('click', function() {
        $('#colorPickerModal').modal('hide');
    });
});

// Replace the saveColor event listener with this version
document.getElementById('saveColor').addEventListener('click', function() {
    if (!currentPickr || !currentStaffId) return;

    const button = this;
    const color = currentPickr.getColor().toHEXA().toString();

    // Show loading state
    button.disabled = true;

    fetch(`/staff/${currentStaffId}/update-color`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ color: color })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const staffRow = document.querySelector(`button[data-staff-id="${currentStaffId}"]`).closest('tr');
            staffRow.querySelector('.color-dot').style.backgroundColor = color;
            staffRow.querySelector('.text-muted').textContent = color;
            staffRow.querySelector('.btn-update-color').setAttribute('data-current-color', color);

            // Close modal using jQuery (Bootstrap 4)
            $('#colorPickerModal').modal('hide');
            
            // Show success message
            showToast('Staff color updated successfully');
        } else {
            showToast('Failed to update color', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to update color', 'error');
    })
    .finally(() => {
        // Reset loading state
        button.disabled = false;
    });
});

document.getElementById('colorPickerModal').addEventListener('hidden.bs.modal', function() {
    if (currentPickr) {
        currentPickr.destroyAndRemove();
        currentPickr = null;
    }
    currentStaffId = null;
});

// Replace your existing toast implementation with this:
function showToast(message, type = 'success') {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center border-0 ${type === 'success' ? 'bg-success' : 'bg-danger'} text-white`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, {
        animation: true,
        autohide: true,
        delay: 3000
    });
    
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1060';
    document.body.appendChild(container);
    return container;
}

// Update the modal close functionality
document.addEventListener('DOMContentLoaded', function() {
    // For modal close button
    const modal = document.getElementById('colorPickerModal');
    const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"]');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    });
});

// Add this script at the bottom of your file
document.addEventListener('DOMContentLoaded', function() {
    // Handle modal scrolling
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            document.body.style.paddingRight = scrollbarWidth + 'px';
            document.body.style.overflow = 'hidden';
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.style.paddingRight = '';
            document.body.style.overflow = '';
        });
    });

});

document.addEventListener('DOMContentLoaded', function() {
    // Reset any overflow restrictions
    document.body.style.overflow = '';
    
    // Only restrict overflow when modal is open
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            document.body.style.overflow = 'hidden';
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.style.overflow = '';
        });
    });
});

$(document).on('change', '.staff-visibility-toggle', function() {
    var staffId = $(this).data('staff-id');
    var hide = $(this).is(':checked') ? 0 : 1;
    var $label = $(this).closest('.form-check').find('.form-check-label');
    $.ajax({
        url: '/staff/' + staffId + '/toggle-visibility',
        method: 'POST',
        data: {
            hide: hide,
            _token: '{{ csrf_token() }}'
        },
        success: function(res) {
            if(res.success) {
                $label.text(hide ? 'Hidden' : 'Visible');
                showToast('Visibility updated!');
            } else {
                showToast('Failed to update visibility', 'error');
            }
        },
        error: function() {
            showToast('Failed to update visibility', 'error');
        }
    });
});
</script>
@endsection
