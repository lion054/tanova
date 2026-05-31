<?php $__env->startPush('css'); ?>
<style>
#sp-wrap *,#sp-wrap *::before,#sp-wrap *::after{box-sizing:border-box;}
#sp-wrap{font-family:'Inter',system-ui,sans-serif;}
#sp-wrap .sp-eyebrow{font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:#9ca3af;margin:0 0 4px;}
#sp-wrap .sp-title{font-size:26px;font-weight:700;color:#111827;letter-spacing:-.5px;margin:0 0 28px;line-height:1.2;}
/* Outer card */
#sp-wrap .sp-box{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.04);}
/* Tab nav */
#sp-wrap .sp-nav{display:flex;border-bottom:1.5px solid #e5e7eb;background:#fafafa;padding:0 28px;}
#sp-wrap .sp-nav-btn{padding:14px 16px;font-size:13px;font-weight:600;color:#6b7280;border:none;background:none;cursor:pointer;position:relative;text-decoration:none;display:inline-block;transition:color .15s;white-space:nowrap;}
#sp-wrap .sp-nav-btn:hover{color:#111827;text-decoration:none;}
#sp-wrap .sp-nav-btn.sp-active,#sp-wrap .sp-nav-btn.is-tab-el-active{color:#111827;}
#sp-wrap .sp-nav-btn.sp-active::after,#sp-wrap .sp-nav-btn.is-tab-el-active::after{content:'';position:absolute;bottom:-1.5px;left:0;right:0;height:2.5px;background:#111827;border-radius:2px 2px 0 0;}
/* Tab body */
#sp-wrap .sp-body{padding:32px;}
/* Avatar */
#sp-wrap .sp-av-row{display:flex;align-items:center;gap:20px;margin-bottom:28px;padding-bottom:24px;border-bottom:1px solid #f3f4f6;}
#sp-wrap .sp-av{width:68px;height:68px;border-radius:50%;background:#e5e7eb;flex-shrink:0;overflow:hidden;border:none;position:relative;cursor:pointer;}
#sp-wrap .sp-av .btn-file{position:absolute;inset:0;z-index:2;opacity:0;cursor:pointer;margin:0;}
#sp-wrap .sp-av .btn-file input[type=file]{position:absolute;inset:0;width:100%;height:100%;cursor:pointer;opacity:0;}
#sp-wrap .sp-av-name{font-size:15px;font-weight:600;color:#111827;margin:0 0 2px;}
#sp-wrap .sp-av-sub{font-size:12px;color:#9ca3af;margin:0 0 10px;}
#sp-wrap .sp-up-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border:1.5px solid #d1d5db;border-radius:8px;font-size:12px;font-weight:600;color:#374151;cursor:pointer;background:#fff;transition:border-color .15s;}
#sp-wrap .sp-up-btn:hover{border-color:#374151;}
/* Section label */
#sp-wrap .sp-sec{font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#9ca3af;margin:0 0 12px;}
/* Field grid */
#sp-wrap .sp-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
#sp-wrap .sp-row.sp-single{grid-template-columns:1fr;}
@media(max-width:640px){#sp-wrap .sp-row{grid-template-columns:1fr;}}
#sp-wrap .sp-col2{grid-column:1/-1;}
/* Field */
#sp-wrap .sp-f{display:flex;flex-direction:column;gap:5px;}
#sp-wrap .sp-lbl{font-size:10px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7280;}
#sp-wrap .sp-in,
#sp-wrap .sp-sel,
#sp-wrap .sp-ta{
    width:100% !important;padding:10px 12px !important;
    border:1.5px solid #d1d5db !important;border-radius:8px !important;
    font-size:13px !important;color:#111827 !important;background:#fff !important;
    transition:border-color .15s,box-shadow .15s;font-family:inherit !important;
    outline:none !important;box-shadow:none !important;line-height:1.5 !important;
    -webkit-appearance:none;appearance:none;
}
#sp-wrap .sp-in:focus,
#sp-wrap .sp-sel:focus,
#sp-wrap .sp-ta:focus{border-color:#111827 !important;box-shadow:0 0 0 3px rgba(17,24,39,.06) !important;}
#sp-wrap .sp-sel{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;background-repeat:no-repeat !important;background-position:right 12px center !important;padding-right:36px !important;}
#sp-wrap .sp-ta{resize:vertical;min-height:90px;}
/* Save button */
#sp-wrap .sp-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 22px;background:#0a0a0a;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;margin-top:8px;}
#sp-wrap .sp-btn:hover{opacity:.85;color:#fff;}
/* Danger zone */
#sp-wrap .sp-danger{background:#fff;border:1px solid #fecaca;border-radius:16px;padding:24px 32px;margin-top:20px;}
#sp-wrap .sp-danger h4{font-size:14px;font-weight:600;color:#dc2626;margin:0 0 6px;}
#sp-wrap .sp-danger p{font-size:13px;color:#6b7280;margin:0 0 16px;}
#sp-wrap .sp-dbtn{display:inline-flex;align-items:center;padding:8px 18px;border:1.5px solid #dc2626;border-radius:8px;font-size:12px;font-weight:600;color:#dc2626;text-decoration:none;}
#sp-wrap .sp-dbtn:hover{background:#fff1f2;color:#dc2626;}
/* Override GoTrip tab pane visibility */
#sp-wrap .tabs__pane{display:none !important;}
#sp-wrap .tabs__pane.is-tab-el-active{display:block !important;}
</style>
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<div id="sp-wrap">

<p class="sp-eyebrow"><?php echo e(__("Account")); ?></p>
<h1 class="sp-title"><?php echo e(__("Settings")); ?></h1>

<?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<form action="<?php echo e(route('user.profile.update')); ?>" method="post" enctype="multipart/form-data" class="input-has-icon">
<?php echo csrf_field(); ?>

<div class="sp-box js-tabs">
    
    <div class="sp-nav js-tabs-controls">
        <button type="button" class="sp-nav-btn sp-active js-tabs-button is-tab-el-active" data-tab-target=".-tab-item-1"><?php echo e(__("Personal Information")); ?></button>
        <button type="button" class="sp-nav-btn js-tabs-button" data-tab-target=".-tab-item-2"><?php echo e(__("Location")); ?></button>
        <a href="<?php echo e(route('user.change_password')); ?>" class="sp-nav-btn"><?php echo e(__("Security")); ?></a>
    </div>

    <div class="tabs__content js-tabs-content">

    
    <div class="tabs__pane -tab-item-1 is-tab-el-active">
    <div class="sp-body">

        
        <?php
            $avatarUrl = get_file_url(old('avatar_id', $dataUser->avatar_id)) ?? $dataUser->getAvatarUrl() ?? null;
            $initials  = strtoupper(substr($dataUser->first_name ?? 'U', 0, 1) . substr($dataUser->last_name ?? '', 0, 1));
        ?>
        <div class="sp-av-row">
            
            <div class="sp-av upload-btn-wrapper">
                <img class="image-demo"
                     style="width:100%;height:100%;object-fit:cover;display:block;"
                     src="<?php echo e($avatarUrl ?? $dataUser->getAvatarUrl()); ?>"
                     alt="<?php echo e($dataUser->first_name); ?>">
                <label class="btn-file">
                    <input type="file" id="sp-avatar-file">
                </label>
                <input type="text" class="form-control text-view" style="display:none;" readonly
                       data-error="<?php echo e(__('Error upload...')); ?>"
                       data-loading="<?php echo e(__('Loading...')); ?>"
                       value="<?php echo e($avatarUrl ?? ''); ?>">
                <input type="hidden" class="form-control" name="avatar_id"
                       value="<?php echo e(old('avatar_id', $dataUser->avatar_id) ?? ''); ?>">
            </div>
            <div>
                <p class="sp-av-name"><?php echo e($dataUser->first_name); ?> <?php echo e($dataUser->last_name); ?></p>
                <p class="sp-av-sub"><?php echo e(__("PNG or JPG · max 800px")); ?></p>
                <label class="sp-up-btn" for="sp-avatar-file">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    <?php echo e(__("Upload photo")); ?>

                </label>
            </div>
        </div>

        <?php if($is_vendor_access): ?>
        <p class="sp-sec"><?php echo e(__("Business")); ?></p>
        <div class="sp-row sp-single" style="margin-bottom:24px;">
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("Business Name")); ?></label>
                <input class="sp-in" type="text" name="business_name" value="<?php echo e(old('business_name', $dataUser->business_name)); ?>">
            </div>
        </div>
        <?php endif; ?>

        <p class="sp-sec"><?php echo e(__("Identity")); ?></p>
        <div class="sp-row" style="margin-bottom:24px;">
            <div class="sp-f sp-col2">
                <label class="sp-lbl"><?php echo e(__("Username")); ?></label>
                <input class="sp-in" type="text" name="user_name" minlength="4" value="<?php echo e(old('user_name', $dataUser->user_name)); ?>">
            </div>
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("First Name")); ?></label>
                <input class="sp-in" type="text" name="first_name" value="<?php echo e(old('first_name', $dataUser->first_name)); ?>">
            </div>
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("Last Name")); ?></label>
                <input class="sp-in" type="text" name="last_name" value="<?php echo e(old('last_name', $dataUser->last_name)); ?>">
            </div>
        </div>

        <p class="sp-sec"><?php echo e(__("Contact")); ?></p>
        <div class="sp-row" style="margin-bottom:24px;">
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("Email")); ?></label>
                <input class="sp-in" type="text" name="email" value="<?php echo e(old('email', $dataUser->email)); ?>">
            </div>
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("Phone")); ?></label>
                <input class="sp-in" type="text" name="phone" value="<?php echo e(old('phone', $dataUser->phone)); ?>">
            </div>
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("Birthday")); ?></label>
                <input class="sp-in date-picker has-value" type="text" name="birthday"
                       value="<?php echo e(old('birthday', $dataUser->birthday ? display_date($dataUser->birthday) : '')); ?>">
            </div>
        </div>

        <p class="sp-sec"><?php echo e(__("About")); ?></p>
        <div class="sp-row sp-single" style="margin-bottom:28px;">
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("About Yourself")); ?></label>
                <textarea class="sp-ta" name="bio" rows="3"><?php echo e(old('bio', $dataUser->bio)); ?></textarea>
            </div>
        </div>

        <button type="submit" class="sp-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <?php echo e(__("Save Changes")); ?>

        </button>
    </div>
    </div>

    
    <div class="tabs__pane -tab-item-2">
    <div class="sp-body">

        <p class="sp-sec"><?php echo e(__("Address")); ?></p>
        <div class="sp-row" style="margin-bottom:24px;">
            <div class="sp-f sp-col2">
                <label class="sp-lbl"><?php echo e(__("Address Line 1")); ?></label>
                <input class="sp-in" type="text" name="address" value="<?php echo e(old('address', $dataUser->address)); ?>">
            </div>
            <div class="sp-f sp-col2">
                <label class="sp-lbl"><?php echo e(__("Address Line 2")); ?></label>
                <input class="sp-in" type="text" name="address2" value="<?php echo e(old('address2', $dataUser->address2)); ?>">
            </div>
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("City")); ?></label>
                <input class="sp-in" type="text" name="city" value="<?php echo e(old('city', $dataUser->city)); ?>">
            </div>
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("State / Province")); ?></label>
                <input class="sp-in" type="text" name="state" value="<?php echo e(old('state', $dataUser->state)); ?>">
            </div>
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("Country")); ?></label>
                <select class="sp-sel" name="country">
                    <option value=""><?php echo e(__('— Select country —')); ?></option>
                    <?php $__currentLoopData = get_country_lists(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option <?php if(old('country', $dataUser->country ?? '') == $id): ?> selected <?php endif; ?> value="<?php echo e($id); ?>"><?php echo e($name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="sp-f">
                <label class="sp-lbl"><?php echo e(__("ZIP / Postal Code")); ?></label>
                <input class="sp-in" type="text" name="zip_code" value="<?php echo e(old('zip_code', $dataUser->zip_code)); ?>">
            </div>
        </div>

        <button type="submit" class="sp-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <?php echo e(__("Save Changes")); ?>

        </button>
    </div>
    </div>

    </div>
</div>
</form>


<?php if(!empty(setting_item('user_enable_permanently_delete')) && !is_admin()): ?>
<div class="sp-danger">
    <h4><?php echo e(__("Delete Account")); ?></h4>
    <p><?php echo clean(setting_item_with_lang('user_permanently_delete_content', '', __('Your account will be permanently deleted. This cannot be undone.'))); ?></p>
    <a data-toggle="modal" data-target="#permanentlyDeleteAccount" class="sp-dbtn" href="#"><?php echo e(__('Delete your account')); ?></a>
</div>
<div class="modal fade" id="permanentlyDeleteAccount" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:16px;border:1px solid #e5e7eb;">
            <div class="modal-header" style="border-bottom:1px solid #e5e7eb;padding:20px 24px;">
                <h5 style="font-size:14px;font-weight:600;color:#111827;margin:0;"><?php echo e(__('Confirm account deletion')); ?></h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body" style="font-size:13px;color:#6b7280;padding:20px 24px;">
                <?php echo clean(setting_item_with_lang('user_permanently_delete_content_confirm')); ?>

            </div>
            <div class="modal-footer" style="border-top:1px solid #e5e7eb;padding:16px 24px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-size:12px;"><?php echo e(__('Cancel')); ?></button>
                <a href="<?php echo e(route('user.permanently.delete')); ?>" class="sp-dbtn"><?php echo e(__('Confirm Delete')); ?></a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/profile.blade.php ENDPATH**/ ?>