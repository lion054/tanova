<?php $__env->startSection('title', 'Page Not Found'); ?>
<?php $__env->startSection('code', '404'); ?>
<?php $__env->startSection('code-color', '#6366f1'); ?>
<?php $__env->startSection('icon-bg', '#eef2ff'); ?>
<?php $__env->startSection('badge-bg', '#eef2ff'); ?>
<?php $__env->startSection('badge-color', '#4f46e5'); ?>
<?php $__env->startSection('badge-label', 'Not Found'); ?>

<?php $__env->startSection('icon'); ?>
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#6366f1" stroke-width="1.5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0012.016 15a4.486 4.486 0 00-3.198 1.318M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/>
</svg>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('title', "Page Not Found"); ?>
<?php $__env->startSection('message', "We searched everywhere but couldn't find the page you're looking for. It may have been moved, deleted, or never existed."); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(url('/')); ?>" class="err-btn" style="background:#6366f1;color:#fff;box-shadow:0 2px 8px rgba(99,102,241,.3)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/></svg>
        Go Home
    </a>
    <a href="javascript:history.back()" class="err-btn err-btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Go Back
    </a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('errors.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/errors/404.blade.php ENDPATH**/ ?>