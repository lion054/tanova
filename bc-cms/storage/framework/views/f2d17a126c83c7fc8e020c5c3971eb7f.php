
<div class="">
    <div class="">
        <h2 class="title-bar no-border-bottom"><?php echo e(__('Visa Booking Detail')); ?></h2>
    </div>

    <div class="booking-history-manager">
        <div class="tabbable">
            <ul class="nav nav-tabs ht-nav-tabs">
                <?php $__currentLoopData = $booking->passengers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index=>$passenger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class=" <?php if($passenger->id == $passengerId): ?> active <?php endif; ?>">
                    <a href="#passenger_<?php echo e($passenger->id); ?>" wire:click="setPassenger(<?php echo e($passenger->id); ?>)"><?php echo e(__('Applicant :index',['index'=>$index + 1])); ?></a>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <div class="tab-content">
                <?php if($this->currentPassenger): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('visa::applicant-form-view', ['passenger' => $this->currentPassenger]);

$__html = app('livewire')->mount($__name, $__params, $this->currentPassenger->id, $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php endif; ?>
            </div>
        </div>
        <?php if(!count($booking->passengers)): ?>
            <div class="alert alert-danger"><?php echo e(__('No applications found')); ?></div>
        <?php endif; ?>
    </div>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Visa/Views/frontend/user/booking-detail.blade.php ENDPATH**/ ?>