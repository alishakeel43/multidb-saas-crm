<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe</title>
</head>
<body style="font-family: sans-serif; margin: 40px auto; max-width: 480px;">
    @if (! empty($done))
        <h1>You are unsubscribed</h1>
        <p>We will not send further marketing email to this contact.</p>
    @else
        <h1>Unsubscribe</h1>
        <p>Confirm that you want to stop marketing email.</p>
        <form method="POST" action="{{ route('marketing_automation.track.unsubscribe.store', $token) }}">
            @csrf
            <button type="submit">Unsubscribe</button>
        </form>
    @endif
</body>
</html>
