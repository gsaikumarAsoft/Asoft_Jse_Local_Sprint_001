<!DOCTYPE html>
<html>

<head>
    <title>Broker Notification Email</title>
</head>

<body>


    Hello {{ $user['user_name'] }},</p>

    <p>The following Settlement Account was added to the JSE DMA platform:</p>
    <li>Bank:<b>{{ $user['user_name'] }}</b></li>
    <li>Account Number:<b>{{ $user['account'] }}</b></li>
    <li>Foreign Broker: <b>{{ $user['foreign_broker'] }}</b></li>
    <li>Local Broker: <b>{{ $user['local_broker'] }}</b></li>
    
    <br>
    Your temporary password is: {{ $user['password'] }}
    {{-- @if (empty($user['level']))
        <p>Credentials to verify:</p>
        <li>Username: <b>{{ $user['email'] }}</b></li>
        <li>Password: <b>{{ $user['password'] }}</b></li>
    @else

    @endif --}}
    @if (empty($user['level']))
        <p>Please use the links below to verify or reject the information.</p>
        <a href="{{ config('app.url') }}verify-settlement-account/{{ $user['hash'] }}/accept">Accept </a><br>
        <a href="{{ config('app.url') }}verify-settlement-account/{{ $user['hash'] }}/reject">Reject </a><br>
    @else
        <p>Please use the links below to verify or reject the information.</p>
        <a href="{{ config('app.url') }}verify-settlement-account-foreign/{{ $user['hash'] }}/accept">Accept </a><br>
        <a href="{{ config('app.url') }}verify-settlement-account-foreign/{{ $user['hash'] }}/reject">Reject </a><br>
    @endif


</body>

</html>
