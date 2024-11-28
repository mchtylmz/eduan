<header>
    <div class="h2_header-area header-sticky border-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-xl-3 col-sm-7 col-6">
                    <div class="h2_header-left">
                        <div class="h2_header-logo">
                            @includeIf('frontend.layouts.section.header-logo')
                        </div>
                        @includeIf('frontend.layouts.section.list-lessons')
                    </div>
                </div>
                <div class="col-xl-6 d-none d-xl-block">
                    <div class="h2_header-middle">
                        <nav class="h2_main-menu mobile-menu" id="mobile-menu">
                            @includeIf('frontend.layouts.section.main-menu')
                        </nav>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-5 col-6">
                    <div class="h2_header-right">
                        @includeIf('frontend.layouts.section.header-right')

                        <div class="header-menu-bar d-xl-none ml-10 me-2 me-sm-0">
                            <span class="header-menu-bar-icon side-toggle">
                                <i class="fa-light fa-bars"></i>
                            </span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
