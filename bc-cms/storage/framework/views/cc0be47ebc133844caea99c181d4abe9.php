<?php
$user = Auth::user();
[$notifications,$countUnread] = getNotify();

$languages = \Modules\Language\Models\Language::getActive();
$locale = App::getLocale();
$theme = \Modules\Theme\ThemeManager::currentProvider();
?>

<div class="header-logo flex-shrink-0" style="display:flex;align-items:center;height:100%;">
    <a href="<?php echo e(route('admin.index')); ?>" style="display:flex;align-items:center;text-decoration:none;padding:0 16px;">
        <img src="<?php echo e(url('/images/logo.png')); ?>" alt="Tsoka" style="height:32px;width:auto;display:block;"
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
            <a href="<?php echo e(url('/')); ?>" class="btn btn-link" target="_blank"><i class="fa fa-eye"></i> <?php echo e(__('Home')); ?>

            </a>
        </div>
    </div>
    <div class="widgets-right flex-shrink-0 d-flex">
        <?php if(!empty($languages) and is_enable_multi_lang()): ?>
        <div class="dropdown header-widget widget-user widget-language flex-shrink-0">
            <div data-toggle="dropdown" class="user-dropdown d-flex align-items-center" aria-haspopup="true" aria-expanded="false">
                <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($locale == $language->locale): ?>
                        <div class="user-info flex-grow-1 d-flex">
                            <?php if($language->flag): ?>
                                <span class="flag-icon mr-2 flag-icon-<?php echo e($language->flag); ?>"></span>
                            <?php endif; ?>
                            <?php echo e($language->name); ?>

                        </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <i class="fa fa-angle-down"></i>
            </div>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($language->locale == $locale) continue; ?>

                    <a class="dropdown-item" href="<?php echo e(route('language.set-admin-lang',['locale'=>$language->locale])); ?>">
                        <?php if($language->flag): ?>
                            <span class="flag-icon flag-icon-<?php echo e($language->flag); ?>"></span>
                        <?php endif; ?>
                        <?php echo e($language->name); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="dropdown header-widget widget-user pt-2 dropdown-notifications flex-shrink-0" style="min-width: 0">
            <div data-toggle="dropdown" class="user-dropdown d-flex align-items-center" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-lg fa-bell m-1 p-1"></i>
                <span class="badge badge-danger notification-icon"><?php echo e($countUnread); ?></span>
            </div>
            <div class="dropdown-menu overflow-auto notify-items dropdown-container dropdown-menu-right dropdown-large" aria-labelledby="dropdownMenuButton">
                <div class="dropdown-toolbar">
                    <div class="dropdown-toolbar-actions">
                        <a href="#" class="markAllAsRead"><?php echo e(__('Mark all as read')); ?></a>
                    </div>
                    <h3 class="dropdown-toolbar-title"><?php echo e(__('Notifications')); ?> (<span class="notif-count"><?php echo e($countUnread); ?></span>)</h3>
                </div>
                <ul class="dropdown-list-items p-0 m-0">
                    <?php if(count($notifications)> 0): ?>
                        <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $oneNotification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
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

                            ?>
                            <li class="notification <?php echo e($active); ?>">
                                <a class="<?php echo e($class); ?>" data-id="<?php echo e($idNotification); ?>" href="<?php echo e($link); ?>">
                                    <div class="media">
                                        <div class="media-left">
                                              <div class="media-object">
                                                  <?php if($avatar): ?>
                                                    <img class="image-responsive" src="<?php echo e($avatar); ?>" alt="<?php echo e($name); ?>">
                                                  <?php else: ?>
                                                      <span class="avatar-text"><?php echo e(ucfirst($name[0])); ?></span>
                                                  <?php endif; ?>
                                              </div>
                                            </div>
                                        <div class="media-body">
                                            <?php echo $title; ?>

                                              <div class="notification-meta">
                                                    <small class="timestamp"><?php echo e(format_interval($oneNotification->created_at)); ?></small>
                                                  </div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </ul>
                <div class="dropdown-footer text-center">
                    <a href="<?php echo e(route('core.admin.notification.loadNotify')); ?>"><?php echo e(__('View More')); ?></a>
                </div>
            </div>
        </div>
        <div class="dropdown header-widget widget-user flex-shrink-0">
            <div data-toggle="dropdown" class="user-dropdown d-flex align-items-center" aria-haspopup="true" aria-expanded="false">
                <span class="user-avatar flex-shrink-0">
                     <?php if($avatar_url = $user->getAvatarUrl()): ?>
                        <div class="avatar avatar-cover" style="background-image: url('<?php echo e($user->getAvatarUrl()); ?>')"></div>
                    <?php else: ?>
                        <span class="avatar-text"><?php echo e(ucfirst($user->getDisplayName()[0])); ?></span>
                    <?php endif; ?>
                </span>
                <div class="user-info flex-grow-1">
                    <div class="user-name"><?php echo e($user->getDisplayName()); ?></div>
                    <div class="user-role"><?php echo e(ucfirst($user->role->name ?? '')); ?></div>
                </div>
                <i class="fa fa-angle-down"></i>
            </div>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <?php if($user->hasPermission('dashboard_vendor_access')): ?>
                    <a class="dropdown-item" href="<?php echo e(route('vendor.dashboard')); ?>"><i class="fa fa-line-chart mr-10"></i> <?php echo e(__('Vendor Dashboard')); ?></a>
                    <div class="dropdown-divider"></div>
                <?php endif; ?>
                <a class="dropdown-item" href="<?php echo e(route('user.profile.index')); ?>"><i class="fa fa-address-card mr-10"></i> <?php echo e(__('My profile')); ?></a>
                <a class="dropdown-item" href="<?php echo e(route('user.booking_history')); ?>"><i class="fa fa-clock-o mr-10"></i> <?php echo e(__('Booking History')); ?></a>
                <a class="dropdown-item" href="<?php echo e(route('user.admin.password',['id'=>$user->id])); ?>"><i class="fa fa-lock mr-10"></i> <?php echo e(__('Change Password')); ?></a>
                <?php if($user->hasPermission('dashboard_access')): ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo e(route('admin.index')); ?>"><i class="fa fa-dashboard mr-10"></i> <?php echo e(__('Admin Dashboard')); ?></a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa fa-sign-out mr-10"></i> <?php echo e(__('Logout')); ?></a>
            </div>
            <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" style="display: none;">
                <?php echo e(csrf_field()); ?>

            </form>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Layout/admin/parts/header.blade.php ENDPATH**/ ?>