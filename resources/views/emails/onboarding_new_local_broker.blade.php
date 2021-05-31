<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>
<h2>Hello {{$user['name']}},  A new Local Broker Account has been created for you. Please login using the credentials below.</h2>
<br/>
<h4>Local Brokers Account Details</h4>
<p>Login: {{$user['email']}}</p>
<p>Temporary Password: Password</p>
<br>
<a href="{{config('app.url')}}logout">Login</a><br>
</body>

</html>