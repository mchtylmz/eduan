<!-- START Page Container -->
<div id="page-container"
     class="sidebar-o sidebar-dark enable-page-overlay side-scroll page-header-fixed side-trans-enabled {{ user()->getMeta('darkMode', 0) ? 'page-header-dark dark-mode' : ''}} {{ user()->getMeta('sidebarMini') ? 'sidebar-mini':'' }}">

    <div id="page-loader" class="show"></div>

    <nav id="sidebar" aria-label="Main Navigation">
        <!-- Side Header -->
        <div class="content-header">
            @includeIf('backend.layouts.sidebar-header')
        </div>
        <!-- END Side Header -->

        <!-- Sidebar Scrolling -->
        <div class="js-sidebar-scroll">
            <!-- Side Navigation -->
            <div class="content-side">
                @includeIf('backend.layouts.sidebar-menu')
            </div>
            <!-- END Side Navigation -->
        </div>
        <!-- END Sidebar Scrolling -->
    </nav>
    <!-- END Sidebar -->

    <!-- Header -->
    <header id="page-header">
        <!-- Header Content -->
        <div class="content-header">
            <!-- Left Section -->
            <div class="d-flex align-items-center">
                <!-- Toggle Sidebar -->
                <!-- Layout API, functionality initialized in Template._uiApiLayout()-->
                <button type="button" class="btn btn-sm btn-alt-secondary me-2 d-lg-none" data-toggle="layout" data-action="sidebar_toggle">
                    <i class="fa fa-fw fa-bars"></i>
                </button>
                <!-- END Toggle Sidebar -->

                @includeIf('backend.layouts.header-title')
            </div>
            <!-- END Left Section -->

            <!-- Right Section -->
            <div class="d-flex align-items-center">
                <!-- User Dropdown -->
                @includeIf('backend.layouts.header-user')
                <!-- END User Dropdown -->
            </div>
            <!-- END Right Section -->
        </div>
        <!-- END Header Content -->
    </header>
    <!-- END Header -->

    <!-- Main Container -->
    <main id="main-container">
        <!-- Page Content -->
        <div class="content content-full">
            @yield('content')
        </div>
        <!-- END Page Content -->
    </main>
    <!-- END Main Container -->

</div>
<!-- END Page Container -->
