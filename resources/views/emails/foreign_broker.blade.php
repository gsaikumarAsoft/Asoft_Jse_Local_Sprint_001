<!DOCTYPE html>
<html>

<head>
    <title>Broker Notification Email</title>
</head>

<body>

    Hello {{ $user['name'] }},
    <br></br><br>
    You have been added as a foreign broker. Please confirm that your email address for the JSE DMA Platform is:
    <br></br><br>
    Your user ID is: <b>{{ $user['name'] }}</b> and your temporary password is <b>{{ $pass }}</b>
    <br></br><br>
    Please use the links below to verify or reject the information.
    <br></br><br>
    <a href="{{ config('app.url') }}verify/{{ $user['hash'] }}/accept">Accept Request </a><br>
    <a href="{{ config('app.url') }}verify/{{ $user['hash'] }}/reject">Reject Request</a><br>




</body>

</html>
