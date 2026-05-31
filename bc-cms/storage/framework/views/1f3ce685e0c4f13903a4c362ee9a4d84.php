<?php $__env->startPush('css'); ?>
<style>
.cc-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px; }
@media(max-width:900px){ .cc-stats{ grid-template-columns:repeat(2,1fr); } }

.tp-stat { background:#fff; border:1px solid #ebebeb; border-radius:10px; padding:18px 20px; position:relative; overflow:hidden; }
.tp-stat::after { content:''; position:absolute; bottom:-14px; right:-14px; width:56px; height:56px; border-radius:50%; opacity:.4; }
.tp-stat--default::after { background:#e5e7eb; }
.tp-stat--green::after   { background:#bbf7d0; }
.tp-stat--red::after     { background:#fecaca; }
.tp-stat--gray::after    { background:#e5e7eb; }
.tp-stat__label { font-size:11px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#aaa; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.tp-stat__amount { font-size:26px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; line-height:1; }
.tp-stat__amount.green { color:#16a34a; } .tp-stat__amount.red { color:#e11d48; } .tp-stat__amount.gray { color:#71717a; }
.tp-stat__sub { font-size:11px; color:#bbb; margin-top:5px; }

.cc-filter { display:flex; align-items:center; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.cc-search { display:flex; align-items:center; gap:8px; background:#fff; border:1.5px solid #e4e4e4; border-radius:8px; padding:0 12px; height:38px; flex:1; min-width:180px; max-width:260px; transition:border-color .15s; }
.cc-search:focus-within { border-color:#0a0a0a; }
.cc-search input { border:none; outline:none; background:transparent; font-size:13px; color:#333; width:100%; }
.cc-search input::placeholder { color:#bbb; }
.cc-select { border:1.5px solid #e4e4e4; border-radius:8px; padding:0 10px; height:38px; font-size:12px; color:#333; background:#fff; outline:none; cursor:pointer; }
.cc-chips { display:flex; gap:4px; flex-wrap:wrap; }
.cc-chip { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:100px; border:1.5px solid #e4e4e4; font-size:11px; font-weight:600; color:#555; background:#fff; cursor:pointer; text-decoration:none; transition:all .12s; }
.cc-chip:hover { border-color:#0a0a0a; color:#0a0a0a; }
.cc-chip.active { background:#0a0a0a; border-color:#0a0a0a; color:#fff; }
.cc-chip .n { background:#f0f0f0; color:#888; min-width:18px; height:18px; border-radius:100px; display:inline-flex; align-items:center; justify-content:center; font-size:10px; padding:0 4px; }
.cc-chip.active .n { background:rgba(255,255,255,.2); color:#fff; }

.tp-card { background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; }
.tp-table { width:100%; border-collapse:collapse; }
.tp-table thead tr { background:#fafafa; border-bottom:1px solid #f0f0f0; }
.tp-table th { padding:10px 16px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#bbb; white-space:nowrap; }
.tp-table td { padding:14px 16px; font-size:13px; color:#222; border-bottom:1px solid #f7f7f7; vertical-align:middle; }
.tp-table tbody tr:last-child td { border-bottom:none; }
.tp-table tbody tr:hover { background:#fafafa; transition:background .08s; }

.tp-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:100px; font-size:11px; font-weight:600; white-space:nowrap; }
.tp-badge::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
.tp-badge--green  { background:#f0fdf4; color:#16a34a; } .tp-badge--green::before  { background:#16a34a; }
.tp-badge--red    { background:#fff1f2; color:#e11d48; } .tp-badge--red::before    { background:#e11d48; }
.tp-badge--gray   { background:#f4f4f5; color:#71717a; } .tp-badge--gray::before   { background:#71717a; }
.tp-badge--blue   { background:#eff6ff; color:#2563eb; } .tp-badge--blue::before   { background:#2563eb; }
.tp-badge--purple { background:#f5f3ff; color:#7c3aed; } .tp-badge--purple::before { background:#7c3aed; }

.tp-ab-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:7px; font-size:12px; font-weight:600; border:none; cursor:pointer; text-decoration:none !important; transition:all .12s; white-space:nowrap; }
.tp-ab-btn--primary { background:#0a0a0a; color:#fff !important; }
.tp-ab-btn--primary:hover { background:#333; }
.tp-ab-btn--outline { background:#fff; border:1.5px solid #e0e0e0; color:#0a0a0a !important; }
.tp-ab-btn--outline:hover { border-color:#0a0a0a; }
.tp-ab-btn--ghost { background:transparent; color:#555 !important; border:1.5px solid #e4e4e4; }
.tp-ab-btn--ghost:hover { border-color:#0a0a0a; color:#0a0a0a !important; }

.tp-empty { text-align:center; padding:56px 20px; color:#aaa; font-size:13px; }
.tp-empty i { font-size:2rem; display:block; margin-bottom:12px; color:#e0e0e0; }

.cc-avatar { width:32px; height:32px; border-radius:50%; background:#0a0a0a; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }

.portal-eyebrow { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#aaa; margin-bottom:4px; }
.portal-h1 { font-size:24px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; margin:0 0 2px; }
.portal-h1 em { font-style:normal; font-weight:400; color:#aaa; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="portal-eyebrow">Guest Messaging</p>
            <h1 class="portal-h1">Concierge <em>& Support</em></h1>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?php echo e(route('admin.concierge.create')); ?>" class="tp-ab-btn tp-ab-btn--primary">
                <i class="ion ion-ios-add-circle"></i> New Conversation
            </a>
        </div>
    </div>

    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php
        $cntAll       = $conversations->total();
        $cntOpen      = \Pro\Concierge\Models\ConciergeConversation::where('status','open')->count();
        $cntEscalated = \Pro\Concierge\Models\ConciergeConversation::where('status','escalated')->count();
        $cntResolved  = \Pro\Concierge\Models\ConciergeConversation::where('status','resolved')->count();
    ?>
    <div class="cc-stats">
        <div class="tp-stat tp-stat--default">
            <div class="tp-stat__label"><i class="ion ion-ios-chatboxes"></i> Total</div>
            <div class="tp-stat__amount"><?php echo e($cntAll); ?></div>
            <div class="tp-stat__sub">all conversations</div>
        </div>
        <div class="tp-stat tp-stat--green">
            <div class="tp-stat__label"><i class="ion ion-ios-chatbubble"></i> Open</div>
            <div class="tp-stat__amount green"><?php echo e($cntOpen); ?></div>
            <div class="tp-stat__sub">awaiting response</div>
        </div>
        <div class="tp-stat tp-stat--red">
            <div class="tp-stat__label"><i class="ion ion-ios-warning"></i> Escalated</div>
            <div class="tp-stat__amount red"><?php echo e($cntEscalated); ?></div>
            <div class="tp-stat__sub">needs urgent attention</div>
        </div>
        <div class="tp-stat tp-stat--gray">
            <div class="tp-stat__label"><i class="ion ion-ios-checkmark-circle"></i> Resolved</div>
            <div class="tp-stat__amount gray"><?php echo e($cntResolved); ?></div>
            <div class="tp-stat__sub">closed conversations</div>
        </div>
    </div>

    
    <?php $curStatus = request('status',''); $curChannel = request('channel',''); $curSearch = request('s',''); ?>
    <form method="GET" class="cc-filter">
        <div class="cc-search">
            <i class="ion ion-ios-search" style="color:#ccc"></i>
            <input type="text" name="s" value="<?php echo e($curSearch); ?>" placeholder="Search guest name or email…">
        </div>
        <select name="channel" class="cc-select" onchange="this.form.submit()">
            <option value="">All Channels</option>
            <option value="web"      <?php if($curChannel==='web'): echo 'selected'; endif; ?>>Web</option>
            <option value="whatsapp" <?php if($curChannel==='whatsapp'): echo 'selected'; endif; ?>>WhatsApp</option>
            <option value="email"    <?php if($curChannel==='email'): echo 'selected'; endif; ?>>Email</option>
            <option value="phone"    <?php if($curChannel==='phone'): echo 'selected'; endif; ?>>Phone</option>
        </select>
        <div class="cc-chips">
            <a href="?" class="cc-chip <?php echo e($curStatus==='' ? 'active':''); ?>">
                All <span class="n"><?php echo e($cntAll); ?></span>
            </a>
            <a href="?status=open<?php echo e($curChannel ? '&channel='.$curChannel : ''); ?>"
               class="cc-chip <?php echo e($curStatus==='open' ? 'active':''); ?>">
                Open <span class="n"><?php echo e($cntOpen); ?></span>
            </a>
            <a href="?status=escalated<?php echo e($curChannel ? '&channel='.$curChannel : ''); ?>"
               class="cc-chip <?php echo e($curStatus==='escalated' ? 'active':''); ?>">
                Escalated <span class="n"><?php echo e($cntEscalated); ?></span>
            </a>
            <a href="?status=resolved<?php echo e($curChannel ? '&channel='.$curChannel : ''); ?>"
               class="cc-chip <?php echo e($curStatus==='resolved' ? 'active':''); ?>">
                Resolved <span class="n"><?php echo e($cntResolved); ?></span>
            </a>
        </div>
        <?php if($curSearch): ?><button type="submit" class="tp-ab-btn tp-ab-btn--ghost">Search</button><?php endif; ?>
    </form>

    
    <div class="tp-card">
        <table class="tp-table">
            <thead>
                <tr>
                    <th>Guest</th>
                    <th>Channel</th>
                    <th>Last Message</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="cc-avatar"><?php echo e(strtoupper(substr($conv->guestDisplayName(), 0, 1))); ?></div>
                            <div>
                                <a href="<?php echo e(route('admin.concierge.show', $conv)); ?>"
                                   style="font-weight:600;color:#0a0a0a;text-decoration:none">
                                    <?php echo e($conv->guestDisplayName()); ?>

                                </a>
                                <?php if($conv->guest_email): ?>
                                <div style="font-size:11px;color:#aaa"><?php echo e($conv->guest_email); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php
                            $chBadge = match($conv->channel) {
                                'whatsapp' => 'tp-badge--green',
                                'email'    => 'tp-badge--blue',
                                default    => 'tp-badge--gray',
                            };
                        ?>
                        <span class="tp-badge <?php echo e($chBadge); ?>"><?php echo e(ucfirst($conv->channel)); ?></span>
                    </td>
                    <td style="max-width:280px">
                        <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px;color:#555;font-size:12px">
                            <?php echo e($conv->last_message ?: '—'); ?>

                        </div>
                    </td>
                    <td>
                        <?php if($conv->status === 'open'): ?>
                            <span class="tp-badge tp-badge--green">Open</span>
                        <?php elseif($conv->status === 'escalated'): ?>
                            <span class="tp-badge tp-badge--red">Escalated</span>
                        <?php else: ?>
                            <span class="tp-badge tp-badge--gray">Resolved</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#aaa;font-size:12px;white-space:nowrap"><?php echo e($conv->last_message_at?->diffForHumans()); ?></td>
                    <td>
                        <a href="<?php echo e(route('admin.concierge.show', $conv)); ?>" class="tp-ab-btn tp-ab-btn--outline">
                            Open
                        </a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6">
                        <div class="tp-empty">
                            <i class="ion ion-ios-chatboxes"></i>
                            No conversations yet.
                            <div style="margin-top:12px">
                                <a href="<?php echo e(route('admin.concierge.create')); ?>" class="tp-ab-btn tp-ab-btn--primary">
                                    <i class="ion ion-ios-add-circle"></i> Start First Conversation
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if($conversations->hasPages()): ?>
        <div style="padding:14px 16px;border-top:1px solid #f0f0f0">
            <?php echo e($conversations->withQueryString()->links()); ?>

        </div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Concierge/Views/admin/index.blade.php ENDPATH**/ ?>