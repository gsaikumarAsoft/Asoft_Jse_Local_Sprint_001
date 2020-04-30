<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

    <h3>Hello Admin,</h3>

    <p>The following user was added for your Local Broker account </p>
    <li>Username: <b>{{$user['name']}}</b></li>
    <li>Email:<b>{{$user['email']}}</b></li>
    
    <br>
    <h4>Permissions Granted:</h4>
    @foreach($user['permissions'] as $data)
    <li>{{$data}}</li>
    @endforeach
    
    <p>Please confirm or deny this new user using the links below:</p>
    
    <a href="http://100.24.69.39/verify-broker-user/{{$user['hash']}}/accept">Confirm Request </a><br>
    <a href="http://100.24.69.39/verify-broker-user/{{$user['hash']}}/reject">Deny Request </a><br>
    
    




</body>

</html>