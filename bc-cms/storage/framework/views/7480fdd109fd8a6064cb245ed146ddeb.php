<?php
$passengers = $booking->passengers;
if(!count($passengers)) return;
?>
<div class="accordion gateways-table my-3" id="passengers_info">
    <a target="_blank" href="<?php echo e(route('visa.user.booking-detail', ['code' => $booking->code])); ?>" class="btn btn-primary btn-sm"><?php echo e(__('View applications details')); ?> <i class="fa fa-arrow-right"></i></a>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Visa/Views/frontend/booking/passengers-info.blade.php ENDPATH**/ ?>