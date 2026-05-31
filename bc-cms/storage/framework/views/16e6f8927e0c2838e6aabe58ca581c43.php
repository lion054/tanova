<tr>
    <td class="booking-history-type">
        <i class="icofont-compass-alt"></i>
        <small><?php echo e(__('Tanova Trip')); ?></small>
    </td>
    <td>
        <?php if($service = $booking->service): ?>
            <strong><?php echo e($service->title); ?></strong>
            <?php if($service->destination): ?>
                <small><div><?php echo e($service->destination); ?></div></small>
            <?php endif; ?>
            <?php if($service->start_date && $service->end_date): ?>
                <small>
                    <div><?php echo e(display_date($service->start_date)); ?> &mdash; <?php echo e(display_date($service->end_date)); ?></div>
                </small>
            <?php endif; ?>
            <?php
                $pkgIndex  = max(0, ($service->booked_package ?? 1) - 1);
                $packages  = $service->itinerary ?? [];
                $chosen    = $packages[$pkgIndex] ?? ($packages[0] ?? null);
                $hotel     = $chosen['hotel'] ?? null;
            ?>
            <small>
                <div><?php echo e($service->guests); ?> <?php echo e(__('guest(s)')); ?>

                    <?php if($hotel): ?> &middot; <?php echo e($hotel['name'] ?? ''); ?> (<?php echo e($hotel['type'] ?? ''); ?>)<?php endif; ?>
                </div>
                <div><?php echo e(__("Customer Info")); ?></div>
                <div><?php echo e(__("First Name")); ?>: <?php echo e($booking->first_name); ?></div>
                <div><?php echo e(__("Last Name")); ?>: <?php echo e($booking->last_name); ?></div>
            </small>
        <?php else: ?>
            <?php echo e(__("[Deleted]")); ?>

        <?php endif; ?>
    </td>
    <td class="a-hidden"><?php echo e(display_date($booking->created_at)); ?></td>
    <td class="a-hidden">
        <?php if($service && $service->start_date): ?>
            <?php echo e(display_date($service->start_date)); ?>

            <?php if($service->end_date): ?>
                &mdash; <?php echo e(display_date($service->end_date)); ?>

            <?php endif; ?>
        <?php else: ?>
            &mdash;
        <?php endif; ?>
    </td>
    <td>
        <div><?php echo e(__("Total")); ?>: <?php echo e(format_money_main($booking->total)); ?></div>
        <div><?php echo e(__("Paid")); ?>: <?php echo e(format_money_main($booking->paid)); ?></div>
        <div><?php echo e(__("Remain")); ?>: <?php echo e(format_money($booking->total - $booking->paid)); ?></div>
    </td>
    <td><?php echo e(format_money($booking->commission)); ?></td>
    <td class="<?php echo e($booking->status); ?> a-hidden"><?php echo e($booking->statusName); ?></td>
    <td width="2%">
        
        <?php if($service): ?>
        <a href="<?php echo e(route('admin.tanova.show', $service->id)); ?>"
           class="btn btn-xs btn-primary btn-info-booking"
           target="_blank">
            <i class="fa fa-info-circle"></i><?php echo e(__("Details")); ?>

        </a>
        <?php endif; ?>

        
        <?php
            $tInvoice = $service
                ? \Modules\TourPay\Models\Invoice::where('tanova_trip_id', $service->id)
                    ->where('author_id', auth()->id())
                    ->first()
                : null;
        ?>
        <?php if($tInvoice): ?>
            <a href="<?php echo e(route('tourpay.vendor.view', $tInvoice->id)); ?>"
               class="btn btn-xs btn-success btn-info-booking open-new-window mt-1"
               target="_blank">
                <i class="fa fa-file-text-o"></i><?php echo e(__("Invoice")); ?>

            </a>
        <?php elseif($service): ?>
            <form method="POST" action="<?php echo e(route('admin.tanova.createInvoice', $service->id)); ?>" style="display:inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="package" value="<?php echo e($service->booked_package ?? 1); ?>">
                <button type="submit" class="btn btn-xs btn-warning mt-1">
                    <i class="fa fa-plus"></i><?php echo e(__("Create Invoice")); ?>

                </button>
            </form>
        <?php endif; ?>
    </td>
</tr>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Tanova/Views/frontend/bookingReport/loop.blade.php ENDPATH**/ ?>