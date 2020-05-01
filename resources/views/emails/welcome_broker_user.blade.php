<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>
<body>


    <p>Hello {{$user['name']}},</p>

    <p>The following Account was created for you on JSE DMA platform:</p>
      
      <p>Credentials:</p>
      <li>Username: <b>{{$user['name']}}</b></li>
      <li>email: <b>{{$user['email']}}</b></li>
      <li>Password: <b>{{$user['p']}}</b></li>
<br>
<a href="{{env('APP_URL')}}logout">Accept </a><br>
</body>

</html>