<div class="item-list">
    <div class="row">
        <div class="col-md-3">
            <div class="thumb-image">
                <a href="<?php echo e($row->getDetailUrl()); ?>" target="_blank">
                    <?php if($row->image_url): ?>
                        <img src="<?php echo e($row->image_url); ?>" class="img-responsive" alt="">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height:120px">
                            <i class="icofont-id" style="font-size:48px;color:#ccc"></i>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="item-title">
                <a href="<?php echo e($row->getDetailUrl()); ?>" target="_blank"><?php echo e($row->title); ?></a>
            </div>
            <div class="location">
                <i class="icofont-globe"></i>
                <?php echo e(__('Country')); ?>: <?php echo e($row->country ?? $row->to_country); ?>

            </div>
            <div class="location">
                <i class="icofont-money"></i>
                <?php echo e(__('Price')); ?>: <span class="sale-price"><?php echo e(format_money($row->price)); ?></span>
                <?php if($row->original_price > $row->price): ?>
                    <span class="price" style="text-decoration:line-through"><?php echo e(format_money($row->original_price)); ?></span>
                <?php endif; ?>
            </div>
            <div class="location">
                <i class="icofont-clock-time"></i>
                <?php echo e(__('Processing')); ?>: <?php echo e($row->processing_days); ?> <?php echo e(__('day(s)')); ?>

            </div>
            <div class="location">
                <i class="icofont-ui-settings"></i>
                <?php echo e(__('Status')); ?>: <span class="badge badge-<?php echo e($row->status); ?>"><?php echo e($row->status_text); ?></span>
            </div>
            <div class="location">
                <i class="icofont-wall-clock"></i>
                <?php echo e(__('Last Updated')); ?>: <?php echo e(display_datetime($row->updated_at ?? $row->created_at)); ?>

            </div>
            <div class="control-action">
                <?php if(!empty($recovery)): ?>
                    <a href="<?php echo e(route('visa.vendor.restore', [$row->id])); ?>" class="btn btn-primary" data-confirm="<?php echo e(__('"Do you want to restore?"')); ?>"><?php echo e(__('Restore')); ?></a>
                    <?php if(Auth::user()->hasPermission('visa_delete')): ?>
                        <a href="<?php echo e(route('visa.vendor.delete', ['id' => $row->id, 'permanently_delete' => 1])); ?>" class="btn btn-danger" data-confirm="<?php echo e(__('"Permanently delete? This cannot be undone."')); ?>"><?php echo e(__('Delete Forever')); ?></a>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if(Auth::user()->hasPermission('visa_update')): ?>
                        <a href="<?php echo e(route('visa.vendor.edit', [$row->id])); ?>" class="btn btn-warning"><?php echo e(__('Edit')); ?></a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('visa_delete')): ?>
                        <a href="<?php echo e(route('visa.vendor.delete', [$row->id])); ?>" class="btn btn-danger" data-confirm="<?php echo e(__('"Do you want to delete?"')); ?>"><?php echo e(__('Delete')); ?></a>
                    <?php endif; ?>
                    <?php if($row->status == 'publish'): ?>
                        <a href="<?php echo e(route('visa.vendor.bulk_edit', [$row->id, 'action' => 'make-hide'])); ?>" class="btn btn-secondary"><?php echo e(__('Make Hidden')); ?></a>
                    <?php endif; ?>
                    <?php if($row->status == 'draft'): ?>
                        <a href="<?php echo e(route('visa.vendor.bulk_edit', [$row->id, 'action' => 'make-publish'])); ?>" class="btn btn-success"><?php echo e(__('Publish')); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/manageVisa/loop-list.blade.php ENDPATH**/ ?>