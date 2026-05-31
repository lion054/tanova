<div class="item-list">
    <div class="row">
        <div class="col-md-3">
            <div class="thumb-image">
                <a href="#" target="_blank">
                    <?php if($row->airline->image_url): ?>
                        <img src="<?php echo e($row->airline->image_url); ?>" class="img-responsive" alt="">
                    <?php endif; ?>
                </a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="item-title">
                <a href="<?php echo e($row->getDetailUrl()); ?>" target="_blank">
                    <?php echo e($row->title); ?>

                </a>
            </div>
            <div class="location">
                <?php if(!empty($row->airportFrom->name)): ?>
                    <i class="fa fa-plane" aria-hidden="true"></i>
                    <?php echo e(__("Airport From")); ?>: <?php echo e($row->airportFrom->name ?? ''); ?>

                    <span class="">(<?php echo e(display_datetime($row->departure_time)); ?>)</span>
                <?php endif; ?>
            </div>
            <div class="location">
                <?php if(!empty($row->airportTo->name)): ?>
                    <i class="fa fa-plane fa-rotate-90" aria-hidden="true"></i>
                    <?php echo e(__("Airport To")); ?>: <?php echo e($row->airportTo->name ?? ''); ?>

                    <span class="">(<?php echo e(display_datetime($row->arrival_time)); ?>)</span>
                <?php endif; ?>
            </div>
            <div class="location">
                <i class="fa fa-clock-o" aria-hidden="true"></i>
                <?php echo e(__("Duration")); ?>: <span class=""><?php echo e($row->duration??0); ?></span>
            </div>
            <div class="location">
                <i class="icofont-ui-settings"></i>
                <?php echo e(__("Status")); ?>: <span class="badge badge-<?php echo e($row->status); ?>"><?php echo e($row->status_text); ?></span>
            </div>
            <div class="location">
                <i class="icofont-wall-clock"></i>
                <?php echo e(__("Last Updated")); ?>: <?php echo e(display_datetime($row->updated_at ?? $row->created_at)); ?>

            </div>
            <div class="control-action">
                <?php if(!empty($recovery)): ?>
                    <a href="<?php echo e(route("flight.vendor.restore",[$row->id])); ?>" class="btn btn-recovery btn-primary" data-confirm="<?php echo e(__('"Do you want to recovery?"')); ?>"><?php echo e(__("Recovery")); ?></a>
                    <?php if(Auth::user()->hasPermission('flight_delete')): ?>
                        <a href="<?php echo e(route("flight.vendor.delete",['id'=>$row->id,'permanently_delete'=>1])); ?>" class="btn btn-danger" data-confirm="<?php echo e(__("Do you want to permanently delete?")); ?>"><?php echo e(__("Del")); ?></a>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if(Auth::user()->hasPermission('flight_update')): ?>
                        <a href="<?php echo e(route("flight.vendor.edit",[$row->id])); ?>" class="btn btn-warning"><?php echo e(__("Edit")); ?></a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('flight_delete')): ?>
                        <a href="<?php echo e(route("flight.vendor.delete",[$row->id])); ?>" class="btn btn-danger" data-confirm="<?php echo e(__("Do you want to delete?")); ?>"><?php echo e(__("Del")); ?></a>
                    <?php endif; ?>
                    <?php if($row->status == 'publish'): ?>
                        <a href="<?php echo e(route("flight.vendor.bulk_edit",[$row->id,'action' => "make-hide"])); ?>" class="btn btn-secondary"><?php echo e(__("Make hide")); ?></a>
                    <?php endif; ?>
                    <?php if($row->status == 'draft'): ?>
                        <a href="<?php echo e(route("flight.vendor.bulk_edit",[$row->id,'action' => "make-publish"])); ?>" class="btn btn-success"><?php echo e(__("Make publish")); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Flight/Views/frontend/manageFlight/loop-list.blade.php ENDPATH**/ ?>