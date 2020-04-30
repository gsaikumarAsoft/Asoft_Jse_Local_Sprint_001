<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>

    <p>
    Hello {{$user['name']}}, your account was updated as follows:<br>
        Registered email-id is <b></b>{{$user['email']}}</b><br>
        Registered user-id is <b>{{$user['name']}}<br>
    </p>
    <p>Please use the links below to verify or reject the updated information</p>
        <a href="http://100.24.69.39/verify/{{$user['hash']}}/accept">Accept Request </a><br>
        <a href="http://100.24.69.39/verify/{{$user['hash']}}/reject">Reject Request </a><br>
</body>

</html>