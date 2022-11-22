<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-gray-700 bg-gray-100">
    <main class="w-screen min-h-screen p-8">
        <h1 class="text-2xl font-bold text-center">@yield('title')</h1>
        <div class="mt-4 space-y-2 text-sm break-normal">
            @yield('content')
        </div>
    </main>
</body>

</html>
