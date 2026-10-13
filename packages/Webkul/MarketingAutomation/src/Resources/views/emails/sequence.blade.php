<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #111;">
    <div>{!! nl2br(e($bodyText)) !!}</div>
    @if ($linkUrl)
        <p><a href="{{ $clickUrl }}">{{ $linkLabel ?: $linkUrl }}</a></p>
    @endif
    <p><a href="{{ $unsubscribeUrl }}">Unsubscribe</a></p>
    <img src="{{ $openUrl }}" width="1" height="1" alt="">
</body>
</html>
