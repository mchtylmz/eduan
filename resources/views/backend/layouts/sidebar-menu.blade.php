<ul class="nav-main">
    <li class="nav-main-item">
        <a @class(['nav-main-link', 'active' => request()->routeIs('admin.home.*')])
           href="{{ route('admin.home.index') }}">
            <i class="nav-main-link-icon fa fa-home"></i>
            <span class="nav-main-link-name">{{ __('Anasayfa') }}</span>
        </a>
    </li>

    @canany(['lessons:view', 'topics:view'])
        <li class="nav-main-heading">{{ __('Ders İşlemleri') }}</li>
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

    @canany(['questions:view', 'exams:view', 'exams-reviews:view'])
        <li class="nav-main-heading">{{ __('Soru İşlemleri') }}</li>
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
    @endcanany

    @canany(['blogs:view', 'languages:view', 'pages:view', 'newsletter:view'])
        <li class="nav-main-heading">{{ __('Bilgi Girişleri') }}</li>
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

        @can('languages:view')
            <li class="nav-main-item">
                <a @class(['nav-main-link', 'active' => request()->routeIs('admin.languages.index')])
                   href="{{ route('admin.languages.index') }}">
                    <i class="nav-main-link-icon fa fa-language"></i>
                    <span class="nav-main-link-name">{{ __('Diller & Çeviriler') }}</span>
                </a>
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
    @endcanany

    @canany(['users:view', 'roles:view'])
        <li class="nav-main-heading">{{ __('Kullanıcı İşlemleri') }}</li>
        @can('users:view')
            <li @class(['nav-main-item', 'open' => request()->routeIs('admin.users.*')])>
                <a @class(['nav-main-link nav-main-link-submenu', 'active' => request()->routeIs('admin.users.*')]) data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="javascript:;">
                    <i class="nav-main-link-icon fa fa-users"></i>
                    <span class="nav-main-link-name">{{ __('Kullanıcılar') }}</span>
                </a>

                <ul class="nav-main-submenu">
                    @can('users:add')
                        <li class="nav-main-item">
                            <a @class(['nav-main-link', 'active' => request()->routeIs('admin.users.create')])
                               href="{{ route('admin.users.create') }}">
                                <span class="nav-main-link-name">{{ __('Yeni Kullancıı Ekle') }}</span>
                            </a>
                        </li>
                    @endcan
                    @can('users:view')
                        <li class="nav-main-item">
                            <a @class(['nav-main-link', 'active' => request()->routeIs('admin.users.index')])
                               href="{{ route('admin.users.index') }}">
                                <span class="nav-main-link-name">{{ __('Kullanıcılar') }}</span>
                            </a>
                        </li>
                    @endcan
                </ul>
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
    @endcanany

    @can('settings:view')
        <li class="nav-main-item">
            <a @class(['nav-main-link', 'active' => request()->routeIs('admin.settings.*')])
               href="{{ route('admin.settings.index') }}">
                <i class="nav-main-link-icon si si-settings"></i>
                <span class="nav-main-link-name">{{ __('Ayarlar') }}</span>
            </a>
        </li>
        {{-----
        <li class="nav-main-item">
            <a @class(['nav-main-link', 'active' => request()->routeIs('admin.settings.*')])
               href="{{ route('admin.settings.index') }}">
                <i class="nav-main-link-icon si si-bar-chart"></i>
                <span class="nav-main-link-name">{{ __('Loglar') }}</span>
            </a>
        </li>
        -----}}
    @endcan

</ul>
