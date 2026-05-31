
<?php $__env->startSection('content'); ?>
    <div class="container">
        <div class="row">
            <div class="col-md-2"></div>
            <div class="col-md-8">
                <div class="d-flex justify-content-between mb20">
                    <h1 class="title-bar"><?php echo e(__('System Updater')); ?></h1>
                </div>
                <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                <?php if($ready_for_update): ?>
                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__('Update booking core')); ?></strong></div>
                    <div class="panel-body">

                            <?php if($updater_latest_version = setting_item('updater_latest_version') and version_compare(config('app.version'),$updater_latest_version,'=')): ?>
                                <p class="alert-success alert"><strong><?php echo e(__("You are using newest version of Booking Core: :version",['version'=>$updater_latest_version])); ?></strong></p>
                            <?php endif; ?>

                            <p><strong><?php echo e(__("Your license key: :key",['key'=>setting_item('envato_license_key')])); ?></strong></p>
                            <?php if($last_check_update = setting_item('last_check_update')): ?>
                                <p><?php echo e(__("Last check for update: :date",['date'=>display_datetime((int)$last_check_update)])); ?></p>
                            <?php endif; ?>

                            <?php if($updater_last_success = setting_item('updater_last_success')): ?>
                                <p><?php echo e(__("Last update success: :date",['date'=>display_datetime((int)$updater_last_success)])); ?></p>
                            <?php endif; ?>
                            <form action="<?php echo e(route('core.admin.updater.check_update')); ?>" method="post">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-info "><?php echo e(__("Check for update")); ?>

                                </button>
                            </form>

                            <?php if($updater_latest_version = setting_item('updater_latest_version') and version_compare(config('app.version'),$updater_latest_version,'<')): ?>
                                <hr>
                                <p class="text-success"><strong><?php echo e(__("Your current version: :version",['version'=>config('app.version')])); ?></strong></p>
                                <p class="text-primary"><strong><?php echo e(__("Latest version available: :version",['version'=>$updater_latest_version])); ?></strong></p>
                                <p><label ><input type="checkbox" class="check_installation_term"> <?php echo e(__("I already back up all files and database")); ?></label></p>
                                <button type="submit" class="btn btn-primary btn-do-update-now bc-form "><?php echo e(__("Update now")); ?>

                                    <i class="fa fa-spinner fa-spin fa-fw"></i>
                                </button>
                            <?php endif; ?>

                            <hr>

                            <span><?php echo e(__('or')); ?> <a href="#" class="show-license-form"><?php echo e(__("change license info")); ?></a></span>

                    </div>
                </div>
                <?php endif; ?>
                <div class="panel <?php if($ready_for_update): ?> d-none <?php endif; ?>" id="license_key_form">
                    <div class="panel-title"><strong><?php echo e(__('License Key Information')); ?></strong></div>
                    <div class="panel-body">
                        <div class="alert alert-info">
                            <?php echo e(__("Please enter envato username and license key (purchase code) to get autoupdate")); ?>

                        </div>
                        <form action="<?php echo e(route('core.admin.updater.store_license')); ?>" method="post">
                            <?php echo csrf_field(); ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label ><strong><?php echo e(__("Envato username")); ?></strong></label>
                                        <div>
                                            <input type="text" name="envato_username" value="<?php echo e(setting_item('envato_username')); ?>" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label ><strong><?php echo e(__("Your license key (Purchase code)")); ?></strong></label>
                                        <div>
                                            <input type="text" name="envato_license_key" value="<?php echo e(setting_item('envato_license_key')); ?>" class="form-control">
                                        </div>
                                        <span><i><a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank"><?php echo e(__("How can I get my license key?")); ?></a></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> <?php echo e(__("Save changes")); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script>
        (function ($) {
            $('.btn-do-update-now').click(function (e) {
                e.preventDefault();
                var me = $(this);

                if(!$('.check_installation_term').prop('checked')){
                    bootbox.alert(
                        {
                            title:'<?php echo e(__("Warning")); ?>',
                            message:'<?php echo e(__('Please make sure you back up data before updating')); ?>'
                        }
                    );
                    return;
                }

                bootbox.confirm({
                    title:'<?php echo e(__("Confirmation")); ?>',
                    message:'<?php echo e(__('Are you want to update now?. Please make sure you backup all your files and database first')); ?>',
                    callback:function (res) {
                        if(!res) return;
                        me.addClass('loading');

                        $.ajax({
                            url:'<?php echo e(route('core.admin.updater.do_update')); ?>',
                            method:'post',
                            success:function (json) {
                                me.removeClass('loading');
                                if(json.message)
                                {
                                    bootbox.alert(
                                        {
                                            title:json.status ? '<?php echo e(__("Warning")); ?>' : '<?php echo e(__('Notice')); ?>',
                                            message:json.message
                                        }
                                    );
                                }

                                // if(json.status){
                                //     window.location.reload();
                                // }
                            },
                            error:function (e) {
                                me.removeClass('loading');
                            }
                        });

                    }
                });

            });
            $('.show-license-form').click(function (e) {

                e.preventDefault();

                $('#license_key_form').removeClass('d-none');
            })
        })(jQuery)
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Core/Views/admin/updater/index.blade.php ENDPATH**/ ?>