<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="font-family: 'JetBrains Mono', monospace; background:#000; color:#fff; padding:32px;">
    <h1 style="font-size:20px; margin:0 0 16px;">Booking confirmed</h1>
    <p style="color:#aaa; line-height:1.6;">
        Your reservation for <strong style="color:#fff;">{{ $courtName }}</strong> is set.
    </p>
    <p style="color:#aaa; line-height:1.6;">
        <strong style="color:#fff;">{{ $startsAt }}</strong> – {{ $endsAt }}
    </p>
    <p style="color:#777; line-height:1.6; font-size:13px;">
        Need to cancel? Open your bookings in the club app.
    </p>
</body>
</html>
