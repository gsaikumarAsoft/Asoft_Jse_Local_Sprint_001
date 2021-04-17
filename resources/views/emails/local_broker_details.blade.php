<!DOCTYPE html>
<html>

<head>
    <title>Broker Notification Email</title>
</head>

<body>
    Hello {{ $user['name'] }},
    <br></br>
    Your account has been updated as follows:<br></><br>

    User ID: {{ $user['name'] }}
    <br>
    Your email remains: {{ $user['email'] }}
    <br></br>
    Please use the links below to verify or reject the updated information.
    <br></br>

    <a href="{{ config('app.url') }}verify/{{ $user['hash'] }}/accept">Accept</a><br>
    <a href="{{ config('app.url') }}verify/{{ $user['hash'] }}/reject">Reject</a><br>
</body>

</html>
