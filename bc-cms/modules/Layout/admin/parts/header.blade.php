<?php
$user = Auth::user();
[$notifications,$countUnread] = getNotify();

$languages = \Modules\Language\Models\Language::getActive();
$locale = App::getLocale();
$theme = \Modules\Theme\ThemeManager::currentProvider();
?>

<div class="header-logo flex-shrink-0" style="display:flex;align-items:center;height:100%;">
    <a href="{{ route('admin.index') }}" style="display:flex;align-items:center;text-decoration:none;padding:0 16px;">
        <img src="{{ url('/images/logo.png') }}" alt="Tsoka" style="height:32px;width:auto;display:block;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
        <span style="display:none;font-weight:700;font-size:16px;color:var(--color-dark,#1a0900);">Tsoka</span>
    </a>
</div>
<div class="header-widgets d-flex flex-grow-1">
    <div class="widgets-left d-flex flex-grow-1 align-items-center">
        <div class="header-widget">
            <span class="btn-toggle-admin-menu btn btn-sm btn-link"><i class="icon ion-ios-menu"></i></span>
        </div>
        <div class="header-widget search-widget">
            <a href="{{url('/')}}" class="btn btn-link" target="_blank"><i class="fa fa-eye"></i> {{__('Home')}}
            </a>
        </div>
    </div>
    <div class="widgets-right flex-shrink-0 d-flex">
        @if(!empty($languages) and is_enable_multi_lang())
        <div class="dropdown header-widget widget-user widget-language flex-shrink-0">
            <div data-toggle="dropdown" class="user-dropdown d-flex align-items-center" aria-haspopup="true" aria-expanded="false">
                @foreach($languages as $language)
                    @if($locale == $language->locale)
                        <div class="user-info flex-grow-1 d-flex">
                            @if($language->flag)
                                <span class="flag-icon mr-2 flag-icon-{{$language->flag}}"></span>
                            @endif
                            {{$language->name}}
                        </div>
                    @endif
                @endforeach
                <i class="fa fa-angle-down"></i>
            </div>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                @foreach($languages as $language)
                    @php if($language->locale == $locale) continue; @endphp

                    <a class="dropdown-item" href="{{route('language.set-admin-lang',['locale'=>$language->locale])}}">
                        @if($language->flag)
                            <span class="flag-icon flag-icon-{{$language->flag}}"></span>
                        @endif
                        {{$language->name}}
                    </a>
                @endforeach
            </div>
        </div>
        @endif
        <div class="dropdown header-widget widget-user pt-2 dropdown-notifications flex-shrink-0" style="min-width: 0">
            <div data-toggle="dropdown" class="user-dropdown d-flex align-items-center" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-lg fa-bell m-1 p-1"></i>
                <span class="badge badge-danger notification-icon">{{$countUnread}}</span>
            </div>
            <div class="dropdown-menu overflow-auto notify-items dropdown-container dropdown-menu-right dropdown-large" aria-labelledby="dropdownMenuButton">
                <div class="dropdown-toolbar">
                    <div class="dropdown-toolbar-actions">
                        <a href="#" class="markAllAsRead">{{__('Mark all as read')}}</a>
                    </div>
                    <h3 class="dropdown-toolbar-title">{{__('Notifications')}} (<span class="notif-count">{{$countUnread}}</span>)</h3>
                </div>
                <ul class="dropdown-list-items p-0 m-0">
                    @if(count($notifications)> 0)
                        @foreach($notifications as $oneNotification)
                            @php
                                $active = $class = '';
                                $data = json_decode($oneNotification['data']);

                                $idNotification = @$data->id;
                                $forAdmin = @$data->for_admin;
                                $usingData = @$data->notification;

                                $services = @$usingData->type;
                                $idServices = @$usingData->id;
                                $title = @$usingData->message;
                                $name = @$usingData->name;
                                $avatar = @$usingData->avatar;
                                $link = @$usingData->link;

                                if(empty($oneNotification->read_at)){
                                    $class = 'markAsRead';
                                    $active = 'active';
                                }

                            @endphp
                            <li class="notification {{$active}}">
                                <a class="{{$class}}" data-id="{{$idNotification}}" href="{{$link}}">
                                    <div class="media">
                                        <div class="media-left">
                                              <div class="media-object">
                                                  @if($avatar)
                                                    <img class="image-responsive" src="{{$avatar}}" alt="{{$name}}">
                                                  @else
                                                      <span class="avatar-text">{{ucfirst($name[0])}}</span>
                                                  @endif
                                              </div>
                                            </div>
                                        <div class="media-body">
                                            {!! $title !!}
                                              <div class="notification-meta">
                                                    <small class="timestamp">{{format_interval($oneNotification->created_at)}}</small>
                                                  </div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
                <div class="dropdown-footer text-center">
                    <a href="{{route('core.admin.notification.loadNotify')}}">{{__('View More')}}</a>
                </div>
            </div>
        </div>
        @if($user->hasPermission('dashboard_vendor_access'))
        <div class="header-widget flex-shrink-0 d-flex align-items-center" style="padding:0 10px;">
            <a href="{{ route('vendor.dashboard') }}" title="{{ __('Switch to your vendor area') }}" style="display:inline-block;border:1.5px solid #0a0a0a;border-radius:999px;padding:5px 14px;font-size:12px;font-weight:700;color:#0a0a0a;text-decoration:none;white-space:nowrap;">{{ __('Vendor view') }} &rarr;</a>
        </div>
        @endif
        <div class="dropdown header-widget widget-user flex-shrink-0">
            <div data-toggle="dropdown" class="user-dropdown d-flex align-items-center" aria-haspopup="true" aria-expanded="false">
                <span class="user-avatar flex-shrink-0">
                     @if($avatar_url = $user->getAvatarUrl())
                        <div class="avatar avatar-cover" style="background-image: url('{{$user->getAvatarUrl()}}')"></div>
                    @else
                        <span class="avatar-text">{{ucfirst($user->getDisplayName()[0])}}</span>
                    @endif
                </span>
                <div class="user-info flex-grow-1">
                    <div class="user-name">{{$user->getDisplayName()}}</div>
                    <div class="user-role">{{ucfirst($user->role->name ?? '')}}</div>
                </div>
                <i class="fa fa-angle-down"></i>
            </div>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                @if($user->hasPermission('dashboard_vendor_access'))
                    <a class="dropdown-item" href="{{route('vendor.dashboard')}}"><i class="fa fa-line-chart mr-10"></i> {{__('Vendor Dashboard')}}</a>
                    <div class="dropdown-divider"></div>
                @endif
                <a class="dropdown-item" href="{{route('user.profile.index')}}"><i class="fa fa-address-card mr-10"></i> {{__('My profile')}}</a>
                <a class="dropdown-item" href="{{route('user.booking_history')}}"><i class="fa fa-clock-o mr-10"></i> {{__('Booking History')}}</a>
                <a class="dropdown-item" href="{{route('user.admin.password',['id'=>$user->id])}}"><i class="fa fa-lock mr-10"></i> {{__('Change Password')}}</a>
                @if($user->hasPermission('dashboard_access'))
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{route('admin.index')}}"><i class="fa fa-dashboard mr-10"></i> {{__('Admin Dashboard')}}</a>
                @endif
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa fa-sign-out mr-10"></i> {{__('Logout')}}</a>
            </div>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                {{ csrf_field() }}
            </form>
        </div>
    </div>
</div>
