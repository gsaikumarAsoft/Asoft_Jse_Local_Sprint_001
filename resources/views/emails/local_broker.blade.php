<!DOCTYPE html>
<html>

<head>
    <title>Broker Notification Email</title>
</head>

<body>
    Hello {{ $user['name'] }},
    <br></br>
    You have been added as a Local Broker to the JSE DMA Platform:<br></><br>

    Your user ID: {{ $user['name'] }}
    <br>
    Your user email is: {{ $user['email'] }}
    <br>
    Your temporary password is: {{ $pass }}
    <br></br>
    Please use the links below to verify or reject the updated information.
    <br></br>

    <a href="{{ config('app.url') }}verify/{{ $user['hash'] }}/accept">Accept</a><br>
    <a href="{{ config('app.url') }}verify/{{ $user['hash'] }}/reject">Reject</a><br>
</body>

</html>
