@extends('layouts.app')
@section('content')
<operator-order orders="{{$orders}}" client_accounts="{{$client_accounts}}"></operator-order>
@endsection
