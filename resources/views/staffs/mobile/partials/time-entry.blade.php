@php
    // Check if current user should be restricted from editing this staff member's hours
    $currentUserEmail = Auth::user()->email ?? '';
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
                $hiddenValue = $displayStartTime . '-' . $displayEndTime;
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
        <div class="d-flex justify-content-between align-items-center">
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
                <button type="button" class="btn btn-outline-danger btn-sm" 
                        onclick="removeTimeEntry(this)">
                    <i class="fas fa-trash"></i>
                </button>
            @endif
        </div>
    @else
        <!-- Regular Time Entry -->
        <div class="row align-items-center">
            <div class="col">
                <!-- Start Time -->
                <label class="form-label small text-muted">Start Time</label>
                <input type="time" 
                       class="form-control form-control-mobile time-start"
                       value="{{ $displayStartTime }}"
                       {{ $isOwnRoster ? 'disabled' : '' }}
                       onchange="updateTimeRange(this)">
            </div>
            <div class="col-auto px-2 mt-4">
                <i class="fas fa-arrow-right text-muted"></i>
            </div>
            <div class="col">
                <!-- End Time -->
                <label class="form-label small text-muted">End Time</label>
                <input type="time" 
                       class="form-control form-control-mobile time-end"
                       value="{{ $displayEndTime }}"
                       {{ $isOwnRoster ? 'disabled' : '' }}
                       onchange="updateTimeRange(this)">
            </div>
            @if(!$isOwnRoster)
                <div class="col-auto">
                    <label class="form-label small text-muted">&nbsp;</label>
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
    
    @if(!$isOwnRoster && !$isSpecialType)
        <!-- Quick Action Buttons -->
        <div class="mt-3">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="applyQuickFill(this, 'V')" title="Vacation">V</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="applyQuickFill(this, 'X')" title="Day Off">X</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="applyQuickFill(this, 'H')" title="Holiday">H</button>
                <button type="button" class="btn btn-outline-success btn-sm" 
                        onclick="applyQuickFill(this, 'on_call')" title="On Call">📞</button>
                <button type="button" class="btn btn-outline-info btn-sm" 
                        onclick="applyQuickFill(this, 'reception')" title="Reception">💻</button>
                <button type="button" class="btn btn-outline-primary btn-sm" 
                        onclick="applyQuickFill(this, 'regular')" title="Regular Hours">⏰</button>
            </div>
        </div>
    @endif
    
    @if($isOwnRoster)
        <!-- Restricted Access Message -->
        <div class="mt-2 text-center">
            <small class="text-muted">
                <i class="fas fa-lock me-1"></i>Cannot edit own roster
            </small>
        </div>
    @endif
</div>

<script>
// Update time range when time inputs change
function updateTimeRange(input) {
    const container = input.closest('.time-picker-group');
    const startInput = container.querySelector('.time-start');
    const endInput = container.querySelector('.time-end');
    const hiddenInput = container.querySelector('.time-range-input');
    
    if (startInput && endInput && hiddenInput) {
        if (startInput.value && endInput.value) {
            // Validate time range
            if (startInput.value >= endInput.value) {
                MobileApp.showError('Start time must be before end time');
                return;
            }
            
            hiddenInput.value = `${startInput.value}-${endInput.value}`;
            hasUnsavedChanges = true;
            MobileApp.vibrate([30]);
        } else {
            hiddenInput.value = '';
        }
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
    
    const hiddenInput = container.querySelector('.time-range-input');
    const startInput = container.querySelector('.time-start');
    const endInput = container.querySelector('.time-end');
    
    console.log('📋 Found elements:', {
        hiddenInput: !!hiddenInput,
        startInput: !!startInput,
        endInput: !!endInput
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
            if (hiddenInput) hiddenInput.value = '09:00-17:00';
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

// Remove time entry
function removeTimeEntry(button) {
    console.log('🗑️ removeTimeEntry called');
    
    const container = button.closest('.time-picker-group');
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