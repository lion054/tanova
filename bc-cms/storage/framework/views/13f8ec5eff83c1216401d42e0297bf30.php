<?php $__env->startSection('content'); ?>
<style>
/* ── Reset helpers ─────────────────────────────── */
.tp * { box-sizing: border-box; }

/* ── Page header ───────────────────────────────── */
.tp-header {
    display: flex; align-items: flex-end; justify-content: space-between;
    gap: 16px; margin-bottom: 24px; flex-wrap: wrap;
}
.tp-header-left h1 {
    font-size: 24px; font-weight: 800; color: #0a0a0a;
    margin: 0 0 3px; letter-spacing: -.03em;
}
.tp-header-left p { font-size: 13px; color: #999; margin: 0; }
.tp-header-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

/* ── Buttons ───────────────────────────────────── */
.tp-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 6px; font-size: 13px; font-weight: 600;
    text-decoration: none !important; white-space: nowrap; border: none;
    cursor: pointer; transition: all .15s; letter-spacing: -.01em;
}
.tp-btn-primary { background: #0a0a0a; color: #fff !important; }
.tp-btn-primary:hover { background: #222; }
.tp-btn-ghost { background: #fff; color: #0a0a0a !important; border: 1.5px solid #e0e0e0; }
.tp-btn-ghost:hover { border-color: #0a0a0a; }

/* ── Stats strip ───────────────────────────────── */
.tp-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 12px; margin-bottom: 20px;
}
.tp-stat {
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    padding: 18px 20px; position: relative; overflow: hidden;
    display: flex; flex-direction: column;
}
.tp-stat-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .08em; color: #aaa; margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
}
.tp-stat-label i { font-size: 12px; }
.tp-stat-amount {
    font-size: 22px; font-weight: 800; color: #0a0a0a;
    letter-spacing: -.03em; line-height: 1;
}
.tp-stat-amount.green { color: #16a34a; }
.tp-stat-amount.amber { color: #d97706; }
.tp-stat-sub { font-size: 11px; color: #bbb; margin-top: auto; padding-top: 8px; }
.tp-stat::after {
    content: ''; position: absolute; right: -14px; bottom: -14px;
    width: 56px; height: 56px; border-radius: 50%;
    background: #e5e7eb; opacity: .5;
}
.tp-stat-outstanding::after { background: #bfdbfe; }
.tp-stat-paid::after        { background: #bbf7d0; }

/* Multi-currency rows inside stat card */
.tp-cur-rows { display: flex; flex-direction: column; gap: 5px; flex: 1; }
.tp-cur-row  { display: flex; align-items: baseline; gap: 6px; }
.tp-cur-code { font-size: 10px; font-weight: 700; letter-spacing: .06em; min-width: 32px; color: #aaa; }
.tp-cur-val  { font-size: 18px; font-weight: 800; letter-spacing: -.03em; line-height: 1; }
.tp-cur-val.blue  { color: #2563eb; }
.tp-cur-val.green { color: #16a34a; }
.tp-cur-empty { font-size: 20px; font-weight: 800; color: #d0d0d0; letter-spacing: -.03em; }

/* ── Filter bar ────────────────────────────────── */
.tp-filters {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 16px; flex-wrap: wrap;
}
.tp-filter-field {
    display: flex; align-items: center; gap: 6px;
    background: #fff; border: 1.5px solid #e4e4e4; border-radius: 8px;
    padding: 0 12px; height: 38px; transition: border-color .15s;
}
.tp-filter-field:focus-within { border-color: #0a0a0a; }
.tp-filter-field i { font-size: 13px; color: #ccc; flex-shrink: 0; }
.tp-filter-field input,
.tp-filter-field select {
    border: none !important; outline: none !important; background: transparent !important;
    font-size: 13px; color: #333; padding: 0 !important; height: 100% !important;
    box-shadow: none !important; appearance: none; -webkit-appearance: none;
}
.tp-filter-field input::placeholder { color: #bbb; }
.tp-filter-field.tf-search { flex: 1; min-width: 200px; }
.tp-filter-field.tf-select { min-width: 130px; position: relative; }
.tp-filter-field.tf-select::after {
    content: ''; width: 0; height: 0; flex-shrink: 0;
    border-left: 4px solid transparent; border-right: 4px solid transparent;
    border-top: 4px solid #ccc; margin-left: 4px;
}
.tp-filter-chips { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.tp-filter-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 100px; background: #f0f0f0;
    font-size: 11px; font-weight: 600; color: #555; text-decoration: none;
    transition: background .1s;
}
.tp-filter-chip:hover { background: #e0e0e0; color: #0a0a0a; }

/* ── Table card ────────────────────────────────── */
.tp-card {
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    overflow: hidden;
}
.tp-table { width: 100%; border-collapse: collapse; }
.tp-table thead { border-bottom: 1px solid #f0f0f0; }
.tp-table thead th {
    padding: 11px 16px; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em; color: #bbb;
    text-align: left; white-space: nowrap; background: #fafafa;
}
.tp-table thead th:last-child { width: 44px; }
.tp-table tbody tr {
    border-bottom: 1px solid #f7f7f7; transition: background .08s;
    position: relative;
}
.tp-table tbody tr:last-child { border-bottom: none; }
.tp-table tbody tr:hover { background: #fafafa; }
.tp-table tbody tr:hover .tp-row-menu-btn { opacity: 1; }
.tp-table td { padding: 14px 16px; vertical-align: middle; }

/* Client cell */
.tp-client-cell { display: flex; align-items: center; gap: 11px; }
.tp-avatar {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff; letter-spacing: -.01em;
}
.tp-client-name { font-size: 13px; font-weight: 600; color: #0a0a0a; line-height: 1.2; }
.tp-client-email { font-size: 11px; color: #bbb; margin-top: 1px; }

/* Reference */
.tp-ref { font-size: 13px; font-weight: 700; color: #0a0a0a; letter-spacing: -.01em; }
.tp-ref-date { font-size: 11px; color: #ccc; margin-top: 2px; }

/* Amount */
.tp-amount { font-size: 14px; font-weight: 700; color: #0a0a0a; letter-spacing: -.02em; }
.tp-currency { font-size: 10px; font-weight: 600; color: #aaa; margin-right: 2px; vertical-align: .1em; }

/* Status badge — pill */
.tp-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 100px;
    font-size: 11px; font-weight: 600; letter-spacing: .02em; white-space: nowrap;
}
.tp-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: .6; }
.tp-badge-draft     { background: #f4f4f5; color: #71717a; }
.tp-badge-sent      { background: #eff6ff; color: #2563eb; }
.tp-badge-paid      { background: #f0fdf4; color: #16a34a; }
.tp-badge-accepted  { background: #f0fdf4; color: #16a34a; }
.tp-badge-cancelled { background: #fff1f2; color: #e11d48; }
.tp-badge-expired   { background: #fffbeb; color: #d97706; }
.tp-badge-invoice   { background: #eef2ff; color: #4f46e5; }
.tp-badge-quotation { background: #fdf4ff; color: #9333ea; }

/* Row action menu */
.tp-row-menu { display: inline-flex; }
.tp-row-menu-btn {
    width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    font-size: 16px; color: #bbb; transition: background .1s, color .1s;
    opacity: 1; letter-spacing: -.05em; font-weight: 800;
}
.tp-row-menu-btn:hover { background: #f0f0f0; color: #0a0a0a; }
/* Dropdown is fixed-position so overflow:hidden on .tp-card doesn't clip it */
.tp-dropdown {
    display: none; position: fixed; z-index: 9999;
    background: #fff; border: 1px solid #e8e8e8; border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0,0,0,.12); min-width: 168px; overflow: hidden;
}
.tp-dropdown.open { display: block; }
.tp-dropdown a, .tp-dropdown button {
    display: flex; align-items: center; gap: 10px; width: 100%;
    padding: 10px 14px; font-size: 13px; color: #333; text-decoration: none;
    background: none; border: none; cursor: pointer; text-align: left;
    transition: background .08s;
}
.tp-dropdown a:hover, .tp-dropdown button:hover { background: #f7f7f7; color: #0a0a0a; }
.tp-dropdown .tp-dd-danger { color: #e11d48; }
.tp-dropdown .tp-dd-danger:hover { background: #fff1f2; color: #e11d48; }
.tp-dropdown .tp-dd-divider { height: 1px; background: #f0f0f0; margin: 4px 0; }

/* Empty state */
.tp-empty { text-align: center; padding: 72px 20px; }
.tp-empty-icon {
    width: 56px; height: 56px; border-radius: 14px; background: #f5f5f5;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; margin: 0 auto 16px; color: #ccc;
}
.tp-empty-title { font-size: 15px; font-weight: 700; color: #333; margin-bottom: 6px; }
.tp-empty-sub   { font-size: 13px; color: #aaa; margin-bottom: 22px; }

/* Pagination */
.tp-pagination {
    padding: 14px 20px; border-top: 1px solid #f0f0f0;
    display: flex; align-items: center; justify-content: space-between;
    font-size: 12px; color: #bbb;
}
.tp-pagination .tp-pag-links { display: flex; gap: 4px; }

@media (max-width: 960px) {
    .tp-stats { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 640px) {
    .tp-stats { grid-template-columns: repeat(2,1fr); }
    .tp-filters { gap: 6px; }
    .tp-filter-field.tf-search { min-width: 0; }
}
</style>

<div class="tp">


<div class="tp-header">
    <div class="tp-header-left">
        <h1>TourPay</h1>
        <p><?php echo e(__("Invoicing & quotations — professional, client-ready.")); ?></p>
    </div>
    <div class="tp-header-right">
        <a href="<?php echo e(route('tourpay.vendor.create', ['type' => 'quotation'])); ?>" class="tp-btn tp-btn-ghost">
            <i class="icofont-file-document"></i> <?php echo e(__("Quotation")); ?>

        </a>
        <a href="<?php echo e(route('tourpay.vendor.create', ['type' => 'invoice'])); ?>" class="tp-btn tp-btn-primary">
            <i class="icofont-plus"></i> <?php echo e(__("New Invoice")); ?>

        </a>
    </div>
</div>

<?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<div class="tp-stats">
    
    <div class="tp-stat tp-stat-outstanding">
        <div class="tp-stat-label"><i class="icofont-clock-time"></i> <?php echo e(__("Outstanding")); ?></div>
        <?php if($stats['outstanding_by_cur']->isEmpty()): ?>
            <div class="tp-cur-empty">—</div>
        <?php else: ?>
            <div class="tp-cur-rows">
                <?php $__currentLoopData = $stats['outstanding_by_cur']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cur): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="tp-cur-row">
                    <span class="tp-cur-code"><?php echo e($cur->currency); ?></span>
                    <span class="tp-cur-val blue"><?php echo e(number_format($cur->total, 0)); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
        <div class="tp-stat-sub"><?php echo e(__("Sent & drafts")); ?></div>
    </div>

    
    <div class="tp-stat tp-stat-paid">
        <div class="tp-stat-label"><i class="icofont-check-circled"></i> <?php echo e(__("Paid This Month")); ?></div>
        <?php if($stats['paid_month_by_cur']->isEmpty()): ?>
            <div class="tp-cur-empty">—</div>
        <?php else: ?>
            <div class="tp-cur-rows">
                <?php $__currentLoopData = $stats['paid_month_by_cur']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cur): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="tp-cur-row">
                    <span class="tp-cur-code"><?php echo e($cur->currency); ?></span>
                    <span class="tp-cur-val green"><?php echo e(number_format($cur->total, 0)); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
        <div class="tp-stat-sub"><?php echo e(now()->format('F Y')); ?></div>
    </div>

    <div class="tp-stat">
        <div class="tp-stat-label"><i class="icofont-edit-alt"></i> <?php echo e(__("Drafts")); ?></div>
        <div class="tp-stat-amount"><?php echo e($stats['drafts']); ?></div>
        <div class="tp-stat-sub"><?php echo e(__("Unsent documents")); ?></div>
    </div>
    <div class="tp-stat">
        <div class="tp-stat-label"><i class="icofont-document-folder"></i> <?php echo e(__("All Documents")); ?></div>
        <div class="tp-stat-amount"><?php echo e($stats['total']); ?></div>
        <div class="tp-stat-sub"><?php echo e(__("Invoices & quotations")); ?></div>
    </div>
</div>


<form method="GET" action="<?php echo e(route('tourpay.vendor.index')); ?>">
<div class="tp-filters">
    <div class="tp-filter-field tf-search">
        <i class="icofont-search"></i>
        <input type="text" name="s" value="<?php echo e(request('s')); ?>" placeholder="<?php echo e(__('Search ref, client, title…')); ?>">
    </div>
    <div class="tp-filter-field tf-select">
        <select name="type">
            <option value=""><?php echo e(__("All Types")); ?></option>
            <option value="invoice"   <?php echo e(request('type')=='invoice'   ? 'selected' : ''); ?>><?php echo e(__("Invoice")); ?></option>
            <option value="quotation" <?php echo e(request('type')=='quotation' ? 'selected' : ''); ?>><?php echo e(__("Quotation")); ?></option>
        </select>
    </div>
    <div class="tp-filter-field tf-select">
        <select name="status">
            <option value=""><?php echo e(__("All Statuses")); ?></option>
            <?php $__currentLoopData = ['draft','sent','paid','accepted','cancelled','expired']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($st); ?>" <?php echo e(request('status')==$st ? 'selected' : ''); ?>><?php echo e(ucfirst($st)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <?php if($stats['currencies']->count() > 1): ?>
    <div class="tp-filter-field tf-select">
        <select name="currency">
            <option value=""><?php echo e(__("All Currencies")); ?></option>
            <?php $__currentLoopData = $stats['currencies']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cur): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($cur); ?>" <?php echo e(request('currency')==$cur ? 'selected' : ''); ?>><?php echo e($cur); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="tp-filter-field">
        <i class="icofont-calendar"></i>
        <input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>" style="min-width:130px;" placeholder="<?php echo e(__('From')); ?>">
    </div>
    <div class="tp-filter-field">
        <i class="icofont-calendar"></i>
        <input type="date" name="date_to" value="<?php echo e(request('date_to')); ?>" style="min-width:130px;" placeholder="<?php echo e(__('To')); ?>">
    </div>
    <button type="submit" class="tp-btn tp-btn-primary" style="padding:9px 16px;font-size:12px;">
        <i class="icofont-search"></i> <?php echo e(__("Filter")); ?>

    </button>
    <?php if(request()->hasAny(['s','type','status','currency','date_from','date_to'])): ?>
        <div class="tp-filter-chips">
            <a href="<?php echo e(route('tourpay.vendor.index')); ?>" class="tp-filter-chip">
                <i class="icofont-close" style="font-size:9px;"></i> <?php echo e(__("Clear filters")); ?>

            </a>
        </div>
    <?php endif; ?>
</div>
</form>


<div class="tp-card">
    <table class="tp-table">
        <thead>
            <tr>
                <th><?php echo e(__("Reference")); ?></th>
                <th><?php echo e(__("Client")); ?></th>
                <th><?php echo e(__("Type")); ?></th>
                <th><?php echo e(__("Amount")); ?></th>
                <th><?php echo e(__("Status")); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $initials = collect(explode(' ', $row->client_name ?? 'U'))->map(fn($w) => strtoupper(mb_substr($w,0,1)))->take(2)->implode('');
                $colors   = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#14b8a6'];
                $avatarBg = $colors[crc32($row->client_name ?? '') % count($colors)];
            ?>
            <tr>
                <td>
                    <div class="tp-ref"><?php echo e($row->invoice_number); ?></div>
                    <div class="tp-ref-date"><?php echo e($row->created_at->format('d M Y')); ?></div>
                </td>
                <td>
                    <div class="tp-client-cell">
                        <div class="tp-avatar" style="background:<?php echo e($avatarBg); ?>"><?php echo e($initials); ?></div>
                        <div>
                            <div class="tp-client-name"><?php echo e($row->client_name ?: '—'); ?></div>
                            <?php if($row->client_email): ?><div class="tp-client-email"><?php echo e($row->client_email); ?></div><?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><span class="tp-badge tp-badge-<?php echo e($row->type); ?>"><?php echo e(ucfirst($row->type)); ?></span></td>
                <td>
                    <div class="tp-amount"><span class="tp-currency"><?php echo e($row->currency); ?></span><?php echo e(number_format($row->total, 2)); ?></div>
                </td>
                <td><span class="tp-badge tp-badge-<?php echo e($row->status); ?>"><?php echo e(ucfirst($row->status)); ?></span></td>
                <td>
                    <div class="tp-row-menu">
                        <button type="button" class="tp-row-menu-btn" onclick="toggleMenu(this)">···</button>
                        <div class="tp-dropdown">
                            <a href="<?php echo e(route('tourpay.vendor.view', $row->id)); ?>"><i class="icofont-eye"></i> <?php echo e(__("View")); ?></a>
                            <a href="<?php echo e(route('tourpay.vendor.edit', $row->id)); ?>"><i class="icofont-edit"></i> <?php echo e(__("Edit")); ?></a>
                            <a href="<?php echo e(route('tourpay.vendor.pdf', $row->id)); ?>" target="_blank"><i class="icofont-file-pdf"></i> <?php echo e(__("Download PDF")); ?></a>
                            <?php if(!in_array($row->status, ['paid','cancelled'])): ?>
                            <div class="tp-dd-divider"></div>
                            <form method="POST" action="<?php echo e(route('tourpay.vendor.mark-paid', $row->id)); ?>" style="display:contents;"
                                  onsubmit="return confirm(tpMarkPaidMsg)">
                                <?php echo csrf_field(); ?>
                                <button type="submit"><i class="icofont-check-circled" style="color:#16a34a;"></i> <?php echo e(__("Mark as Paid")); ?></button>
                            </form>
                            <?php endif; ?>
                            <div class="tp-dd-divider"></div>
                            <a href="<?php echo e(route('tourpay.vendor.delete', $row->id)); ?>"
                               class="tp-dd-danger"
                               onclick="return confirm('<?php echo e(__('Delete this record?')); ?>')">
                                <i class="icofont-trash"></i> <?php echo e(__("Delete")); ?>

                            </a>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6">
                    <div class="tp-empty">
                        <div class="tp-empty-icon"><i class="icofont-document-folder"></i></div>
                        <div class="tp-empty-title"><?php echo e(__("No documents yet")); ?></div>
                        <div class="tp-empty-sub"><?php echo e(__("Create your first invoice or quotation to get started.")); ?></div>
                        <a href="<?php echo e(route('tourpay.vendor.create', ['type' => 'invoice'])); ?>" class="tp-btn tp-btn-primary">
                            <i class="icofont-plus"></i> <?php echo e(__("New Invoice")); ?>

                        </a>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if(isset($rows) && $rows->total() > 0): ?>
    <div class="tp-pagination">
        <span><?php echo e(__("Showing :from–:to of :total", ['from' => $rows->firstItem(), 'to' => $rows->lastItem(), 'total' => $rows->total()])); ?></span>
        <div class="tp-pag-links"><?php echo e($rows->appends(request()->query())->links()); ?></div>
    </div>
    <?php endif; ?>
</div>

</div>

<script>
var tpMarkPaidMsg = "<?php echo e(__('Mark this invoice as paid?')); ?>";

function toggleMenu(btn) {
    var dd = btn.nextElementSibling;
    var isOpen = dd.classList.contains('open');
    // close all open dropdowns first
    document.querySelectorAll('.tp-dropdown.open').forEach(function(d){ d.classList.remove('open'); });
    if (!isOpen) {
        var rect = btn.getBoundingClientRect();
        // position fixed: below the button, aligned to its right edge
        dd.style.top  = (rect.bottom + 4) + 'px';
        dd.style.left = 'auto';
        dd.style.right = (window.innerWidth - rect.right) + 'px';
        dd.classList.add('open');
    }
}
// Reposition on scroll so dropdown tracks the button
window.addEventListener('scroll', function() {
    document.querySelectorAll('.tp-dropdown.open').forEach(function(d){ d.classList.remove('open'); });
}, true);
document.addEventListener('click', function(e) {
    if (!e.target.closest('.tp-row-menu')) {
        document.querySelectorAll('.tp-dropdown.open').forEach(function(d){ d.classList.remove('open'); });
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/TourPay/Views/frontend/index.blade.php ENDPATH**/ ?>