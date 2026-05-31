<?php
    $inv     = $invoice;
    $author  = $inv->author ?? null;
    $company = $author?->business_name ?: ($author?->name ?: setting_item('site_title', 'Tsoka Travel'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
body { font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #1a1a1a; background: #f5f5f5; margin: 0; padding: 0; }
.wrap { max-width: 560px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.topbar { height: 4px; background: #c8a96e; }
.body   { padding: 36px 40px; }
.greeting { font-size: 18px; font-weight: 700; margin-bottom: 16px; }
.para     { font-size: 13px; color: #555; line-height: 1.7; margin-bottom: 16px; }
.info-box { background: #fdf8f0; border: 1px solid #e8d5b5; border-radius: 6px; padding: 20px; margin: 24px 0; }
.info-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; border-bottom: 1px solid #f0ead8; }
.info-row:last-child { border-bottom: none; }
.info-row .k { color: #888; }
.info-row .v { font-weight: 600; }
.cta-btn {
    display: block; width: 100%; padding: 14px; border-radius: 5px;
    text-align: center; background: #0a0a0a; color: #fff !important;
    font-size: 14px; font-weight: 700; text-decoration: none; margin: 24px 0 8px;
}
.footer { background: #fafafa; border-top: 1px solid #f0f0f0; padding: 20px 40px; text-align: center; font-size: 11px; color: #bbb; }
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar"></div>
    <div class="body">
        <div class="greeting">
            <?php echo e(__("Hi :name,", ['name' => $inv->client_name])); ?>

        </div>
        <p class="para">
            <?php if($inv->type === 'quotation'): ?>
                <?php echo e(__("Please find your quotation from :company attached to this email as a PDF.", ['company' => $company])); ?>

            <?php else: ?>
                <?php echo e(__("Please find your invoice from :company attached to this email as a PDF.", ['company' => $company])); ?>

            <?php endif; ?>
        </p>

        <div class="info-box">
            <div class="info-row"><span class="k"><?php echo e(__("Reference")); ?></span><span class="v"><?php echo e($inv->invoice_number); ?></span></div>
            <?php if($inv->title): ?>
            <div class="info-row"><span class="k"><?php echo e(__("Description")); ?></span><span class="v"><?php echo e($inv->title); ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="k"><?php echo e(__("Date")); ?></span><span class="v"><?php echo e($inv->issue_date ? $inv->issue_date->format('d M Y') : date('d M Y')); ?></span></div>
            <?php if($inv->due_date): ?>
            <div class="info-row"><span class="k"><?php echo e(__("Due Date")); ?></span><span class="v"><?php echo e($inv->due_date->format('d M Y')); ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="k"><?php echo e(__("Amount")); ?></span><span class="v"><?php echo e($inv->currency); ?> <?php echo e(number_format($inv->total, 2)); ?></span></div>
        </div>

        <a href="<?php echo e(route('tourpay.pay', $inv->pay_token)); ?>" class="cta-btn">
            <?php echo e(__("View & Pay Online")); ?>

        </a>

        <p class="para" style="font-size:12px;">
            <?php echo e(__("If you have any questions, please don't hesitate to get in touch.")); ?>

        </p>
    </div>
    <div class="footer">
        <?php echo e($company); ?> &nbsp;·&nbsp; <?php echo e(__("This email was sent by :c", ['c' => $company])); ?>

    </div>
</div>
</body>
</html>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/TourPay/Views/emails/invoice.blade.php ENDPATH**/ ?>