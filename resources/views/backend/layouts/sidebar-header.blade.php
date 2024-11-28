<!-- Logo -->
@if($logoWhite = settings()->siteLogoWhite)
    <a class="fw-semibold text-dual" href="{{ route('admin.home.index') }}">
        <img src="{{ asset($logoWhite) }}" alt="Logo" style="max-height: 40px"/>
    </a>
@else
    <a class="fw-semibold text-dual" href="{{ route('admin.home.index') }}">
        <span class="text-white">{{ settings()->appName }}</span>
    </a>
@endif
<!-- END Logo -->

<!-- Extra -->
<div>
    <button type="button" class="btn btn-sm btn-alt-secondary" data-toggle="layout" data-action="sidebar_mini_toggle">
        <i class="fa fa-compress-alt"></i>
    </button>
    <button type="button" class="btn btn-sm btn-alt-secondary" data-toggle="layout" data-action="dark_mode_toggle">
        <i class="far fa-moon"></i>
    </button>

    <!-- Close Sidebar, Visible only on mobile screens -->
    <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
    <a class="d-lg-none btn btn-sm btn-alt-secondary ms-1" data-toggle="layout" data-action="sidebar_close" href="javascript:void(0)">
        <i class="fa fa-fw fa-times"></i>
    </a>
    <!-- END Close Sidebar -->
</div>
<!-- END Extra -->
