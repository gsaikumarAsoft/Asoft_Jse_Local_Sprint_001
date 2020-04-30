<!DOCTYPE html>
<html>
<head>
    <title>Broker Notification Email</title>
</head>

<body>
    <p>Congratulations {{$user['broker_name']}},</p>

        <p>
            The following Trading Account settings were verified as follows:
            </p>    
        <li>Settlement Agent:  {{$user['bank_agent']}}</li>
        <li>Settlement Account Number: {{$user['account']}}</li>
        <li>Foreign Broker: {{$user['broker_name']}}</li>
        <li>TargetCompID:  {{$user['target_comp_id']}}</li>
        <li>SenderCompID: {{$user['sender_comp_id']}}</li>
        
        <p>Please provide your verification of these settings using the links below:</p>
        <a href="http://100.24.69.39/verify-trading-account/b2b/{{$user['hash']}}/accept">Accept </a><br>
        <a href="http://100.24.69.39/verify-trading-account/b2b/{{$user['hash']}}/reject">Reject </a><br>
 
</body>

</html>