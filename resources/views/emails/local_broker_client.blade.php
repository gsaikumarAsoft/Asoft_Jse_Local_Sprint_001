<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>
<h2>Hello Admin,  A request has been made to create a Broker Client Account for the following user</h2>
<br/>
{{-- <p>User Details Here</p> --}}
Registered email-id is {{$user['email']}}
<p>Please provide your verification of these settings using the links below:</p>
<a href="http://100.24.69.39/verify-broker-trader/{{$user['id']}}/accept">Accept </a><br>
<a href="http://100.24.69.39/verify-broker-trader/{{$user['id']}}/reject">Reject </a><br>
</body>

</html>