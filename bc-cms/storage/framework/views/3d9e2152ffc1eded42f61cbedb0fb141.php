<?php
    $company = setting_item('site_title', 'Tsoka Travel');
    $logoId  = setting_item('logo_id');
    $logoUrl = $logoId ? get_file_url($logoId) : null;
    $banking = $row->banking_details ?? json_decode(setting_item('tourpay_banking_details', '{}'), true) ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($row->invoice_number); ?> — <?php echo e($company); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #1a1a1a; background: #f5f5f5; }
.pay-wrap { min-height: 100vh; padding: 40px 20px 60px; }
.pay-header {
    display: flex; align-items: center; justify-content: space-between;
    max-width: 780px; margin: 0 auto 32px;
}
.pay-logo img  { max-height: 36px; }
.pay-logo-text { font-size: 16px; font-weight: 700; color: #111; }
.pay-badge {
    display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px;
    border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; background: #f0f0f0; color: #888;
}
.pay-badge.paid     { background: #e8f5e9; color: #2e7d32; }
.pay-badge.accepted { background: #e8f5e9; color: #2e7d32; }
.pay-badge.sent     { background: #e8f4ff; color: #1a73e8; }

/* Invoice card */
.pay-card {
    max-width: 780px; margin: 0 auto 28px; background: #fff;
    border-radius: 10px; overflow: hidden; box-shadow: 0 2px 16px rgba(0,0,0,.08);
}
/* Use template1 styles embedded */
.inv-doc { padding: 40px 44px; }
.inv-hd { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid #c8a96e; }
.inv-logo img { max-height: 42px; }
.inv-logo-text { font-size: 20px; font-weight: 700; color: #1a1a1a; }
.inv-hd-right { text-align: right; }
.inv-type   { font-size: 26px; font-weight: 700; color: #c8a96e; text-transform: uppercase; }
.inv-number { font-size: 12px; color: #888; margin-top: 4px; }
.inv-meta { display: flex; gap: 32px; margin-bottom: 24px; }
.inv-meta-item {}
.inv-meta-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #c8a96e; margin-bottom: 3px; }
.inv-meta-value { font-size: 12px; color: #333; line-height: 1.5; }
.inv-title { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
.inv-desc  { font-size: 12px; color: #666; line-height: 1.5; margin-bottom: 20px; }
.inv-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.inv-table thead th { background: #c8a96e; color: #fff; padding: 8px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; text-align: left; }
.inv-table thead th:nth-child(2),.inv-table thead th:nth-child(3),.inv-table thead th:last-child { text-align: right; }
.inv-table tbody tr { border-bottom: 1px solid #f0ead8; }
.inv-table tbody td { padding: 9px 12px; font-size: 12px; }
.inv-table tbody td:nth-child(2),.inv-table tbody td:nth-child(3),.inv-table tbody td:last-child { text-align: right; }
.item-sub { font-size: 10px; color: #aaa; margin-top: 2px; }
.inv-totals { display: flex; justify-content: flex-end; margin-bottom: 24px; }
.inv-totals-inner { width: 260px; }
.inv-tot-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f0ead8; font-size: 12px; color: #666; }
.inv-tot-row span:last-child { font-weight: 600; color: #1a1a1a; }
.inv-tot-grand { background: #c8a96e; border-radius: 3px; padding: 8px 12px; display: flex; justify-content: space-between; margin-top: 6px; }
.inv-tot-grand span { color: #fff; font-weight: 700; }
.inv-tot-grand span:last-child { font-size: 16px; font-weight: 800; }
.inv-notes { font-size: 11px; color: #666; line-height: 1.6; margin-top: 8px; }

/* Payment section */
.pay-section {
    max-width: 780px; margin: 0 auto;
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
}
.pay-option {
    background: #fff; border-radius: 10px; padding: 28px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 2px solid transparent;
}
.pay-option-head { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.pay-option-icon { width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.pay-option-title { font-size: 15px; font-weight: 700; color: #0a0a0a; }
.pay-option-sub   { font-size: 11px; color: #888; margin-top: 1px; }
.pay-now-btn {
    display: block; width: 100%; padding: 13px; border-radius: 6px;
    font-size: 14px; font-weight: 700; text-align: center;
    background: #0a0a0a; color: #fff !important; text-decoration: none;
    border: none; cursor: pointer; margin-top: 16px; transition: opacity .15s;
}
.pay-now-btn:hover { opacity: .85; }
.pay-bank-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f5f5f5; font-size: 12px; }
.pay-bank-row .k { color: #888; }
.pay-bank-row .v { font-weight: 600; }
.pay-ref-note { margin-top: 12px; font-size: 11px; color: #888; line-height: 1.5; background: #fafafa; border-radius: 4px; padding: 10px 12px; }

@media (max-width: 600px) {
    .pay-section { grid-template-columns: 1fr; }
    .inv-meta { flex-wrap: wrap; }
}
</style>
</head>
<body>
<div class="pay-wrap">

    
    <div class="pay-header">
        <div class="pay-logo">
            <?php if($logoUrl): ?><img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($company); ?>">
            <?php else: ?><div class="pay-logo-text"><?php echo e($company); ?></div><?php endif; ?>
        </div>
        <div class="pay-badge <?php echo e($row->status); ?>"><?php echo e(ucfirst($row->status)); ?></div>
    </div>

    
    <div class="pay-card">
        <div class="inv-doc">
            <div class="inv-hd">
                <div class="inv-logo">
                    <?php if($logoUrl): ?><img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($company); ?>">
                    <?php else: ?><div class="inv-logo-text"><?php echo e($company); ?></div><?php endif; ?>
                </div>
                <div class="inv-hd-right">
                    <div class="inv-type"><?php echo e(strtoupper($row->type)); ?></div>
                    <div class="inv-number"><?php echo e($row->invoice_number); ?></div>
                </div>
            </div>
            <div class="inv-meta">
                <div class="inv-meta-item">
                    <div class="inv-meta-label"><?php echo e(__("Billed To")); ?></div>
                    <div class="inv-meta-value"><strong><?php echo e($row->client_name); ?></strong>
                    <?php if($row->client_email): ?><br><?php echo e($row->client_email); ?><?php endif; ?>
                    <?php if($row->client_phone): ?><br><?php echo e($row->client_phone); ?><?php endif; ?></div>
                </div>
                <div class="inv-meta-item">
                    <div class="inv-meta-label"><?php echo e(__("Date")); ?></div>
                    <div class="inv-meta-value"><?php echo e($row->issue_date ? $row->issue_date->format('d M Y') : $row->created_at->format('d M Y')); ?></div>
                </div>
                <?php if($row->due_date): ?>
                <div class="inv-meta-item">
                    <div class="inv-meta-label"><?php echo e(__("Due")); ?></div>
                    <div class="inv-meta-value"><?php echo e($row->due_date->format('d M Y')); ?></div>
                </div>
                <?php endif; ?>
                <div class="inv-meta-item">
                    <div class="inv-meta-label"><?php echo e(__("Amount Due")); ?></div>
                    <div class="inv-meta-value" style="font-size:16px;font-weight:800;color:#c8a96e;"><?php echo e($row->currency); ?> <?php echo e(number_format($row->total, 2)); ?></div>
                </div>
            </div>

            <?php if($row->title): ?><div class="inv-title"><?php echo e($row->title); ?></div><?php endif; ?>
            <?php if($row->description): ?><div class="inv-desc"><?php echo e($row->description); ?></div><?php endif; ?>

            <table class="inv-table">
                <thead><tr>
                    <th><?php echo e(__("Description")); ?></th>
                    <th><?php echo e(__("Qty")); ?></th>
                    <th><?php echo e(__("Unit")); ?></th>
                    <th><?php echo e(__("Total")); ?></th>
                </tr></thead>
                <tbody>
                <?php $__currentLoopData = $row->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($item->name); ?>

                        <?php if($item->description): ?><div class="item-sub"><?php echo e($item->description); ?></div><?php endif; ?>
                    </td>
                    <td><?php echo e($item->quantity); ?></td>
                    <td><?php echo e(number_format($item->unit_price, 2)); ?></td>
                    <td><?php echo e(number_format($item->total, 2)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>

            <div class="inv-totals">
                <div class="inv-totals-inner">
                    <div class="inv-tot-row"><span><?php echo e(__("Subtotal")); ?></span><span><?php echo e($row->currency); ?> <?php echo e(number_format($row->subtotal, 2)); ?></span></div>
                    <?php if($row->tax_rate > 0): ?>
                    <div class="inv-tot-row"><span><?php echo e(__("VAT")); ?> (<?php echo e($row->tax_rate); ?>%)</span><span><?php echo e($row->currency); ?> <?php echo e(number_format($row->tax_amount, 2)); ?></span></div>
                    <?php endif; ?>
                    <div class="inv-tot-grand">
                        <span><?php echo e(__("TOTAL DUE")); ?></span>
                        <span><?php echo e($row->currency); ?> <?php echo e(number_format($row->total, 2)); ?></span>
                    </div>
                </div>
            </div>

            <?php if($row->payment_terms): ?>
            <div class="inv-notes"><strong><?php echo e(__("Payment Terms:")); ?></strong> <?php echo e($row->payment_terms); ?></div>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if(!in_array($row->status, ['paid','cancelled'])): ?>
    <div class="pay-section">

        
        <div class="pay-option" style="border-color:#0a0a0a;">
            <div class="pay-option-head">
                <div class="pay-option-icon">💳</div>
                <div>
                    <div class="pay-option-title"><?php echo e(__("Pay Online")); ?></div>
                    <div class="pay-option-sub"><?php echo e(__("Instant card or gateway payment")); ?></div>
                </div>
            </div>
            <p style="font-size:12px;color:#888;line-height:1.6;"><?php echo e(__("Pay securely via credit/debit card. Your payment is processed immediately.")); ?></p>
            <a href="#" class="pay-now-btn" onclick="alert('<?php echo e(__("Online payment gateway not yet configured. Please use EFT below.")); ?>'); return false;">
                <?php echo e(__("Pay Now")); ?> — <?php echo e($row->currency); ?> <?php echo e(number_format($row->total, 2)); ?>

            </a>
        </div>

        
        <div class="pay-option">
            <div class="pay-option-head">
                <div class="pay-option-icon">🏦</div>
                <div>
                    <div class="pay-option-title"><?php echo e(__("Bank Transfer (EFT)")); ?></div>
                    <div class="pay-option-sub"><?php echo e(__("Pay directly to our bank account")); ?></div>
                </div>
            </div>
            <?php $hasBanking = !empty($banking['bank'] ?? $banking['account_name'] ?? null); ?>
            <?php if($hasBanking): ?>
                <?php $__currentLoopData = ['account_name'=>__('Account Name'),'bank'=>__('Bank'),'account_number'=>__('Account No'),'branch_code'=>__('Branch Code'),'swift'=>__('SWIFT')]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bk=>$bl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(!empty($banking[$bk])): ?>
                <div class="pay-bank-row"><span class="k"><?php echo e($bl); ?></span><span class="v"><?php echo e($banking[$bk]); ?></span></div>
                <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <div class="pay-ref-note">
                    <?php echo e(__("Please use")); ?> <strong><?php echo e($row->invoice_number); ?></strong> <?php echo e(__("as your payment reference. Once paid, the seller will confirm your payment.")); ?>

                </div>
            <?php else: ?>
                <p style="font-size:12px;color:#888;line-height:1.6;"><?php echo e(__("Banking details will be provided by the vendor. Please contact them directly.")); ?></p>
            <?php endif; ?>
        </div>

    </div>
    <?php else: ?>
    <div style="max-width:780px;margin:0 auto;text-align:center;padding:24px;background:#e8f5e9;border-radius:8px;color:#2e7d32;font-weight:700;">
        ✓ <?php echo e(__("This invoice has been paid. Thank you!")); ?>

    </div>
    <?php endif; ?>

</div>
</body>
</html>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/TourPay/Views/public/pay.blade.php ENDPATH**/ ?>