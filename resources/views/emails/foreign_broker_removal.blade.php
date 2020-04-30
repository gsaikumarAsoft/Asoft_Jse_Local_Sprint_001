<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>
<h2>Hello {{$user['name']}}, a request has been made to delete your account. Do You Confirm these changes?</h2>
<br/>
{{-- <p>User Details Here</p> --}}
Registered email-id is {{$user['email']}}
Registered user-id is {{$user['name']}}
</body>

</html>