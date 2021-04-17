<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, height=device-height,  initial-scale=1.0, user-scalable=no, user-scalable=0"/>
    <meta name="description" content="An Innovate 10x Product : We are an engine of creativity for the rapid digital disruption that drives transformation, growth and sustainable development for our clients, partners and colleagues in the Caribbean.">
    <meta name="author" content="Jason Lawrence Innovate 10x">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
        <meta name="csrf-token" content="{{ csrf_token() }}">  
        @if(Auth::user())         
        <meta name="user-id" content="{{ Auth::user()->id }}">
        <meta name="fix-api" content="{{ config('fixwrapper.base_url') }}">
     <meta name="user-permissions" content="{{ Auth::user()->getDirectPermissions() }}">       
@endif
        
        <title>JSE Broker DMA Tool</title>
        @yield('link-styles')
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
        <link href="{{ asset('css/app.css')}}" rel="stylesheet">
        
    </head>
    <body>
        <div id="app">
        @yield('content')
        </div>
        <script src="{{ asset('js/app.js')}}"></script>
    </body>
</html>
