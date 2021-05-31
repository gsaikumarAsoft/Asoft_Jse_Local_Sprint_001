<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>
    <p>Hello Admin,</p>

      <p>The following Client Account was updated on the JSE DMA platform:</p>
        <li>Name:<b>{{$user['name']}}</b></li>
        <li>Email: <b>{{$user['email']}}</b></li>
        <li>JCSD #: <b>{{$user['jcsd']}}</b></li>
        <li>Account Balance: <b>${{ number_format($user['account_balance'])}}</b></li>
        <li>Open Orders: <b>{{$user['open_orders']}}</b></li>
        <li>Unsettled Trades: <b>{{$user['filled_orders']}}</b></li>
        

       <p> Please provide your verification of these settings using the links below:</p>
       <a href="{{config('app.url')}}verify/client/{{$user['jcsd']}}/accept">Accept </a><br>
       <a href="{{config('app.url')}}verify/client/{{$user['jcsd']}}/reject">Reject </a><br>

</body>

</html>