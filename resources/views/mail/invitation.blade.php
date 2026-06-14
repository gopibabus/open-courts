<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="font-family: 'JetBrains Mono', monospace; background:#000; color:#fff; padding:32px;">
    <h1 style="font-size:20px; margin:0 0 16px;">You're invited to {{ config('app.name') }}</h1>
    <p style="color:#aaa; line-height:1.6;">
        You've been invited to join <strong style="color:#fff;">{{ $clubName }}</strong>
        as <strong style="color:#fff;">{{ $role }}</strong>.
    </p>
    <p style="color:#aaa; line-height:1.6;">
        Accept your invitation and set up your account:
    </p>
    <p style="margin:24px 0;">
        <a href="{{ $acceptUrl }}"
           style="display:inline-block; background:#fff; color:#000; padding:12px 20px; text-decoration:none; border-radius:6px;">
            Accept invitation
        </a>
    </p>
    <p style="color:#666; line-height:1.6; font-size:12px;">
        Or paste this link into your browser:<br>
        <a href="{{ $acceptUrl }}" style="color:#aaa;">{{ $acceptUrl }}</a>
    </p>
</body>
</html>
