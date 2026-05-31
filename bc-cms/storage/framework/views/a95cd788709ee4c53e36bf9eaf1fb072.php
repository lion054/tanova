
<div class="bc_detail_tour">
    <?php echo $__env->make('Layout::parts.bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="container my-3">
        <div class="d-flex justify-content-between">
            <h3><?php echo e(__("Applicant :index", ['index' => ($guestIndex + 1) . "/" . $total_guests])); ?></h3>
            <div class="d-flex b-gap-2">
                <?php if($guestIndex > 0): ?>
                <button class="btn btn-primary btn-sm" wire:click="prev">
                    <i class="fa fa-arrow-left"></i>
                    <?php echo e(__('Prev Applicant')); ?>

                </button>
                <?php endif; ?>
                <?php if($guestIndex < $maxGuestIndex): ?>
                <button class="btn btn-primary btn-sm" wire:click="next">
                    <?php echo e(__('Next Applicant')); ?>

                    <i class="fa fa-arrow-right"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3">
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('form::simple-form',['options'=>['provider'=>'visa_application_form'], 'data'=>$this->passengerData]);

$__html = app('livewire')->mount($__name, $__params, $guestIndex, $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        </div>
    </div>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/applications.blade.php ENDPATH**/ ?>