<ul class="nav-main">
    <li class="nav-main-item">
        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.home.*')])
           href="{{ route('admin.home.index') }}">
            <i class="nav-main-link-icon fa fa-home"></i>
            <span class="nav-main-link-name">{{ __('Anasayfa') }}</span>
        </a>
    </li>

    @canany(['lessons:view', 'topics:view'])
        @can('lessons:view')
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.lessons.index')])
                   href="{{ route('admin.lessons.index') }}">
                    <i class="nav-main-link-icon fa fa-book"></i>
                    <span class="nav-main-link-name">{{ __('Dersler') }}</span>
                </a>
            </li>
        @endcan
        @can('topics:view')
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.topics.index')])
                   href="{{ route('admin.topics.index') }}">
                    <i class="nav-main-link-icon fa fa-book-reader"></i>
                    <span class="nav-main-link-name">{{ __('Konular') }}</span>
                </a>
            </li>
        @endcan
    @endcanany

    @canany(['questions:view', 'exams:view', 'tests:view', 'stats:view'])
        @can('ai:view')
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.ai.index')])
                   href="{{ route('admin.ai.index') }}">
                    <i class="nav-main-link-icon fa fa-magic-wand-sparkles"></i>
                    <span class="nav-main-link-name">{{ __('Yapay Zeka') }}</span>
                </a>
            </li>
        @endcan
        @can('questions:view')
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.questions.index')])
                   href="{{ route('admin.questions.index') }}">
                    <i class="nav-main-link-icon fa fa-question"></i>
                    <span class="nav-main-link-name">{{ __('Soru Havuzu') }}</span>
                </a>
            </li>
        @endcan
        @can('exams:view')
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.exams.index')])
                   href="{{ route('admin.exams.index') }}">
                    <i class="nav-main-link-icon fa fa-pen"></i>
                    <span class="nav-main-link-name">{{ __('Testler') }}</span>
                </a>
            </li>
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.exams.results')])
                   href="{{ route('admin.exams.results') }}">
                    <i class="nav-main-link-icon fa fa-poll"></i>
                    <span class="nav-main-link-name">{{ __('Test Sonuçları') }}</span>
                </a>
            </li>
        @endcan
        @can('tests:view')
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.tests.index')])
                   href="{{ route('admin.tests.index') }}">
                    <i class="nav-main-link-icon fa fa-book-open-reader"></i>
                    <span class="nav-main-link-name">{{ __('Sınavlar') }}</span>
                </a>
            </li>
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.tests.results')])
                   href="{{ route('admin.tests.results') }}">
                    <i class="nav-main-link-icon fa fa-book-bookmark"></i>
                    <span class="nav-main-link-name">{{ __('Sınav Sonuçları') }}</span>
                </a>
            </li>
        @endcan
        @can('stats:view')
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.stats.index')])
                   href="{{ route('admin.stats.index') }}">
                    <i class="nav-main-link-icon fa fa-chart-area"></i>
                    <span class="nav-main-link-name">{{ __('İstatistikler') }}</span>
                </a>
            </li>
        @endcan
    @endcanany

    @can('leagues:view')
        <li class="nav-main-item">
            <a @class(['nav-main-link', 'active' => request()->routeIs('admin.leagues.index')])
               href="{{ route('admin.leagues.index') }}">
                <i class="nav-main-link-icon fa fa-award"></i>
                <span class="nav-main-link-name">{{ __('Ligler / Lig Sonuçları') }}</span>
            </a>
        </li>
    @endcan

    @canany(['exams-reviews:view', 'blogs:view', 'languages:view', 'pages:view', 'contacts:view', 'newsletter:view'])
        <li @class(['nav-main-item', 'open' => request()->routeIs('admin.exams.reviews') || request()->routeIs('admin.blogs.*') || request()->routeIs('admin.pages.*') || request()->routeIs('admin.contacts.*') || request()->routeIs('admin.newsletter.*') || request()->routeIs('admin.languages.*')])>
            <a @class(['nav-main-link nav-main-link-submenu', 'active' => request()->routeIs('admin.exams.reviews') || request()->routeIs('admin.blogs.*') || request()->routeIs('admin.pages.*') || request()->routeIs('admin.contacts.*') || request()->routeIs('admin.newsletter.*') || request()->routeIs('admin.languages.*')]) data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="javascript:;">
                <i class="nav-main-link-icon fa fa-pen"></i>
                <span class="nav-main-link-name">{{ __('Bilgi Girişleri') }}</span>
            </a>

            <ul class="nav-main-submenu">
                @can('exams-reviews:view')
                    <li class="nav-main-item">
                        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.exams.reviews')])
                           href="{{ route('admin.exams.reviews') }}">
                            <i class="nav-main-link-icon fa fa-comments"></i>
                            <span class="nav-main-link-name">{{ __('Değerlendirmeler') }}</span>
                            @if($count = data()->countExamReviewsNotRead())
                                <span class="badge bg-success">{{ $count }}</span>
                            @endif
                        </a>
                    </li>
                @endcan
                @can('blogs:view')
                    <li class="nav-main-item">
                        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.blogs.index')])
                           href="{{ route('admin.blogs.index') }}">
                            <i class="nav-main-link-icon fa fa-newspaper"></i>
                            <span class="nav-main-link-name">{{ __('Bloglar') }}</span>
                        </a>
                    </li>
                @endcan
                @can('pages:view')
                    <li @class(['nav-main-item', 'open' => request()->routeIs('admin.pages.*')])>
                        <a @class(['nav-main-link nav-main-link-submenu', 'active' => request()->routeIs('admin.pages.*')]) data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="javascript:;">
                            <i class="nav-main-link-icon fa fa-pager"></i>
                            <span class="nav-main-link-name">{{ __('Sayfalar') }}</span>
                        </a>

                        <ul class="nav-main-submenu">
                            <li class="nav-main-item">
                                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.pages.home')])
                                   href="{{ route('admin.pages.home') }}">
                                    <span class="nav-main-link-name">{{ __('Anasayfa') }}</span>
                                </a>
                            </li>
                            <li class="nav-main-item">
                                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.pages.faqs.index')])
                                   href="{{ route('admin.pages.faqs.index') }}">
                                    <span class="nav-main-link-name">{{ __('Sıkça Sorulan Sorular') }}</span>
                                </a>
                            </li>
                            <li class="nav-main-item">
                                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.pages.all')])
                                   href="{{ route('admin.pages.all') }}">
                                    <span class="nav-main-link-name">{{ __('Diğer Sayfalar') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                @can('contacts:view')
                    <li class="nav-main-item">
                        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.contacts.index')])
                           href="{{ route('admin.contacts.index') }}">
                            <i class="nav-main-link-icon fa fa-message"></i>
                            <span class="nav-main-link-name">{{ __('İletişim Mesajları') }}</span>
                            @if($count = data()->countContactMessageNotRead())
                                <span class="badge bg-success">{{ $count }}</span>
                            @endif
                        </a>
                    </li>
                @endcan
                @can('newsletter:view')
                    <li class="nav-main-item">
                        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.newsletter.index')])
                           href="{{ route('admin.newsletter.index') }}">
                            <i class="nav-main-link-icon fa fa-bullhorn"></i>
                            <span class="nav-main-link-name">{{ __('Bilgilendirme Aboneleri') }}</span>
                        </a>
                    </li>
                @endcan
                @can('languages:view')
                    <li class="nav-main-item">
                        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.languages.index')])
                           href="{{ route('admin.languages.index') }}">
                            <i class="nav-main-link-icon fa fa-language"></i>
                            <span class="nav-main-link-name">{{ __('Diller & Çeviriler') }}</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcanany

    @can('users:view')
        <li class="nav-main-item">
            <a @class(['nav-main-link', 'active' => request()->routeIs('admin.users.index')])
               href="{{ route('admin.users.index') }}">
                <i class="nav-main-link-icon fa fa-users"></i>
                <span class="nav-main-link-name">{{ __('Kullanıcılar') }}</span>
            </a>
        </li>
    @endcan

    @canany(['roles:view', 'settings:view', 'roles:view', 'roles:view', 'seasons:view'])
        <li @class(['nav-main-item', 'open' => request()->routeIs('admin.roles.*') || request()->routeIs('admin.settings.*')])>
            <a @class(['nav-main-link nav-main-link-submenu', 'active' => request()->routeIs('admin.roles.*') || request()->routeIs('admin.settings.*')]) data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="javascript:;">
                <i class="nav-main-link-icon fa fa-cogs"></i>
                <span class="nav-main-link-name">{{ __('Ayarlar') }} & {{ __('Yetkiler') }}</span>
            </a>

            <ul class="nav-main-submenu">
                @can('seasons:view')
                    <li class="nav-main-item">
                        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.seasons.*')])
                           href="{{ route('admin.seasons.index') }}">
                            <i class="nav-main-link-icon fa fa-calendar"></i>
                            <span class="nav-main-link-name">{{ __('Dönemler') }}</span>
                        </a>
                    </li>
                @endcan
                @can('roles:view')
                    <li class="nav-main-item">
                        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.roles.*')])
                           href="{{ route('admin.roles.index') }}">
                            <i class="nav-main-link-icon si si-lock"></i>
                            <span class="nav-main-link-name">{{ __('Yetkiler') }}</span>
                        </a>
                    </li>
                @endcan
                @can('settings:view')
                    <li class="nav-main-item">
                        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.settings.index')])
                           href="{{ route('admin.settings.index') }}">
                            <i class="nav-main-link-icon si si-settings"></i>
                            <span class="nav-main-link-name">{{ __('Ayarlar') }}</span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.settings.logs')])
                           href="{{ route('admin.settings.logs') }}">
                            <i class="nav-main-link-icon si si-chart"></i>
                            <span class="nav-main-link-name">{{ __('Loglar') }}</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcanany

</ul>
