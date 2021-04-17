<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>

    <p>
    Hello {{$user['name']}}, your account has been created and your credentials are as follows:
        Registered email-id is <b></b>{{$user['email']}}</b><br>
        Registered user-id is <b>{{$user['name']}}</b><br>
        Registered user-temporary-password is <b>password</b>
    </p>
    <p>Please use the link below to login</p>
        <a href="{{config('app.url')}}logout">Login</a><br>
</body>

</html>