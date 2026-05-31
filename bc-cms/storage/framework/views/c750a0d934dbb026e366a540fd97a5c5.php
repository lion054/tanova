
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__("Airport: :name",['name'=>@$row->code])); ?></h1>
            <a href="<?php echo e(URL::signedRoute('flight.admin.airport.importIATA')); ?>" class="btn btn-primary" onclick="return confirm('Do you want to import all list airports from IATA?')"><?php echo e(__('Import from IATA')); ?></a>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="row">
            <div class="col-md-4 mb40">
                <div class="panel">
                    <div class="panel-title"><?php echo e(__("Add Airport")); ?></div>
                    <div class="panel-body">
                        <form action="<?php echo e(route('flight.admin.airport.store',['id'=>$row->id??-1])); ?>" method="post">
                            <?php echo csrf_field(); ?>
                            <?php echo $__env->make('Flight::admin.airport.form',['map_full'=>true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <div class="">
                                <button class="btn btn-primary" type="submit"><?php echo e(__("Add new")); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="filter-div d-flex justify-content-between ">
                    <div class="col-left">
                        <?php if(!empty($rows)): ?>
                            <form method="post" action="<?php echo e(route('flight.admin.airport.bulkEdit')); ?>" class="filter-form filter-form-left d-flex justify-content-start">
                                <?php echo e(csrf_field()); ?>

                                <select name="action" class="form-control">
                                    <option value=""><?php echo e(__(" Bulk Action ")); ?></option>
                                    <option value="publish"><?php echo e(__(" Mark as Publish ")); ?></option>
                                    <option value="draft"><?php echo e(__(" Mark as Draft ")); ?></option>
                                    <option value="delete"><?php echo e(__(" Delete ")); ?></option>
                                </select>
                                <button data-confirm="<?php echo e(__("Do you want to delete?")); ?>" class="btn-info btn btn-icon dungdt-apply-form-btn" type="button"><?php echo e(__('Apply')); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="col-left">
                        <form method="get" action="<?php echo e(route('flight.admin.airport.index')); ?> " class="filter-form filter-form-right d-flex justify-content-end" role="search">
                            <input type="text" name="s" value="<?php echo e(Request()->s); ?>" class="form-control" placeholder="<?php echo e(__("Search by name")); ?>">
                            <button class="btn-info btn btn-icon btn_search" id="search-submit" type="submit"><?php echo e(__('Search')); ?></button>
                        </form>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-title"><?php echo e(__("All")); ?></div>
                    <div class="panel-body">
                        <form class="bc-form-item">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th width="60px"><input type="checkbox" class="check-all"></th>
                                    <th><?php echo e(__("Name")); ?></th>
                                    <th><?php echo e(__("IATA Code")); ?></th>
                                    <th><?php echo e(__("Address")); ?></th>
                                    <th><?php echo e(__("Status")); ?></th>
                                    <th class="date"><?php echo e(__("Date")); ?></th>
                                    <th class="date"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if(count($rows) > 0): ?>
                                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><input type="checkbox" class="check-item" name="ids[]" value="<?php echo e($row->id); ?>"></td>
                                            <td class="title">
                                                <a href="<?php echo e(route('flight.admin.airport.edit',['id'=>$row->id])); ?>"><?php echo e($row->name); ?></a>
                                            </td>
                                            <td><?php echo e($row->code); ?></td>
                                            <td><?php echo e($row->address); ?></td>
                                            <td><span class="badge badge-<?php echo e($row->status_badge); ?>"><?php echo e($row->status_text); ?></span></td>
                                            <td><?php echo e(display_date($row->updated_at)); ?></td>
                                            <td><a class="btn btn-primary btn-sm" href="<?php echo e(route('flight.admin.airport.edit',['id'=>$row->id])); ?>"><i class="fa fa-edit"></i> <?php echo e(__('Edit')); ?></a></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4"><?php echo e(__("No data")); ?></td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between align-items-center">
                                <?php echo e($rows->appends(request()->query())->links()); ?>

                                <?php echo e(__("Found :count airport(s)",['count'=>$rows->total()])); ?>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <?php echo \App\Helpers\MapEngine::scripts(); ?>

    <script>
        jQuery(function ($) {
            "use strict"
            new BCMapEngine('map_content', {
                disableScripts:true,
                fitBounds: true,
                center: [<?php echo e($row->map_lat ?? setting_item('map_lat_default',51.505 )); ?>, <?php echo e($row->map_lng ?? setting_item('map_lng_default',-0.09 )); ?>],
                zoom:<?php echo e($row->map_zoom ?? "8"); ?>,
                ready: function (engineMap) {
                    <?php if($row->map_lat && $row->map_lng): ?>
                    engineMap.addMarker([<?php echo e($row->map_lat); ?>, <?php echo e($row->map_lng); ?>], {
                        icon_options: {}
                    });
                    <?php endif; ?>
                    engineMap.on('click', function (dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                    engineMap.on('zoom_changed', function (zoom) {
                        $("input[name=map_zoom]").attr("value", zoom);
                    })
                }
            });
        })
    </script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Flight/Views/admin/airport/index.blade.php ENDPATH**/ ?>