<?php if(!empty($translation->specs)): ?>
    <div class="col-12">
        <h5 class="text-16 fw-500"><?php echo e(__("About")); ?></h5>
        <div class="list-item">
            <?php $__currentLoopData = $translation->specs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <ul class="list-disc text-15 mt-10">
                    <li><?php echo e($item['title']); ?>: <?php echo e($item['content']); ?></li>
                </ul>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Boat/Views/frontend/layouts/details/specs.blade.php ENDPATH**/ ?>