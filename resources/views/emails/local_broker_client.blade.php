<!DOCTYPE html>
<html>

<head>
    <title>Broker Notification Email</title>
</head>

<body>
    <p>Hello Admin,</p>
    <p>A broker client account has been created for the following:</p>
    <p>Name {{$user['name']}}</p>
    <p>Email {{$user['email']}}</p>
    <p>JCSD Account Number JCSD{{$user['jcsd']}}</p>
    <p>Please provide your verification of these settings using the links below:</p>
    <a href="{{ config('app.url') }}verify-broker-trader/{{ $user['id'] }}/accept">Accept </a><br>
    <a href="{{ config('app.url') }}verify-broker-trader/{{ $user['id'] }}/reject">Reject </a><br>
</body>

</html>
