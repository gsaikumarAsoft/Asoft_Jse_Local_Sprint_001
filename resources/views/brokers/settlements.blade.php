@extends('layouts.app')
@section('content')
<broker-settlements :settlement_accounts="{{$accounts}}"></broker-settlements>
@endsection
