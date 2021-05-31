<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>
<body>

    Hello {{ $user['name'] }},
    <br>
    <p>The following profile has been verified for you on the JSE DMA Platform:</p>
    <h4>CREDENTIALS</h4>
    Username: {{ $user['name'] }}<br>
    Email:{{ $user['email'] }}<br>
    Temporary Password: {{ $user['p'] }}
    <br>
    <h4>USER ACCESS</h4>
    @foreach ($user['permissions'] as $data)
        {{ $data['name'] }}<br>
    @endforeach
    <br>
    <p>Please use the links below to accept or reject the information.</p>
<br>
    <a href="{{config('app.url')}}verify/{{$user['hash']}}/accept">Accept</a><br>
    <a href="{{config('app.url')}}verify/{{$user['hash']}}/reject">Reject</a>
    
</body>

</html>