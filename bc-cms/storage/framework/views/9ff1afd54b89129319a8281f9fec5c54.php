
<?php $__env->startSection('ticket_content'); ?>
    <div id="invoice-print-zone">
        <div class="ticket-content">
            <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($k): ?>
                    <div style="page-break-after: always;">&nbsp</div>
                    <div style="page-break-before: always;">&nbsp;</div>
                <?php endif; ?>
                <div class="mb-3">
                    <?php echo $__env->make('Booking::frontend.user.ticket.loop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Booking::frontend.user.ticket.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Booking/Views/frontend/user/ticket/tickets.blade.php ENDPATH**/ ?>