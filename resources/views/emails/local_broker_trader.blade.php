<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>
<h2>Hello Admin,  A request has been made to create a Broker Trader Account for the following user</h2>
<br/>
<h4>Account Details</h4>
<p>{{$user['name']}}</p>
<p>{{$user['email']}}</p>
<br>
<a href="{{config('app.url')}}verify-broker-trader/{{$user['id']}}/accept">Accept </a><br>
<a href="{{config('app.url')}}verify-broker-trader/{{$user['id']}}/reject">Reject </a><br>
</body>

</html>