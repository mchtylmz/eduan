<!-- START Page Container -->
<div id="page-container">
    <!-- Main Container -->
    <main id="main-container">
        <!-- Page Content -->
        <div class="hero-static d-flex align-items-center">
            <div class="w-100">
               @yield('content')

                <!-- Footer -->
                <div class="fs-sm text-center text-muted py-3">
                    <strong>{{ settingLocale('siteTitle') }}</strong> &copy; <span data-toggle="year-copy"></span>
                </div>
                <!-- END Footer -->
            </div>
        </div>
        <!-- END Page Content -->
    </main>
    <!-- END Main Container -->
</div>
<!-- END Page Container -->
