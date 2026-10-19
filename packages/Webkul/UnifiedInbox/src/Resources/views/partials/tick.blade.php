@php
    $tickStatus = in_array($status, ['pending', 'sent', 'delivered', 'read', 'failed'], true) ? $status : 'pending';
    $tickTitle = ['pending' => 'Sending', 'sent' => 'Sent', 'delivered' => 'Delivered', 'read' => 'Read', 'failed' => 'Failed'][$tickStatus];
@endphp
<span class="ui-msg-status ui-msg-status-{{ $status }} ui-tick ui-tick-{{ $tickStatus }}" title="{{ $status === 'unknown' ? 'Delivery not confirmed' : $tickTitle }}" aria-label="{{ $tickTitle }}">
    @if ($tickStatus === 'failed')
        !
    @elseif ($tickStatus === 'pending')
        <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><path d="M8 5v3.5l2 1.5"/></svg>
    @elseif ($tickStatus === 'sent')
        <svg viewBox="0 0 16 11" width="15" height="11" fill="currentColor"><path d="M11.07.65 4.6 7.12 1.93 4.45.87 5.51l3.73 3.73L12.13 1.7z"/></svg>
    @else
        <svg viewBox="0 0 20 11" width="18" height="11" fill="currentColor"><path d="M11.07.65 4.6 7.12 1.93 4.45.87 5.51l3.73 3.73L12.13 1.7z"/><path d="M15.07.65 8.6 7.12l-.9-.9-1.06 1.06 1.96 1.96L16.13 1.7z"/></svg>
    @endif
</span>
