@foreach (($message->attachments ?? []) as $attIndex => $att)
    @continue(! is_array($att))
    @php
        $attType = $att['type'] ?? 'document';
        $attUrl = route('admin.unified_inbox.conversations.attachments.show', [$message->conversation_id, $message->id, $attIndex]);
        $attName = $att['filename'] ?? ucfirst($attType);
    @endphp
    @if (in_array($attType, ['image', 'sticker'], true))
        <a href="{{ $attUrl }}" target="_blank" rel="noopener"><img class="ui-msg-media" src="{{ $attUrl }}" alt="{{ $attName }}" loading="lazy"></a>
    @elseif ($attType === 'audio')
        <audio class="ui-msg-audio" controls preload="none" src="{{ $attUrl }}"></audio>
    @elseif ($attType === 'video')
        <video class="ui-msg-media" controls preload="none" src="{{ $attUrl }}"></video>
    @else
        <a class="ui-msg-file" href="{{ $attUrl }}?download=1" rel="noopener">
            <span aria-hidden="true">📄</span><span class="truncate">{{ $attName }}</span><span aria-hidden="true">⬇</span>
        </a>
    @endif
@endforeach
