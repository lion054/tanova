
<?php $__env->startPush('css'); ?>
    <style type="text/css">
        html,
        body {
            background: #f0f0f0;
        }

        .bc_topbar,
        .bc_header,
        .bc_footer {
            display: none;
        }

        .invoice-amount {
            margin-top: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 20px;
            display: inline-block;
            text-align: center;
        }

        .table-service-head {
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .table-service-head th {
            padding: 5px 15px;
        }

        #invoice-print-zone {
            margin: 60px auto 40px auto;
            max-width: 400px;
        }

        .ticket-wrap {
            background: white;
            padding: 15px;
            border-radius: 7px;
        }

        .invoice-company-info {
            margin-top: 15px;
        }

        .invoice-company-info p {
            margin-bottom: 2px;
            font-weight: normal;
        }

        .servive-name {
            font-size: 18px;
            font-weight: bold;
            color: #5191fa;

        }

        .service-location {

            font-style: italic;
        }

        .service-info {
            margin-bottom: 14px;
        }

        .ticket-body {

            border-top: dashed 1px #dfdfdf;
            padding-top: 20px;
        }

        .ticket-body td {
            padding-bottom: 20px;
            vertical-align: top;
        }

        .ticket-body .label {
            color: #868686;
            margin-bottom: 5px;
        }

        .ticket-body .val {
            font-weight: 600;
            font-size: 15px;
        }

        .list-ticket {
            list-style: none;
        }

        .ticket-footer {
            margin-top: 20px;
            border-top: dashed 1px #dfdfdf;
            padding-top: 20px;
        }

        @media (max-width: 400px) {
            #invoice-print-zone {
                margin-left: 15px;
                margin-right: 15px;
            }
        }
    </style>
    <link href="<?php echo e(asset('module/user/css/user.css')); ?>" rel="stylesheet">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <?php echo $__env->yieldContent('ticket_content'); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset('module/user/js/user.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('Layout::empty', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Booking/Views/frontend/user/ticket/layout.blade.php ENDPATH**/ ?>