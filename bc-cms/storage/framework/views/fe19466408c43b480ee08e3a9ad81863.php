<?php
    $author       = $row->author ?? null;
    $company      = $author?->business_name ?: ($author?->name ?: setting_item('site_title', 'Tsoka Travel'));
    $logoId       = $author?->avatar_id ?: setting_item('logo_id');
    $logoUrl      = $logoId ? get_file_url($logoId) : null;
    $banking      = $row->banking_details ?? [];
    $preview      = $preview ?? false;
    $sellerEmail   = $author?->email  ?: setting_item('admin_email', '');
    $sellerPhone   = $author?->phone  ?: setting_item('phone', '');
    $sellerAddress = $author?->address ?: setting_item('address', '');
    $sellerReg     = setting_item('company_reg', '');
    $sellerVat     = setting_item('company_vat', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; }
.doc { max-width: 720px; margin: 0 auto; }
/* Top colour bar */
.topbar { height: 6px; background: linear-gradient(90deg,#1a73e8,#0d47a1); }
.inner { padding: 40px 52px; }
.hd { display: table; width: 100%; margin-bottom: 32px; }
.hd-left  { display: table-cell; vertical-align: middle; }
.hd-right { display: table-cell; vertical-align: middle; text-align: right; }
.logo-img  { max-height: 42px; max-width: 150px; }
.logo-text { font-size: 18px; font-weight: 700; color: #1a73e8; }
.doc-type  { font-size: 26px; font-weight: 800; color: #1a1a1a; letter-spacing: -.02em; }
.doc-number{ font-size: 12px; color: #888; margin-top: 3px; }
.badge-status {
    display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em; background: #e8f4ff; color: #1a73e8; margin-top: 6px;
}
.meta { display: table; width: 100%; margin-bottom: 28px; background: #f8faff; border-radius: 6px; padding: 18px; }
.meta-col { display: table-cell; vertical-align: top; width: 33.33%; padding-right: 12px; }
.meta-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #1a73e8; margin-bottom: 4px; }
.meta-value { font-size: 12px; color: #333; line-height: 1.5; }
.proj { margin-bottom: 24px; }
.proj-title { font-size: 15px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
.proj-desc  { font-size: 12px; color: #666; line-height: 1.5; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.items-table thead th { padding: 9px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #1a73e8; text-align: left; border-bottom: 2px solid #1a73e8; }
.items-table thead th:last-child, .items-table thead th:nth-child(2), .items-table thead th:nth-child(3) { text-align: right; }
.items-table tbody tr { border-bottom: 1px solid #f0f4ff; }
.items-table tbody td { padding: 9px 12px; font-size: 12px; vertical-align: top; }
.items-table tbody td:nth-child(2), .items-table tbody td:nth-child(3), .items-table tbody td:last-child { text-align: right; }
.item-desc { font-size: 10px; color: #aaa; margin-top: 2px; }
.totals-wrap { display: table; width: 100%; margin-bottom: 28px; }
.totals-pad { display: table-cell; width: 55%; }
.totals-box { display: table-cell; width: 45%; vertical-align: top; }
.tr { display: table; width: 100%; padding: 6px 0; border-bottom: 1px solid #f0f4ff; }
.tl { display: table-cell; font-size: 12px; color: #666; }
.tv { display: table-cell; font-size: 12px; text-align: right; font-weight: 600; }
.tr-total { background: #1a73e8; border-radius: 4px; margin-top: 6px; }
.tr-total .tl { color: #fff; font-weight: 700; padding: 8px 10px; }
.tr-total .tv { color: #fff; font-weight: 800; font-size: 15px; padding: 8px 10px; }
.info-grid { display: table; width: 100%; margin-bottom: 24px; }
.info-col  { display: table-cell; vertical-align: top; width: 50%; padding-right: 20px; }
.info-col:last-child { padding-right: 0; }
.info-head { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #1a73e8; margin-bottom: 8px; }
.info-row  { display: table; width: 100%; margin-bottom: 3px; }
.info-k    { display: table-cell; font-size: 11px; color: #888; width: 50%; }
.info-v    { display: table-cell; font-size: 11px; color: #333; font-weight: 600; }
.notes-text { font-size: 11px; color: #555; line-height: 1.6; }
.footer { border-top: 2px solid #1a73e8; margin-top: 8px; padding-top: 12px; text-align: center; font-size: 10px; color: #bbb; }
</style>
</head>
<body>
<div class="doc">
<div class="topbar"></div>
<div class="inner">
    <div class="hd">
        <div class="hd-left">
            <?php if($logoUrl): ?><img src="<?php echo e($logoUrl); ?>" class="logo-img" alt="<?php echo e($company); ?>">
            <?php else: ?><div class="logo-text"><?php echo e($company); ?></div><?php endif; ?>
        </div>
        <div class="hd-right">
            <div class="doc-type"><?php echo e(ucfirst($row->type)); ?></div>
            <div class="doc-number"><?php echo e($row->invoice_number); ?></div>
            <div class="badge-status"><?php echo e(ucfirst($row->status)); ?></div>
        </div>
    </div>
    <div class="meta">
        <div class="meta-col">
            <div class="meta-label"><?php echo e(__("From")); ?></div>
            <div class="meta-value"><strong><?php echo e($company); ?></strong><br>
            <?php if($sellerEmail): ?><?php echo e($sellerEmail); ?><br><?php endif; ?>
            <?php if($sellerPhone): ?><?php echo e($sellerPhone); ?><br><?php endif; ?>
            <?php if($sellerAddress): ?><?php echo e($sellerAddress); ?><br><?php endif; ?>
            <?php if($sellerReg): ?><?php echo e(__("Reg")); ?>: <?php echo e($sellerReg); ?><br><?php endif; ?>
            <?php if($sellerVat): ?><?php echo e(__("VAT No")); ?>: <?php echo e($sellerVat); ?><?php endif; ?></div>
        </div>
        <div class="meta-col">
            <div class="meta-label"><?php echo e(__("Billed To")); ?></div>
            <div class="meta-value"><strong><?php echo e($row->client_name); ?></strong><br>
            <?php if($row->client_email): ?><?php echo e($row->client_email); ?><br><?php endif; ?>
            <?php if($row->client_phone): ?><?php echo e($row->client_phone); ?><br><?php endif; ?>
            <?php if($row->client_country): ?><?php echo e($row->client_country); ?><?php endif; ?></div>
        </div>
        <div class="meta-col">
            <div class="meta-label"><?php echo e(__("Issued")); ?></div>
            <div class="meta-value"><?php echo e($row->issue_date ? $row->issue_date->format('d M Y') : date('d M Y')); ?></div>
            <?php if($row->due_date): ?><div class="meta-label" style="margin-top:10px"><?php echo e(__("Due")); ?></div>
            <div class="meta-value"><?php echo e($row->due_date->format('d M Y')); ?></div><?php endif; ?>
        </div>
        <div class="meta-col">
            <div class="meta-label"><?php echo e(__("Currency")); ?></div>
            <div class="meta-value"><?php echo e($row->currency); ?></div>
            <?php if($row->isQuotation() && $row->valid_days): ?>
            <div class="meta-label" style="margin-top:10px"><?php echo e(__("Valid For")); ?></div>
            <div class="meta-value"><?php echo e($row->valid_days); ?> <?php echo e(__("days")); ?></div><?php endif; ?>
        </div>
    </div>
    <?php if($row->title || $row->description): ?>
    <div class="proj">
        <?php if($row->title): ?><div class="proj-title"><?php echo e($row->title); ?></div><?php endif; ?>
        <?php if($row->description): ?><div class="proj-desc"><?php echo e($row->description); ?></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <table class="items-table">
        <thead><tr>
            <th><?php echo e(__("Description")); ?></th><th><?php echo e(__("Qty")); ?></th><th><?php echo e(__("Unit")); ?></th><th><?php echo e(__("Total")); ?></th>
        </tr></thead>
        <tbody>
        <?php $__currentLoopData = $row->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($item->name); ?><?php if($item->description): ?><div class="item-desc"><?php echo e($item->description); ?></div><?php endif; ?></td>
            <td><?php echo e($item->quantity); ?></td>
            <td><?php echo e(number_format($item->unit_price,2)); ?></td>
            <td><?php echo e(number_format($item->total,2)); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
    <div class="totals-wrap">
        <div class="totals-pad"></div>
        <div class="totals-box">
            <?php if($row->tax_rate > 0): ?>
            <div class="tr"><div class="tl"><?php echo e(__("Excl. VAT")); ?></div><div class="tv"><?php echo e($row->currency); ?> <?php echo e(number_format($row->subtotal,2)); ?></div></div>
            <div class="tr"><div class="tl"><?php echo e(__("VAT")); ?> (<?php echo e($row->tax_rate); ?>% <?php echo e(__("incl.")); ?>)</div><div class="tv"><?php echo e($row->currency); ?> <?php echo e(number_format($row->tax_amount,2)); ?></div></div>
            <?php endif; ?>
            <div class="tr tr-total"><div class="tl"><?php echo e(__("TOTAL")); ?></div><div class="tv"><?php echo e($row->currency); ?> <?php echo e(number_format($row->total,2)); ?></div></div>
        </div>
    </div>
    <?php $hasBanking = !empty($banking['bank'] ?? $banking['account_name'] ?? null); ?>
    <?php if($row->notes || $hasBanking): ?>
    <div class="info-grid">
        <?php if($row->notes || $row->payment_terms): ?>
        <div class="info-col">
            <?php if($row->notes): ?><div class="info-head"><?php echo e(__("Notes")); ?></div><div class="notes-text"><?php echo e($row->notes); ?></div><?php endif; ?>
            <?php if($row->payment_terms): ?><div class="info-head" style="margin-top:12px"><?php echo e(__("Payment Terms")); ?></div><div class="notes-text"><?php echo e($row->payment_terms); ?></div><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if($hasBanking): ?>
        <div class="info-col">
            <div class="info-head"><?php echo e(__("Banking Details")); ?></div>
            <?php $__currentLoopData = ['account_name'=>__('Account Name'),'bank'=>__('Bank'),'account_number'=>__('Account No'),'branch_code'=>__('Branch Code'),'swift'=>__('SWIFT')]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bk=>$bl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(!empty($banking[$bk])): ?><div class="info-row"><div class="info-k"><?php echo e($bl); ?></div><div class="info-v"><?php echo e($banking[$bk]); ?></div></div><?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="footer"><?php echo e($company); ?> &nbsp;·&nbsp; <?php echo e(__("Thank you for your business.")); ?></div>
</div>
</div>
</body>
</html>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/TourPay/Views/pdf/template2.blade.php ENDPATH**/ ?>