@extends('layouts.app')
@section('content')

@if(empty($orders))
    <operator-order client_accounts="{{$client_accounts}}"></operator-order>
@else
    <operator-order orders="{{$orders}}" client_accounts="{{$client_accounts}}"></operator-order>
@endif
@endsection
