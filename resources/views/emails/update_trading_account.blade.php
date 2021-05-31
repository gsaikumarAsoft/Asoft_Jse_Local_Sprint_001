<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>
    <p>Hello {{$user['broker_name']}},</p>

        <p>The following Trading Account was updated and requires your verification:</p>
        <h4>Trading Account</h4>
        <li>Local Broker: {{$user['local_broker_name']}}</li>
        <li>Trading Account: {{$user['trading_account_number']}}</li>
        {{-- <li>Settlement Bank:  {{$user['settlement_agent']}}</li> --}}
        {{-- <li>Settlement Account Number: {{$user['settlement_account_number']}}</li> --}}
        <li>Foreign Broker: {{$user['broker_name']}}</li>
        <li>TargetCompID:  {{$user['target_comp_id']}}</li>
        <li>SenderCompID: {{$user['sender_comp_id']}}</li>
        
        <p>Please provide your verification of these settings using the links below:</p>
        <a href="{{config('app.url')}}verify-trading-account/{{$user['hash']}}/accept">Accept </a><br>
        <a href="{{config('app.url')}}verify-trading-account/{{$user['hash']}}/reject">Reject </a><br>
 
</body>

</html>