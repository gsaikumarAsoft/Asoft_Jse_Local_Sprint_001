<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>
<body>
    <h1>Account Created</h1>
    <p>Broker User Account Created, your credentials are as follows</p>
    <br>
<h4>User Account Details</h4>
<a href="http://localhost:8000/">Login Here</a>
<p>{{$user['email']}}</p>
<p>Your temporary password is <b>password</b></p>
<br>
</body>

</html>