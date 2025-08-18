@php
    // Check if current user should be restricted from editing this staff member's hours
    $currentUserEmail                        @if(!$isOwnRoster && $isApproved !== 1)
                <div class="col-auto">
                    <label class="form-label small" style="color: #a8b5c8;">&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="removeTimeEntry(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endifer()->email ?? '';
    $isRestrictedUser = in_array($currentUserEmail, ['beatriz@nordictravels.eu', 'semi@nordictravels.eu']);
    $isOwnRoster = $isRestrictedUser && $currentUserEmail === $staff->email;
    
    $isOnCall = false;
    $isReception = false;
    $isSpecialType = false;
    $displayStartTime = '';
    $displayEndTime = '';
    $hiddenValue = '';
    
    // Parse time range data
    if ($timeRange) {
        if (is_array($timeRange)) {
            if (isset($timeRange['type']) && $timeRange['type'] === 'on_call') {
                $isOnCall = true;
                $displayStartTime = $timeRange['start_time'] ?? '';
                $displayEndTime = $timeRange['end_time'] ?? '';
                $hiddenValue = json_encode($timeRange);
            } elseif (isset($timeRange['type']) && $timeRange['type'] === 'reception') {
                $isReception = true;
                $displayStartTime = $timeRange['start_time'] ?? '';
                $displayEndTime = $timeRange['end_time'] ?? '';
                $hiddenValue = json_encode($timeRange);
            } elseif (isset($timeRange['type']) && in_array($timeRange['type'], ['V', 'X', 'H', 'SL'])) {
                $isSpecialType = true;
                $hiddenValue = $timeRange['type'];
            } elseif (isset($timeRange['start_time']) && isset($timeRange['end_time'])) {
                $displayStartTime = $timeRange['start_time'];
                $displayEndTime = $timeRange['end_time'];
                // Preserve the full JSON object to maintain notes and other data
                $hiddenValue = json_encode($timeRange);
            }
        } elseif (is_string($timeRange)) {
            if (in_array($timeRange, ['V', 'X', 'H', 'SL'])) {
                $isSpecialType = true;
                $hiddenValue = $timeRange;
            } elseif (strpos($timeRange, '-') !== false) {
                list($displayStartTime, $displayEndTime) = explode('-', $timeRange);
                $hiddenValue = $timeRange;
            } else {
                $hiddenValue = $timeRange;
            }
        }
    }
@endphp

<div class="time-picker-group {{ $isApproved == 0 ? 'unapproved-entry' : '' }}" 
     data-time-entry="{{ $index }}"
     data-staff-id="{{ $staff->id }}"
     data-date="{{ $dateString }}">
    
    <!-- Hidden input for form submission -->
    <input type="hidden" 
           name="hours[{{ $staff->id }}][{{ $dateString }}][]"
           value="{{ $hiddenValue }}"
           class="time-range-input {{ $isOwnRoster ? 'own-roster-disabled' : '' }}"
           {{ $isOwnRoster ? 'disabled' : '' }}>
    
    @if($isSpecialType)
        <!-- Special Type Display (V, X, H, SL) -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="special-type-display">
                <span class="badge bg-secondary fs-6 p-2">
                    @switch($hiddenValue)
                        @case('V') Vacation @break
                        @case('X') Day Off @break
                        @case('H') Holiday @break
                        @case('SL') Sick Leave @break
                        @default {{ $hiddenValue }}
                    @endswitch
                </span>
            </div>
            @if(!$isOwnRoster)
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            onclick="convertToRegularHours(this)"
                            title="Convert to regular time entry">
                        <i class="fas fa-clock"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            onclick="removeTimeEntry(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            @endif
        </div>
    @else
        <!-- Regular Time Entry -->
        <div class="row align-items-center">
            <div class="col">
                <!-- Start Time -->
                <label class="form-label small text-muted" style="color: #a8b5c8;">Start Time</label>
                <input type="time" 
                       class="form-control form-control-mobile time-start {{ $isApproved === 1 ? 'approved-input' : '' }}"
                       value="{{ $displayStartTime }}"
                       {{ ($isOwnRoster || $isApproved === 1) ? 'disabled' : '' }}
                       {{ $isApproved === 1 ? 'title="This record has been approved and cannot be modified"' : '' }}
                       onchange="updateTimeRange(this)"
                       @if($isApproved === 1) style="cursor: not-allowed; pointer-events: none;" @endif>
            </div>
            <div class="col-auto px-2 mt-4">
                <i class="fas fa-arrow-right text-muted" style="color: #a8b5c8;"></i>
            </div>
            <div class="col">
                <!-- End Time -->
                <label class="form-label small text-muted" style="color: #a8b5c8;">End Time</label>
                <input type="time" 
                       class="form-control form-control-mobile time-end {{ $isApproved === 1 ? 'approved-input' : '' }}"
                       value="{{ $displayEndTime }}"
                       {{ ($isOwnRoster || $isApproved === 1) ? 'disabled' : '' }}
                       {{ $isApproved === 1 ? 'title="This record has been approved and cannot be modified"' : '' }}
                       onchange="updateTimeRange(this)"
                       @if($isApproved === 1) style="cursor: not-allowed; pointer-events: none;" @endif>
            </div>
            @if(!$isOwnRoster)
                <div class="col-auto">
                    <label class="form-label small" style="color: #a8b5c8;">&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="removeTimeEntry(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endif
        </div>
        
        @if($isOnCall)
            <div class="mt-2">
                <span class="badge bg-success">
                    <i class="fas fa-phone me-1"></i>On Call
                </span>
            </div>
        @endif
        
        @if($isReception)
            <div class="mt-2">
                <span class="badge bg-info">
                    <i class="fas fa-desktop me-1"></i>Reception
                </span>
            </div>
        @endif
    @endif
    
    @if($isApproved === 1)
        <!-- Approved Record Indicator -->
        <div class="mt-2 text-center">
            <span class="badge bg-success">
                <i class="fas fa-check-circle me-1"></i>APPROVED - Cannot be modified
            </span>
        </div>
    @elseif(!$isOwnRoster && !$isSpecialType)
        <!-- Quick Action Buttons -->
        @php
            // Check current user's department for department-specific buttons
            $currentUserDepartment = '';
            if (Auth::user()->staff) {
                $currentUserDepartment = Auth::user()->staff->department;
            }
            
            // Departments that should have On Call and Reception buttons
            $departmentsWithPhoneReception = ['Operations', 'HR', 'Booking'];
            $showPhoneReceptionButtons = false;
            
            if ($currentUserDepartment) {
                foreach ($departmentsWithPhoneReception as $allowedDept) {
                    // Check for exact match or if department contains the allowed department name
                    if ($currentUserDepartment === $allowedDept || 
                        str_contains(strtolower($currentUserDepartment), strtolower($allowedDept))) {
                        $showPhoneReceptionButtons = true;
                        break;
                    }
                }
            }
        @endphp
        
        <div class="mt-3">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="applyQuickFill(this, 'V')" title="Vacation">V</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="applyQuickFill(this, 'X')" title="Day Off">X</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="applyQuickFill(this, 'H')" title="Holiday">H</button>
                
                @if($showPhoneReceptionButtons)
                    <button type="button" class="btn btn-outline-success btn-sm" 
                            onclick="applyQuickFill(this, 'on_call')" title="On Call">📞</button>
                    <button type="button" class="btn btn-outline-info btn-sm" 
                            onclick="applyQuickFill(this, 'reception')" title="Reception">💻</button>
                @endif
                
                <button type="button" class="btn btn-outline-primary btn-sm" 
                        onclick="applyQuickFill(this, 'regular')" title="Regular Hours">⏰</button>
            </div>
        </div>
    @endif
    
    <!-- Notes Field -->
    @if(!$isOwnRoster)
        <div class="mt-3">
            <label class="form-label small text-muted" style="color: #a8b5c8;">
                <i class="fas fa-sticky-note me-1"></i>Notes (Optional)
            </label>
            <textarea class="form-control form-control-mobile notes-input" 
                     rows="2" 
                     placeholder="Add notes for this time entry..."
                     onchange="updateTimeRangeWithNotes(this)"
                     style="font-size: 14px; background-color: #2d3748; border-color: #4a5568; color: #e2e8f0;">{{ isset($timeRange['notes']) ? $timeRange['notes'] : '' }}</textarea>
        </div>
    @endif
    
    @if($isOwnRoster)
        <!-- Restricted Access Message -->
        <div class="mt-2 text-center">
            <small class="text-muted" style="color: #a8b5c8;">
                <i class="fas fa-lock me-1"></i>Cannot edit own roster
            </small>
        </div>
    @endif
</div>

<script>
// Update time range when time inputs change
function updateTimeRange(input) {
    // CRITICAL FIX: Check if this is an approved record
    if (input.classList.contains('approved-input') || input.disabled) {
        MobileApp.showError('This record has been approved and cannot be modified');
        return;
    }
    
    const container = input.closest('.time-picker-group');
    const startInput = container.querySelector('.time-start');
    const endInput = container.querySelector('.time-end');
    const hiddenInput = container.querySelector('.time-range-input');
    const notesInput = container.querySelector('.notes-input');
    
    if (startInput && endInput && hiddenInput) {
        if (startInput.value && endInput.value) {
            // Validate time range
            if (startInput.value >= endInput.value) {
                MobileApp.showError('Start time must be before end time');
                return;
            }
            
            // Always create JSON object to preserve notes
            const timeData = {
                start_time: startInput.value,
                end_time: endInput.value,
                type: 'normal'
            };
            
            // Add notes if available
            if (notesInput && notesInput.value.trim()) {
                timeData.notes = notesInput.value.trim();
            }
            
            hiddenInput.value = JSON.stringify(timeData);
            hasUnsavedChanges = true;
            MobileApp.vibrate([30]);
        } else {
            hiddenInput.value = '';
        }
    }
}

// Update time range with notes only
function updateTimeRangeWithNotes(notesInput) {
    const container = notesInput.closest('.time-picker-group');
    const startInput = container.querySelector('.time-start');
    const endInput = container.querySelector('.time-end');
    const hiddenInput = container.querySelector('.time-range-input');
    
    if (startInput && endInput && hiddenInput && startInput.value && endInput.value) {
        // Always create JSON object to preserve all data
        const timeData = {
            start_time: startInput.value,
            end_time: endInput.value,
            type: 'normal'
        };
        
        // Add notes if available
        if (notesInput.value.trim()) {
            timeData.notes = notesInput.value.trim();
        }
        
        hiddenInput.value = JSON.stringify(timeData);
        hasUnsavedChanges = true;
        MobileApp.vibrate([30]);
    }
}

// Apply quick fill options
function applyQuickFill(button, value) {
    console.log(`🎯 applyQuickFill called with value: ${value}`);
    
    const container = button.closest('.time-picker-group');
    if (!container) {
        console.error('❌ Time picker group container not found');
        MobileApp.showError('Container not found');
        return;
    }
    
    // CRITICAL FIX: Check if this is an approved record
    const approvedInputs = container.querySelectorAll('.approved-input');
    if (approvedInputs.length > 0) {
        MobileApp.showError('This record has been approved and cannot be modified');
        return;
    }
    
    const hiddenInput = container.querySelector('.time-range-input');
    const startInput = container.querySelector('.time-start');
    const endInput = container.querySelector('.time-end');
    const notesInput = container.querySelector('.notes-input');
    
    console.log('📋 Found elements:', {
        hiddenInput: !!hiddenInput,
        startInput: !!startInput,
        endInput: !!endInput,
        notesInput: !!notesInput
    });
    
    switch(value) {
        case 'V':
        case 'X':  
        case 'H':
            console.log(`🏷️ Setting special type: ${value}`);
            if (hiddenInput) hiddenInput.value = value;
            if (startInput) startInput.value = '';
            if (endInput) endInput.value = '';
            
            // Instead of reloading, show immediate feedback
            const buttonGroup = container.querySelector('.btn-group');
            if (buttonGroup) {
                buttonGroup.style.backgroundColor = '#f8f9fa';
                buttonGroup.innerHTML = `<div class="text-center p-2"><span class="badge bg-secondary fs-6">${getTypeLabel(value)}</span></div>`;
            }
            
            hasUnsavedChanges = true;
            console.log(`✅ Applied ${value} successfully`);
            break;
            
        case 'on_call':
            console.log('📞 Setting on call hours');
            const onCallData = {
                start_time: '09:00',
                end_time: '17:00',
                type: 'on_call'
            };
            // Add notes if available
            if (notesInput && notesInput.value.trim()) {
                onCallData.notes = notesInput.value.trim();
            }
            if (hiddenInput) hiddenInput.value = JSON.stringify(onCallData);
            if (startInput) startInput.value = '09:00';
            if (endInput) endInput.value = '17:00';
            hasUnsavedChanges = true;
            console.log('✅ Applied on call successfully');
            break;
            
        case 'reception':
            console.log('💻 Setting reception hours');
            const receptionData = {
                start_time: '09:00',
                end_time: '17:00',
                type: 'reception'
            };
            // Add notes if available
            if (notesInput && notesInput.value.trim()) {
                receptionData.notes = notesInput.value.trim();
            }
            if (hiddenInput) hiddenInput.value = JSON.stringify(receptionData);
            if (startInput) startInput.value = '09:00';
            if (endInput) endInput.value = '17:00';
            hasUnsavedChanges = true;
            console.log('✅ Applied reception successfully');
            break;
            
        case 'regular':
            console.log('⏰ Setting regular hours');
            if (startInput) startInput.value = '09:00';
            if (endInput) endInput.value = '17:00';
            
            // Always use JSON format for consistency
            const regularData = {
                start_time: '09:00',
                end_time: '17:00',
                type: 'normal'
            };
            // Add notes if available
            if (notesInput && notesInput.value.trim()) {
                regularData.notes = notesInput.value.trim();
            }
            if (hiddenInput) hiddenInput.value = JSON.stringify(regularData);
            
            hasUnsavedChanges = true;
            console.log('✅ Applied regular hours successfully');
            break;
            
        default:
            console.error(`❌ Unknown quick fill value: ${value}`);
            MobileApp.showError(`Unknown action: ${value}`);
            return;
    }
    
    MobileApp.vibrate([50]);
    MobileApp.showSuccess(`Applied ${getTypeLabel(value)} successfully!`);
}

// Helper function to get readable labels
function getTypeLabel(value) {
    switch(value) {
        case 'V': return 'Vacation';
        case 'X': return 'Day Off';
        case 'H': return 'Holiday';
        case 'on_call': return 'On Call';
        case 'reception': return 'Reception';
        case 'regular': return 'Regular Hours';
        default: return value;
    }
}

// Convert special type entry to regular hours
function convertToRegularHours(button) {
    console.log('🔄 convertToRegularHours called');
    
    const container = button.closest('.time-picker-group');
    if (!container) {
        console.error('❌ Time picker group container not found');
        MobileApp.showError('Container not found');
        return;
    }
    
    MobileApp.confirm('Convert this entry to regular working hours?', () => {
        const hiddenInput = container.querySelector('.time-range-input');
        const specialDisplay = container.querySelector('.special-type-display').parentElement;
        
        // Clear the hidden input value to make it a regular entry
        if (hiddenInput) hiddenInput.value = '';
        
        // Replace the special type display with regular time inputs
        const regularHoursHTML = `
            <div class="row align-items-center">
                <div class="col">
                    <!-- Start Time -->
                    <label class="form-label small" style="color: #a8b5c8;">Start Time</label>
                    <input type="time" 
                           class="form-control form-control-mobile time-start"
                           value="09:00"
                           onchange="updateTimeRange(this)"
                           style="background: #38455c; color: #dee2e6; border-color: #4a5568;">
                </div>
                <div class="col-auto px-2 mt-4">
                    <i class="fas fa-arrow-right" style="color: #a8b5c8;"></i>
                </div>
                <div class="col">
                    <!-- End Time -->
                    <label class="form-label small" style="color: #a8b5c8;">End Time</label>
                    <input type="time" 
                           class="form-control form-control-mobile time-end"
                           value="17:00"
                           onchange="updateTimeRange(this)"
                           style="background: #38455c; color: #dee2e6; border-color: #4a5568;">
                </div>
                <div class="col-auto">
                    <label class="form-label small" style="color: #a8b5c8;">&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="removeTimeEntry(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Quick Action Buttons -->
            <div class="mt-3">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                            onclick="applyQuickFill(this, 'V')" title="Vacation">V</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                            onclick="applyQuickFill(this, 'X')" title="Day Off">X</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                            onclick="applyQuickFill(this, 'H')" title="Holiday">H</button>
                    ${ (typeof showPhoneReceptionButtons !== 'undefined' && showPhoneReceptionButtons) ? 
                        '<button type="button" class="btn btn-outline-success btn-sm" onclick="applyQuickFill(this, \'on_call\')" title="On Call">📞</button>' : '' }
                    ${ (typeof showPhoneReceptionButtons !== 'undefined' && showPhoneReceptionButtons) ? 
                        '<button type="button" class="btn btn-outline-info btn-sm" onclick="applyQuickFill(this, \'reception\')" title="Reception">💻</button>' : '' }
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            onclick="applyQuickFill(this, 'regular')" title="Regular Hours">⏰</button>
                </div>
            </div>
        `;
        
        // Replace the special display
        specialDisplay.outerHTML = regularHoursHTML;
        
        // Set default values and update hidden input
        if (hiddenInput) hiddenInput.value = '09:00-17:00';
        
        hasUnsavedChanges = true;
        MobileApp.vibrate([50]);
        MobileApp.showSuccess('Converted to regular hours');
        
        console.log('✅ Converted special type to regular hours');
    });
}

// Remove time entry
function removeTimeEntry(button) {
    console.log('🗑️ removeTimeEntry called');
    
    const container = button.closest('.time-picker-group');
    
    // CRITICAL FIX: Check if this is an approved record
    const approvedInputs = container.querySelectorAll('.approved-input');
    if (approvedInputs.length > 0) {
        MobileApp.showError('This record has been approved and cannot be modified');
        return;
    }
    
    const dayContent = container.closest('.day-content');
    
    // Check if this time entry is inside a wrapper (newly added entries)
    const wrapper = container.closest('.time-entry-wrapper');
    
    // Count all time entries (both original and wrapped)
    const originalTimeEntries = dayContent.querySelectorAll('.time-entry .time-picker-group');
    const wrappedTimeEntries = dayContent.querySelectorAll('.time-entry-wrapper .time-picker-group');
    const totalTimeEntries = originalTimeEntries.length + wrappedTimeEntries.length;
    
    console.log(`📊 Found ${originalTimeEntries.length} original entries, ${wrappedTimeEntries.length} wrapped entries, ${totalTimeEntries} total`);
    
    if (totalTimeEntries > 1) {
        MobileApp.confirm('Remove this time entry?', () => {
            if (wrapper) {
                // Remove the entire wrapper for newly added entries
                console.log('🗑️ Removing wrapper:', wrapper);
                wrapper.remove();
            } else {
                // Remove just the container for original entries
                console.log('🗑️ Removing container:', container);
                container.remove();
            }
            
            hasUnsavedChanges = true;
            MobileApp.vibrate([100]);
            console.log('✅ Time entry removed successfully');
        });
    } else {
        MobileApp.showError('Cannot remove the last time entry');
        console.log('❌ Cannot remove last time entry');
    }
}
</script>