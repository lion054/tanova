<?php
if(!$user->hasPermission('hotel_create')) return;
?>
<?php if(!empty($services) and $services->total()): ?>
    <div class="bc-profile-list-services">

        <div class="list-item">
            <div class="row">
                <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-xl-4 col-lg-4 col-sm-6 mb-30">
                        <?php echo $__env->make('Hotel::frontend.layouts.search.loop-grid', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <div class="container">
            <?php if(!empty($view_all)): ?>
                <div class="review-pag-wrapper">
                    <div class="bc-pagination">
                        <?php echo e($services->appends(request()->query())->links()); ?>

                    </div>
                    <div class="review-pag-text text-center">
                        <?php echo e(__("Showing :from - :to of :total total",["from"=>$services->firstItem(),"to"=>$services->lastItem(),"total"=>$services->total()])); ?>

                    </div>
                </div>
            <?php else: ?>
                <div class="text-center mt30"><a class="button h-50 px-24 -dark-1 bg-blue-1 text-white d-inline-flex" href="<?php echo e(route('user.profile.services',['id'=>$user->user_name ?? $user->id,'type'=>'hotel'])); ?>"><?php echo e(__('View all (:total)',['total'=>$services->total()])); ?></a></div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Hotel/Views/frontend/profile/service.blade.php ENDPATH**/ ?>