<div
    x-data="countdownPush({{ $pushAt }})"
    x-init="start()"
    class="fi-topbar-item flex items-center gap-2 px-3 py-1 rounded-lg
           bg-warning-50 text-warning-700 text-xs font-semibold"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 6v6l4 2" />
    </svg>

    <span x-text="label"></span>
</div>
