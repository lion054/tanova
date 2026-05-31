<?php $__env->startPush('css'); ?>
<style>
#sp-wrap *,#sp-wrap *::before,#sp-wrap *::after{box-sizing:border-box;}
#sp-wrap{font-family:'Inter',system-ui,sans-serif;}
#sp-wrap .sp-eyebrow{font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:#9ca3af;margin:0 0 4px;}
#sp-wrap .sp-title{font-size:26px;font-weight:700;color:#111827;letter-spacing:-.5px;margin:0 0 28px;line-height:1.2;}
#sp-wrap .sp-box{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.04);max-width:600px;}
#sp-wrap .sp-nav{display:flex;border-bottom:1.5px solid #e5e7eb;background:#fafafa;padding:0 28px;}
#sp-wrap .sp-nav-btn{padding:14px 16px;font-size:13px;font-weight:600;color:#6b7280;border:none;background:none;cursor:pointer;position:relative;text-decoration:none;display:inline-block;transition:color .15s;white-space:nowrap;}
#sp-wrap .sp-nav-btn:hover{color:#111827;text-decoration:none;}
#sp-wrap .sp-nav-btn.sp-active{color:#111827;}
#sp-wrap .sp-nav-btn.sp-active::after{content:'';position:absolute;bottom:-1.5px;left:0;right:0;height:2.5px;background:#111827;border-radius:2px 2px 0 0;}
#sp-wrap .sp-body{padding:32px;}
#sp-wrap .sp-sec{font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#9ca3af;margin:0 0 20px;}
#sp-wrap .sp-f{display:flex;flex-direction:column;gap:5px;margin-bottom:16px;}
#sp-wrap .sp-lbl{font-size:10px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7280;}
#sp-wrap .sp-in{
    width:100% !important;padding:10px 12px !important;
    border:1.5px solid #d1d5db !important;border-radius:8px !important;
    font-size:13px !important;color:#111827 !important;background:#fff !important;
    font-family:inherit !important;outline:none !important;box-shadow:none !important;
    line-height:1.5 !important;-webkit-appearance:none;appearance:none;
    transition:border-color .15s,box-shadow .15s;
}
#sp-wrap .sp-in:focus{border-color:#111827 !important;box-shadow:0 0 0 3px rgba(17,24,39,.06) !important;}
#sp-wrap .sp-hint{font-size:11px;color:#9ca3af;margin-top:4px;line-height:1.5;}
#sp-wrap .sp-strength{display:flex;gap:4px;margin-top:8px;}
#sp-wrap .sp-bar{flex:1;height:3px;border-radius:2px;background:#e5e7eb;transition:background .2s;}
#sp-wrap .sp-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 22px;background:#0a0a0a;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;margin-top:8px;}
#sp-wrap .sp-btn:hover{opacity:.85;color:#fff;}
</style>
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<div id="sp-wrap">

<p class="sp-eyebrow"><?php echo e(__("Account")); ?></p>
<h1 class="sp-title"><?php echo e(__("Settings")); ?></h1>

<div class="sp-box">
    <div class="sp-nav">
        <a href="<?php echo e(route('user.profile.index')); ?>" class="sp-nav-btn"><?php echo e(__("Personal Information")); ?></a>
        <a href="<?php echo e(route('user.profile.index')); ?>" class="sp-nav-btn"><?php echo e(__("Location")); ?></a>
        <span class="sp-nav-btn sp-active"><?php echo e(__("Security")); ?></span>
    </div>

    <div class="sp-body">
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <p class="sp-sec"><?php echo e(__("Change Password")); ?></p>

        <form action="<?php echo e(route('user.change_password.update')); ?>" method="post">
            <?php echo csrf_field(); ?>
            <div class="sp-f">
                <label class="sp-lbl" for="sp-cur"><?php echo e(__("Current Password")); ?></label>
                <input class="sp-in" type="password" id="sp-cur" name="current-password" required
                       placeholder="<?php echo e(__('Enter your current password')); ?>">
            </div>
            <div class="sp-f">
                <label class="sp-lbl" for="sp-new"><?php echo e(__("New Password")); ?></label>
                <input class="sp-in" type="password" id="sp-new" name="new-password" required minlength="8"
                       placeholder="<?php echo e(__('At least 8 characters')); ?>" oninput="spStrength(this.value)">
                <div class="sp-strength">
                    <div class="sp-bar" id="spb1"></div>
                    <div class="sp-bar" id="spb2"></div>
                    <div class="sp-bar" id="spb3"></div>
                    <div class="sp-bar" id="spb4"></div>
                </div>
                <p class="sp-hint"><?php echo e(__("Requires uppercase, lowercase, number and symbol.")); ?></p>
            </div>
            <div class="sp-f">
                <label class="sp-lbl" for="sp-conf"><?php echo e(__("Confirm New Password")); ?></label>
                <input class="sp-in" type="password" id="sp-conf" name="new-password_confirmation" required minlength="8"
                       placeholder="<?php echo e(__('Repeat new password')); ?>">
            </div>
            <button type="submit" class="sp-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75M3.75 21h16.5a.75.75 0 00.75-.75V11.25a.75.75 0 00-.75-.75H3.75a.75.75 0 00-.75.75v9a.75.75 0 00.75.75z"/></svg>
                <?php echo e(__("Update Password")); ?>

            </button>
        </form>
    </div>
</div>

</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
<script>
function spStrength(pw){
    var s=0;
    if(pw.length>=8)s++;
    if(/[A-Z]/.test(pw))s++;
    if(/[0-9]/.test(pw))s++;
    if(/[^A-Za-z0-9]/.test(pw))s++;
    var c=['','#ef4444','#f59e0b','#3b82f6','#22c55e'];
    for(var i=1;i<=4;i++) document.getElementById('spb'+i).style.background=i<=s?c[s]:'#e5e7eb';
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/changePassword.blade.php ENDPATH**/ ?>