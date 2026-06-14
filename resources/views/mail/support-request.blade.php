<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="font-family: 'JetBrains Mono', monospace; background:#000; color:#fff; padding:32px;">
    <h1 style="font-size:20px; margin:0 0 16px;">New support request</h1>
    <p style="color:#aaa; line-height:1.6; margin:0 0 4px;">
        <strong style="color:#fff;">{{ $memberName }}</strong>
        @if ($memberEmail)
            (<a href="mailto:{{ $memberEmail }}" style="color:#fff;">{{ $memberEmail }}</a>)
        @endif
        from <strong style="color:#fff;">{{ $clubName }}</strong> needs a hand.
    </p>
    <p style="color:#666; line-height:1.6; margin:0 0 16px; text-transform:uppercase; letter-spacing:0.1em; font-size:12px;">
        {{ $category }}
    </p>
    <p style="color:#fff; line-height:1.6; margin:0 0 8px;"><strong>{{ $subject }}</strong></p>
    <p style="color:#aaa; line-height:1.6; white-space:pre-wrap;">{{ $body }}</p>
</body>
</html>
