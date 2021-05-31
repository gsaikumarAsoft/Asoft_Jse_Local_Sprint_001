@extends('layouts.app')
@section('content')
<broker-order orders="{{$orders}}" client_accounts="{{$client_accounts ?? ''}}"></broker-order>
@endsection
