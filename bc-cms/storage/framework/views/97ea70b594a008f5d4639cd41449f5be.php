<?php $__env->startPush('css'); ?>
<style>
.wl-stat { background:#fff; border:1px solid #ebebeb; border-radius:10px; padding:18px 20px; }
.wl-stat-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#aaa; margin-bottom:6px; }
.wl-stat-val { font-size:22px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; line-height:1.1; }
.wl-stat-sub { font-size:11px; color:#bbb; margin-top:4px; }

.tp-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:100px; font-size:11px; font-weight:600; }
.tp-badge::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; opacity:.7; flex-shrink:0; }
.wl-success   { background:#f0fdf4; color:#16a34a; }
.wl-pending   { background:#fffbeb; color:#d97706; }
.wl-failed    { background:#fff1f2; color:#e11d48; }
.wl-default   { background:#f4f4f5; color:#71717a; }

.wl-table { width:100%; border-collapse:collapse; }
.wl-table thead { background:#fafafa; border-bottom:1px solid #ebebeb; }
.wl-table th { padding:10px 16px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#bbb; white-space:nowrap; text-align:left; }
.wl-table td { padding:14px 16px; font-size:13px; color:#222; border-bottom:1px solid #f7f7f7; vertical-align:middle; }
.wl-table tbody tr:hover { background:#fafafa; }
.wl-table tbody tr:last-child td { border-bottom:none; }

.tp-card { background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; margin-bottom:20px; }
.tp-btn-prim { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; border-radius:7px; font-size:13px; font-weight:700; background:#0a0a0a; color:#fff !important; border:none; cursor:pointer; text-decoration:none !important; }
.tp-btn-prim:hover { background:#333; }
.wl-credit-big { font-size:36px; font-weight:800; color:#0a0a0a; letter-spacing:-.04em; line-height:1; }
.wl-credit-eq { font-size:13px; color:#aaa; margin-top:6px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="portal-header" style="margin-bottom:28px">
    <div>
        <p class="portal-eyebrow"><?php echo e(__("Account")); ?></p>
        <h1 class="portal-h1"><?php echo e(__("Wallet")); ?></h1>
    </div>
    <a href="<?php echo e(route('user.wallet.buy')); ?>" class="tp-btn-prim" style="margin-top:auto">
        <i class="ion ion-ios-add-circle-outline"></i> <?php echo e(__("Buy Credits")); ?>

    </a>
</div>

<?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="wl-stat" style="border-left:3px solid #2563eb">
            <div class="wl-stat-label"><?php echo e(__("Credit Balance")); ?></div>
            <div class="wl-credit-big"><?php echo e(number_format($row->balance)); ?></div>
            <?php if($row->balance): ?>
            <div class="wl-credit-eq">≈ <?php echo e(format_money(credit_to_money($row->balance))); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php
        $totalIn  = $transactions->where('type','in')->sum('amount')  ?? 0;
        $totalOut = $transactions->where('type','out')->sum('amount') ?? 0;
    ?>
    <div class="col-6 col-md-4">
        <div class="wl-stat">
            <div class="wl-stat-label"><?php echo e(__("Total Topped Up")); ?></div>
            <div class="wl-stat-val" style="color:#16a34a"><?php echo e(number_format($totalIn)); ?></div>
            <div class="wl-stat-sub"><?php echo e(__("credits added")); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="wl-stat">
            <div class="wl-stat-label"><?php echo e(__("Total Spent")); ?></div>
            <div class="wl-stat-val" style="color:#d97706"><?php echo e(number_format($totalOut)); ?></div>
            <div class="wl-stat-sub"><?php echo e(__("credits used")); ?></div>
        </div>
    </div>
</div>


<div class="tp-card">
    <div style="padding:14px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:13px;font-weight:700;color:#0a0a0a"><?php echo e(__("Latest Transactions")); ?></span>
        <span style="font-size:12px;color:#aaa"><?php echo e($transactions->total()); ?> <?php echo e(__("records")); ?></span>
    </div>
    <div style="overflow-x:auto">
        <table class="wl-table">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th><?php echo e(__("Type")); ?></th>
                    <th><?php echo e(__("Amount")); ?></th>
                    <th><?php echo e(__("Gateway")); ?></th>
                    <th><?php echo e(__("Status")); ?></th>
                    <th><?php echo e(__("Description")); ?></th>
                    <th><?php echo e(__("Date")); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $sc = match($transaction->status ?? '') {
                        'completed', 'approved' => 'wl-success',
                        'pending'               => 'wl-pending',
                        'failed', 'cancelled'   => 'wl-failed',
                        default                 => 'wl-default',
                    };
                    $typeColor = ($transaction->type === 'in') ? '#16a34a' : '#d97706';
                    $typeIcon  = ($transaction->type === 'in') ? '↑' : '↓';
                ?>
                <tr>
                    <td style="color:#aaa;font-size:12px">#<?php echo e($transaction->id); ?></td>
                    <td>
                        <span style="color:<?php echo e($typeColor); ?>;font-weight:700;font-size:13px">
                            <?php echo e($typeIcon); ?> <?php echo e(ucfirst($transaction->type)); ?>

                        </span>
                    </td>
                    <td style="font-size:14px;font-weight:800;color:#0a0a0a;letter-spacing:-.02em">
                        <?php echo e(number_format($transaction->amount)); ?>

                        <span style="font-size:11px;font-weight:400;color:#aaa">cr</span>
                    </td>
                    <td style="font-size:12px;color:#555">
                        <?php if(!empty($transaction->payment) && $transaction->payment->gateway_obj): ?>
                            <?php echo e($transaction->payment->gateway_obj->getDisplayName() ?? '—'); ?>

                        <?php else: ?>
                            <span style="color:#ccc">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="tp-badge <?php echo e($sc); ?>"><?php echo e($transaction->status_name ?? ucfirst($transaction->status ?? '—')); ?></span>
                    </td>
                    <td style="font-size:12px;color:#888">
                        <?php if(!empty($transaction->meta['admin_deposit'])): ?>
                            <?php echo e(__("Deposit by :name", ['name' => $transaction->author->display_name ?? ''])); ?>

                        <?php else: ?>
                            <span style="color:#ccc">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#aaa;white-space:nowrap"><?php echo e(display_datetime($transaction->created_at)); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:60px 20px;color:#aaa;font-size:13px">
                        <i class="ion ion-ios-wallet" style="font-size:32px;display:block;margin-bottom:10px;color:#e0e0e0"></i>
                        <?php echo e(__("No transactions yet.")); ?>

                        <br>
                        <a href="<?php echo e(route('user.wallet.buy')); ?>" style="color:#2563eb;font-size:12px;margin-top:8px;display:inline-block">
                            <?php echo e(__("Buy your first credits →")); ?>

                        </a>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-end"><?php echo e($transactions->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/wallet/index.blade.php ENDPATH**/ ?>