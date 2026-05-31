
<?php $__env->startSection('content'); ?>
    <form action="<?php echo e(route('agencies.admin.store', ['id' => $row->id ? $row->id : '-1', 'lang' => request()->query('lang')])); ?>"
        method="post" id="form_admin_agecies">
        <?php echo csrf_field(); ?>
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb20">
                <div class="">
                    <h1 class="title-bar"><?php echo e($row->id ? __('Edit: ') . $row->name : __('Add new agency')); ?></h1>
                    <?php if($row->slug): ?>
                        <p class="item-url-demo"><?php echo e(__('Permalink')); ?>: <?php echo e(url('agency')); ?>/<a href="#"
                                class="open-edit-input" data-name="slug"><?php echo e($row->slug); ?></a>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="">
                    <?php if($row->slug): ?>
                        <a class="btn btn-primary btn-sm" href="<?php echo e($row->getDetailUrl(request()->query('lang'))); ?>"
                            target="_blank"><?php echo e(__('View agency')); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php if($row->id): ?>
                <?php echo $__env->make('Language::admin.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
            <div class="lang-content-box">
                <div class="row">
                    <div class="col-md-9">
                        <?php echo $__env->make('Agency::admin.agency.include.content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo $__env->make('Agency::admin.agency.include.list-agent', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo $__env->make('Core::admin/seo-meta/seo-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-md-3">
                        <div class="panel">
                            <div class="panel-title"><strong><?php echo e(__('Publish')); ?></strong></div>
                            <div class="panel-body">
                                <?php if(is_default_lang()): ?>
                                    <div>
                                        <label><input <?php if((!empty($row->id) && $row->status == 'publish') || empty($row->id)): ?> checked <?php endif; ?> type="radio"
                                                name="status" value="publish"> <?php echo e(__('Publish')); ?>

                                        </label>
                                    </div>
                                    <div>
                                        <label><input <?php if(!empty($row->id) && $row->status == 'draft'): ?> checked <?php endif; ?> type="radio"
                                                name="status" value="draft"> <?php echo e(__('Draft')); ?>

                                        </label>
                                    </div>
                                <?php endif; ?>
                                <div class="text-right">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i>
                                        <?php echo e(__('Save Changes')); ?></button>
                                </div>
                            </div>
                        </div>
                        <?php if(is_default_lang()): ?>
                            <div class="panel">
                                <div class="panel-title"><strong><?php echo e(__('Author Setting')); ?></strong></div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <?php
                                        $user = !empty($row->author_id) ? \Themes\Findhouse\User\Models\User::find($row->author_id) : false;
                                        \App\Helpers\AdminForm::select2(
                                            'author_id',
                                            [
                                                'configs' => [
                                                    'ajax' => [
                                                        'url' => url('/admin/module/user/getForSelect2'),
                                                        'dataType' => 'json',
                                                    ],
                                                    'allowClear' => true,
                                                    'placeholder' => __('-- Select User --'),
                                                ],
                                            ],
                                            !empty($user->id) ? [$user->id, $user->getDisplayName() . ' (#' . $user->id . ')'] : false,
                                        );
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if(is_default_lang()): ?>
                            <div class="panel">
                                <div class="panel-title"><strong><?php echo e(__('Feature Image')); ?></strong></div>
                                <div class="panel-body">
                                    <div class="form-group image-feature">
                                        <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('image_id', $row->image_id); ?>

                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <?php echo App\Helpers\MapEngine::scripts(); ?>

    <script>
        jQuery(function($) {
            new BCMapEngine('map_content', {
                disableScripts: true,
                fitBounds: true,
                center: [<?php echo e($row->map_lat ?? '51.505'); ?>, <?php echo e($row->map_lng ?? '-0.09'); ?>],
                zoom: <?php echo e($row->map_zoom ?? '8'); ?>,
                ready: function(engineMap) {
                    <?php if($row->map_lat && $row->map_lng): ?>
                        engineMap.addMarker([<?php echo e($row->map_lat); ?>, <?php echo e($row->map_lng); ?>], {
                            icon_options: {}
                        });
                    <?php endif; ?>
                    engineMap.on('click', function(dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                    engineMap.on('zoom_changed', function(zoom) {
                        $("input[name=map_zoom]").attr("value", zoom);
                    });
                    engineMap.searchBox($('.bc_searchbox'), function(dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                }
            });
        })
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Agency/Views/admin/agency/detail.blade.php ENDPATH**/ ?>