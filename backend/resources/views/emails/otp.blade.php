<x-mail::message>
    # Verification Code

    {{ $messageLine }}

    # {{ $otp }}

    This code will expire in 10 minutes.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>