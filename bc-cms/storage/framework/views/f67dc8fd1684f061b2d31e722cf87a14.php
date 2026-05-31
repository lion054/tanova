
<?php $__env->startSection('content'); ?>
    <div class="row y-gap-20 justify-between items-end pb-20 lg:pb-40 md:pb-20">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600"><?php echo e(__("Manage Seats")); ?></h1>
            <div class="text-15 text-light-1"><?php echo e(__('AI-native airline operations. Seat inventory, fares, ancillaries, and distribution.')); ?></div>
        </div>
        <div class="col-auto">
            <a href="<?php echo e(route('flight.vendor.edit',['id'=>$currentFlight->id])); ?>" class="btn btn-info"><i class="fa fa-hand-o-right"></i> <?php echo e(__("Back to flight")); ?></a>
            <a href="<?php echo e(route("flight.vendor.seat.create",['flight_id'=>$currentFlight->id])); ?>" class="btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> <?php echo e(__("Add Seat")); ?></a>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="row">
        <div class="col-md-4 mb40">
            <div class="panel">
                <div class="panel-title"><?php echo e(__("Add Flight Seat")); ?></div>
                <div class="panel-body">
                    <form action="<?php echo e(route('flight.vendor.seat.store',['flight_id'=>$currentFlight->id,'id'=>$row->id??-1])); ?>" method="post">
                        <?php echo csrf_field(); ?>
                        <div class="row">
                            <?php echo $__env->make('Flight::admin.flight.seat.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button class="button h-50 px-24 -dark-1 bg-blue-1 text-white" type="submit"><i class="fa fa-save mr-2"></i> <?php echo e(__('Save Changes')); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="filter-div d-flex justify-content-between mb-15">
                <div class="col-left">
                    <?php if(!empty($rows)): ?>
                        <form method="post" action="<?php echo e(route('flight.vendor.seat.bulkEdit',['flight_id'=>$currentFlight->id])); ?>" class="filter-form filter-form-left d-flex justify-content-start">
                            <?php echo e(csrf_field()); ?>

                            <select name="action" class="form-control ">
                                <option value=""><?php echo e(__(" Bulk Action ")); ?></option>
                                <option value="delete"><?php echo e(__(" Delete ")); ?></option>
                            </select>
                            <button data-confirm="<?php echo e(__("Do you want to delete?")); ?>" class="btn-info btn btn-sm dungdt-apply-form-btn ml-3" type="button"><?php echo e(__('Apply')); ?></button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="col-left">
                    <form method="get" action="<?php echo e(route('flight.vendor.seat.index',['flight_id'=>$currentFlight->id])); ?> " class="filter-form filter-form-right d-flex justify-content-end" role="search">
                        <input type="text" name="s" value="<?php echo e(Request()->s); ?>" class="form-control" placeholder="<?php echo e(__("Search by code")); ?>">
                        <button class="btn btn-info btn-sm ml-2" id="search-submit" type="submit"><?php echo e(__('Search')); ?></button>
                    </form>
                </div>
            </div>
            <div class="panel">
                <div class="panel-title"><?php echo e(__("All Flight seat")); ?></div>
                <div class="panel-body">
                    <form class="bc-form-item">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th width="60px"><input type="checkbox" class="check-all"></th>
                                <th><?php echo e(__("Flight id")); ?></th>
                                <th><?php echo e(__("Seat type")); ?></th>
                                <th ><?php echo e(__("Price")); ?></th>
                                <th ><?php echo e(__("Max passengers")); ?></th>
                                <th class="date"></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if(count($rows) > 0): ?>
                                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><input type="checkbox" class="check-item" name="ids[]" value="<?php echo e($row->id); ?>"></td>
                                        <td><a href="<?php echo e(route('flight.admin.edit',$row->flight)); ?>">#<?php echo e($row->flight->id); ?></a></td>
                                        <td><?php echo e($row->seatType->name); ?></td>
                                        <td><?php echo e(format_money($row->price)); ?></td>
                                        <td><?php echo e($row->max_passengers); ?></td>
                                        <td><a class="btn btn-primary btn-sm" href="<?php echo e(route('flight.vendor.seat.edit',['flight_id'=>$currentFlight->id,'id'=>$row->id])); ?>"><i class="fa fa-edit"></i> <?php echo e(__('Edit')); ?></a></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4"><?php echo e(__("No data")); ?></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                            <?php echo e($rows->appends(request()->query())->links()); ?>

                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Flight/Views/frontend/manageFlight/seat/index.blade.php ENDPATH**/ ?>