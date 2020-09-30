<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>
<body>

    Hello {{ $user['name'] }},
    <br>
    <p>The following profile has been created for you on the JSE DMA Platform:</p>
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
    <p>Please use the links below to verify or reject the information.</p>
<br>
    <a href="{{env('APP_URL')}}verify/{{$user['hash']}}/accept">Accept</a><br>
    <a href="{{env('APP_URL')}}verify/{{$user['hash']}}/reject">Reject</a>
    
</body>

</html>