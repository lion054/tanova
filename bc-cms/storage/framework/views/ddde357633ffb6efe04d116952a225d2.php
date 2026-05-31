<div class="item-list">
    <div class="row">
        <div class="col-md-3">
            <div class="thumb-image">
                <a href="<?php echo e($row->getDetailUrl()); ?>" target="_blank">
                    <?php if($row->image_url): ?>
                        <img src="<?php echo e($row->image_url); ?>" class="img-responsive" alt="">
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
                <i class="icofont-ui-settings"></i>
                <?php echo e(__("Number")); ?>: <?php echo e($row->number); ?>

            </div>
            <div class="location">
                <i class="icofont-money"></i>
                <?php echo e(__("Price")); ?>: <span class="price"><?php echo e($row->display_price); ?></span>
            </div>
            <div class="location">
                <i class="icofont-ui-settings"></i>
                <?php echo e(__("Status")); ?>: <span class="badge badge-<?php echo e($row->status); ?>"><?php echo e($row->status); ?></span>
            </div>
            <div class="location">
                <i class="icofont-wall-clock"></i>
                <?php echo e(__("Last Updated")); ?>: <?php echo e(display_datetime($row->updated_at ?? $row->created_at)); ?>

            </div>
            <div class="control-action">
                <?php if(Auth::user()->hasPermission('hotel_update')): ?>
                    <a href="<?php echo e(route('hotel.vendor.room.edit',['hotel_id'=>$hotel->id,'id'=>$row->id])); ?>" class="btn btn-warning"><?php echo e(__("Edit")); ?></a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('hotel_update')): ?>
                    <a href="<?php echo e(route('hotel.vendor.room.delete',['hotel_id'=>$hotel->id,'id'=>$row->id])); ?>" class="btn btn-danger" data-confirm="<?php echo e(__("Do you want to delete?")); ?>"><?php echo e(__("Del")); ?></a>
                <?php endif; ?>
                <?php if($row->status == 'publish'): ?>
                    <a href="<?php echo e(route('hotel.vendor.room.bulk_edit',['hotel_id'=>$hotel->id,'id'=>$row->id,'action' => "make-hide"])); ?>" class="btn btn-secondary"><?php echo e(__("Make hide")); ?></a>
                <?php endif; ?>
                <?php if($row->status == 'draft'): ?>
                    <a href="<?php echo e(route('hotel.vendor.room.bulk_edit',['hotel_id'=>$hotel->id,'id'=>$row->id,'action' => "make-publish"])); ?>" class="btn btn-success"><?php echo e(__("Make publish")); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Hotel/Views/frontend/vendorHotel/room/loop-list.blade.php ENDPATH**/ ?>