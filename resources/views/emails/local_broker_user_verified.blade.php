<!DOCTYPE html>
<html>

<head>
    <title>Broker User Notification Email</title>
</head>

Hello {{ $user['name'] }},
<br>
<p>The following profile has been approved for your Broker Operator Account on the JSE DMA Platform:</p>
<br>
<h4>CREDENTIALS</h4>
Username: <b>{{ $user['name'] }}</b>
Email:<b>{{ $user['email'] }}</b>

<br>
<h4>USER ACCESS</h4>
@foreach ($user['permissions'] as $data)
    {{ $data }}
@endforeach
<br>
{{-- <p>Please accept or reject this user account using the links below:</p> --}}
<br></>
<a href="{{ config('app.url') }}verify-broker-user/{{ $user['hash'] }}/accept">Accept</a><br>
<a href="{{ config('app.url') }}verify-broker-user/{{ $user['hash'] }}/reject">Reject</a><br>






</body>

</html>
