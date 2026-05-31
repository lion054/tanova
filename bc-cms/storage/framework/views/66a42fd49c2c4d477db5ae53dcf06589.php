<?php $__env->startSection('title', 'Session Expired'); ?>
<?php $__env->startSection('code', '419'); ?>
<?php $__env->startSection('code-color', '#0ea5e9'); ?>
<?php $__env->startSection('icon-bg', '#f0f9ff'); ?>
<?php $__env->startSection('badge-bg', '#f0f9ff'); ?>
<?php $__env->startSection('badge-color', '#0284c7'); ?>
<?php $__env->startSection('badge-label', 'Session Expired'); ?>

<?php $__env->startSection('icon'); ?>
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#0ea5e9" stroke-width="1.5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
</svg>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('title', 'Session Expired'); ?>
<?php $__env->startSection('message', 'Your session has timed out for security. This usually happens after a period of inactivity. Simply go back and try again — your work may still be there.'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="javascript:history.back()" class="err-btn" style="background:#0ea5e9;color:#fff;box-shadow:0 2px 8px rgba(14,165,233,.3)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Go Back & Retry
    </a>
    <a href="<?php echo e(url('/')); ?>" class="err-btn err-btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/></svg>
        Go Home
    </a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('errors.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/errors/419.blade.php ENDPATH**/ ?>