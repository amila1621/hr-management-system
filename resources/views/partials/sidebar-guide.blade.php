<li>
    <a href="{{ route('guide.work-report') }}" class="waves-effect">
        <i class="fas fa-clipboard-list"></i><span> Work Report </span>
    </a>
</li>

@if (auth()->user()->tourGuide && auth()->user()->tourGuide->allow_report_hours)
    <li>
        <a href="{{ route('guide.report-hours') }}" class="waves-effect">
            <i class="fas fa-clock"></i><span> Record Work Hours </span>
        </a>
    </li>
@endif
