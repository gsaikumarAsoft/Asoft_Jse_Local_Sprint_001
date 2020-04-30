<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>
    <p>Hello {{$user['user_name']}},</p>

      <p>The following Settlement Account was added to the JSE DMA platform:</p>
        <li>Account Number:<b>{{$user['account']}}</b></li>
        <li>Foreign Broker: <b>{{$user['foreign_broker']}}</b></li>
        <li>Local Broker: <b>{{$user['local_broker']}}</b></li>
        
        <p>Credentials to verify:</p>
<li>Username: <b>{{$user['email']}}</b></li>
<li>Password: <b>{{$user['password']}}</b></li>
<br>
       <p> Please provide your verification of these settings using the links below:</p>
       <a href="http://100.24.69.39/verify-settlement-account/{{$user['hash']}}/accept">Accept </a><br>
       <a href="http://100.24.69.39/verify-settlement-account/{{$user['hash']}}/reject">Reject </a><br>

</body>

</html>