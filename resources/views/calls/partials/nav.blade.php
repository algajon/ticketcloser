<div class="flex flex-wrap gap-2">
    <a href="{{ route('app.calls.index', $workspace) }}" class="tc-chip {{ $active === 'log' ? 'tc-chip-active' : '' }}">
        Call log
    </a>
    <a href="{{ route('app.calls.analytics', $workspace) }}" class="tc-chip {{ $active === 'analytics' ? 'tc-chip-active' : '' }}">
        Analytics
    </a>
</div>
