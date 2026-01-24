<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <meta name="keywords"
        content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="{{ asset('asset/img/icons/icon-48x48.png') }}" />

    <title>NeptuneWare CRM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="{{ asset('asset/css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('styles')
</head>

<body>
    <div class="wrapper">
        <nav id="sidebar" class="sidebar js-sidebar">
            @include('layouts.backend.part.sidebar')
        </nav>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                @include('layouts.backend.part.navbar')
            </nav>

            <main class="content">
                @yield('content')
            </main>

            <footer class="footer">
                @include('layouts.backend.part.footer')
            </footer>
        </div>
    </div>

    {{-- ✅ AdminKit bundle (includes Bootstrap 5 JS) --}}
    <script src="{{ asset('asset/js/app.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- ✅ SortableJS (global, used by Kanban) --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    {{-- ✅ Global Upgrade Modal (standard Bootstrap markup, no custom z-index CSS) --}}
    @include('layouts.partials.upgrade-modal')
    @stack('modals')
    {{-- ✅ Page scripts --}}
    @stack('scripts')

    @if (session('upgrade_feature'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modalEl = document.getElementById('upgradeModal');
                if (!modalEl) return;

                const msg = @json(session('upgrade_message'));
                const url = @json(session('upgrade_url'));

                const txt = document.getElementById('upgradeText');
                if (txt && msg) txt.innerText = msg;

                const btn = document.getElementById('upgradeCta');
                if (btn && url) btn.href = url;

                if (window.bootstrap?.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
        </script>
    @endif
</body>

</html>
