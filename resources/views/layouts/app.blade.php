<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, height=device-height,  initial-scale=1.0, user-scalable=no, user-scalable=0"/>
    <meta name="description" content="An Innovate 10x Product : Clive Beckford | Jason Lawrence">
    <meta name="author" content="Jason Lawrence Innovate 10x">
        <meta name="csrf-token" content="{{ csrf_token() }}">  
        @if(Auth::user())         
        <meta name="user-id" content="{{ Auth::user()->id }}">
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
