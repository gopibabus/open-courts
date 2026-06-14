<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="font-family: 'JetBrains Mono', monospace; background:#000; color:#fff; padding:32px;">
    <h1 style="font-size:20px; margin:0 0 16px;">Welcome to {{ config('app.name') }}</h1>
    <p style="color:#aaa; line-height:1.6;">
        Your club <strong style="color:#fff;">{{ $clubName }}</strong> is ready.
    </p>
    <p style="color:#aaa; line-height:1.6;">
        Sign in and manage courts, members, and tournaments at
        <a href="http://{{ $clubUrl }}" style="color:#fff;">{{ $clubUrl }}</a>.
    </p>
</body>
</html>
