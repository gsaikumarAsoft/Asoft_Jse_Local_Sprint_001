<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>

   {{-- <h2>Hello , {{$user['name']}} You have been added as a Foreign broker to the JSE DMA Broker Tool. Do You Confirm these changes?</h2> --}}
<br/>
Hello {{$user['name']}}, you have been added as a Foreign Broker. Do you confirm the following:
Registered email-id is <b></b>{{$user['email']}}</b><br>
Registered user-id is <b>{{$user['name']}}</b><a>
Registered user-temporary-password is <b>{{$pass}}</b>
</p>

<p>Please use the links below to verify or reject the updated information</p>
<a href="{{env('APP_URL')}}verify/{{$user['hash']}}/accept">Accept Request </a><a>
<a href="{{env('APP_URL')}}verify/{{$user['hash']}}/reject">Reject </a><br>
    <p>Please use the links below to verify or reject the updated information</p>

    
    

</body>

</html>