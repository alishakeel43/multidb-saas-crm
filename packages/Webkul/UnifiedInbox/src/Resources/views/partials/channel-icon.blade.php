@switch($channel)
    @case('whatsapp')
        <svg class="ui-channel-logo" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M20.1 11.9a8.1 8.1 0 0 1-12 7.1L3.5 20l1.1-4.4a8.1 8.1 0 1 1 15.5-3.7Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8.2 7.7c.2-.5.5-.5.8-.5h.5c.2 0 .4.1.5.4l.8 1.8c.1.2.1.4-.1.6l-.6.8c-.2.2-.2.4 0 .6.5.9 1.3 1.6 2.2 2.1.2.1.4.1.6-.1l.8-1c.2-.2.4-.3.6-.2l1.7.8c.3.1.4.3.4.5 0 .4-.2 1.2-.7 1.6-.5.5-1.2.7-2 .5-1.1-.2-2.5-.9-3.8-2.1-1.1-1-2.1-2.4-2.4-3.5-.3-.9-.1-1.7.4-2.3l.3-.2Z" fill="currentColor"/>
        </svg>
        @break
    @case('messenger')
        <svg class="ui-channel-logo" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 2.8c-5.1 0-9.2 3.8-9.2 8.6 0 2.7 1.3 5.1 3.5 6.7v3.1l3.2-1.8c.8.2 1.6.3 2.5.3 5.1 0 9.2-3.8 9.2-8.6S17.1 2.8 12 2.8Z" fill="currentColor"/>
            <path d="m6.8 13.8 4.1-4.4 2.5 2.1 3.8-2.1-4.1 4.4-2.5-2.1-3.8 2.1Z" fill="white"/>
        </svg>
        @break
    @case('instagram')
        <svg class="ui-channel-logo" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="3.2" y="3.2" width="17.6" height="17.6" rx="5" stroke="currentColor" stroke-width="1.8"/>
            <circle cx="12" cy="12" r="4.1" stroke="currentColor" stroke-width="1.8"/>
            <circle cx="17.7" cy="6.6" r="1.1" fill="currentColor"/>
        </svg>
        @break
    @default
        <svg class="ui-channel-logo" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
            <path d="m13.8 7.5-4.5 5.1h3l-2.1 3.9 4.5-5.1h-3l2.1-3.9Z" fill="currentColor" stroke="currentColor" stroke-linejoin="round"/>
        </svg>
@endswitch
