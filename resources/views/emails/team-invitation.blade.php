<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team Invitation</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        h2 { color: #1a1a1a; }
        p { color: #444; line-height: 1.6; }
        .btn { display: inline-block; margin-top: 24px; padding: 12px 28px; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .footer { margin-top: 32px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <h2>You've been invited to {{ config('app.name') }}</h2>

        <p>Hi there,</p>

        <p>
            <strong>{{ $inviterName }}</strong> has invited you to join their team
            <strong>{{ $team->name }}</strong> on <strong>{{ config('app.name') }}</strong>.
        </p>

        <p>
            To accept this invitation, create an account using your email address:
            <strong>{{ $inviteeEmail }}</strong>
        </p>

        <a href="{{ url('/register') }}" class="btn">Create Account</a>

        <p class="footer">
            If you didn't expect this invitation, you can safely ignore this email.
        </p>
    </div>
</body>
</html>
