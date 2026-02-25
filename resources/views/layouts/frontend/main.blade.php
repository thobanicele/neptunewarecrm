<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="A modern, multi-tenant CRM built for teams that need leads, deals, quotes, invoices and payments in one place.">
    <meta name="author" content="NeptuneWare Pty LTD">
    <meta name="keywords"
        content="A modern, multi-tenant CRM built for teams that need leads, deals, quotes, invoices and payments in one place.">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />

    <link rel="canonical" href="https://demo-basic.adminkit.io/pages-blank.html" />
    <title>NeptuneWare CRM</title>

    <link href="{{ asset('asset/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('asset/css/frontend_custom_css.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <div class="wrapper">

        <div class="main">
            {{-- <nav class="navbar navbar-expand navbar-light navbar-bg"> --}}
            @include('layouts.frontend.header')
            {{-- </nav> --}}

            <main class="content">
                @yield('content')
            </main>

            <footer class="footer">
                @include('layouts.frontend.footer')
            </footer>
        </div>
    </div>

    <script src="{{ asset('asset/js/app.js') }}"></script>
    @stack('scripts')
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('frontendNavbar');
            if (!navbar) return;

            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    </script>


</body>

</html>
