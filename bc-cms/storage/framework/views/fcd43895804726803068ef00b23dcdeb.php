<?php $__env->startPush('css'); ?>
<style>
/* ── Hub page ────────────────────────────────────────────────── */
.hub-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 28px; }
@media (max-width: 640px) { .hub-stats { grid-template-columns: 1fr 1fr; } }

.tp-stat {
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    padding: 18px 20px; position: relative; overflow: hidden;
}
.tp-stat::after {
    content: ''; position: absolute; bottom: -14px; right: -14px;
    width: 56px; height: 56px; border-radius: 50%; opacity: .4;
}
.tp-stat--default::after { background: #e5e7eb; }
.tp-stat--green::after   { background: #bbf7d0; }
.tp-stat--amber::after   { background: #fef08a; }
.tp-stat__label {
    font-size: 11px; font-weight: 600; letter-spacing: .08em;
    text-transform: uppercase; color: #aaa; margin-bottom: 8px;
    display: flex; align-items: center; gap: 6px;
}
.tp-stat__amount { font-size: 26px; font-weight: 800; color: #0a0a0a; letter-spacing: -.03em; line-height: 1; }
.tp-stat__amount.green { color: #16a34a; }
.tp-stat__amount.amber { color: #d97706; }
.tp-stat__sub { font-size: 11px; color: #bbb; margin-top: 5px; }

/* Required alert */
.hub-required {
    background: #0a0a0a; color: #fff; border-radius: 10px;
    padding: 16px 20px; display: flex; align-items: center; gap: 14px;
    margin-bottom: 28px; flex-wrap: wrap;
}
.hub-required__icon { font-size: 1.4rem; flex-shrink: 0; opacity: .8; }
.hub-required__text { flex: 1; font-size: 13px; line-height: 1.5; }
.hub-required__text strong { display: block; font-size: 14px; margin-bottom: 2px; }
.hub-required__btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 16px; border-radius: 7px; font-size: 13px; font-weight: 600;
    background: #fff; color: #0a0a0a !important; text-decoration: none !important;
    transition: opacity .12s; white-space: nowrap;
}
.hub-required__btn:hover { opacity: .88; }

/* Section label */
.hub-section-label {
    font-size: 10px; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: #aaa; margin-bottom: 14px;
}

/* OS category cards */
.hub-os-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
@media (max-width: 1100px) { .hub-os-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 580px)  { .hub-os-grid { grid-template-columns: 1fr; } }

.os-card {
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    overflow: hidden; transition: box-shadow .15s, transform .15s;
    text-decoration: none !important; display: flex; flex-direction: column;
}
.os-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.1); transform: translateY(-2px); }
.os-card--all-connected { border-color: #d1fae5; }

.os-card__top { padding: 18px 18px 14px; flex: 1; }
.os-card__icon {
    width: 44px; height: 44px; border-radius: 10px; border: 1px solid #ebebeb;
    background: #fafafa; display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px; overflow: hidden;
}
.os-card__icon img { width: 28px; height: 28px; object-fit: contain; }
.os-card__icon-fallback {
    font-size: 1.2rem; display: flex; align-items: center; justify-content: center;
    width: 100%; height: 100%;
}
.os-card__name { font-size: 14px; font-weight: 700; color: #0a0a0a; letter-spacing: -.01em; margin-bottom: 4px; }
.os-card__desc { font-size: 12px; color: #aaa; line-height: 1.45; }

.os-card__progress { padding: 0 18px 4px; }
.os-card__progress-track {
    height: 3px; background: #f0f0f0; border-radius: 2px; overflow: hidden;
}
.os-card__progress-fill { height: 100%; border-radius: 2px; transition: width .3s; }

.os-card__foot {
    padding: 10px 18px; background: #fafafa; border-top: 1px solid #f0f0f0;
    display: flex; align-items: center; justify-content: space-between;
}
.os-card__count { font-size: 11px; font-weight: 600; color: #aaa; }
.os-card__count.has-any { color: #16a34a; }
.os-card__arrow { font-size: 1rem; color: #ccc; }

/* Connected strip */
.hub-connected { margin-top: 32px; }
.hub-connected-pills { display: flex; flex-wrap: wrap; gap: 6px; }
.hub-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 12px; border-radius: 100px;
    background: #f0fdf4; color: #16a34a !important;
    border: 1px solid #d1fae5; font-size: 12px; font-weight: 600;
    text-decoration: none !important; transition: background .1s;
}
.hub-pill:hover { background: #dcfce7; }
.hub-pill__dot { width: 6px; height: 6px; border-radius: 50%; background: #16a34a; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="portal-eyebrow">Tsoka Platform</p>
            <h1 class="portal-h1">Integrations <em>& Channels</em></h1>
        </div>
    </div>

    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php
        $totalConnected  = collect($integrations)->filter(fn($s) => $s === 'connected')->count();
        $totalPossible   = collect($all)->flatten(1)->count();
        $requiredItems   = collect($all)->flatten(1)->filter(fn($i) => !empty($i['required']));
        $requiredPending = $requiredItems->filter(fn($i) => ($integrations[$i['slug']] ?? '') !== 'connected')->count();
    ?>
    <div class="hub-stats">
        <div class="tp-stat tp-stat--green">
            <div class="tp-stat__label"><i class="ion ion-ios-checkmark-circle"></i> Connected</div>
            <div class="tp-stat__amount green"><?php echo e($totalConnected); ?></div>
            <div class="tp-stat__sub">of <?php echo e($totalPossible); ?> available services</div>
        </div>
        <div class="tp-stat tp-stat--default">
            <div class="tp-stat__label"><i class="ion ion-ios-apps"></i> Categories</div>
            <div class="tp-stat__amount"><?php echo e(count($categories)); ?></div>
            <div class="tp-stat__sub">operating system groups</div>
        </div>
        <div class="tp-stat <?php echo e($requiredPending ? 'tp-stat--amber' : 'tp-stat--green'); ?>">
            <div class="tp-stat__label"><i class="ion ion-ios-warning"></i> Required Setup</div>
            <div class="tp-stat__amount <?php echo e($requiredPending ? 'amber' : 'green'); ?>"><?php echo e($requiredPending); ?></div>
            <div class="tp-stat__sub"><?php echo e($requiredPending ? 'pending — action needed' : 'all required services connected'); ?></div>
        </div>
    </div>

    
    <?php if($requiredPending > 0): ?>
    <div class="hub-required">
        <i class="ion ion-ios-warning hub-required__icon"></i>
        <div class="hub-required__text">
            <strong>Action required — <?php echo e($requiredPending); ?> core <?php echo e(Str::plural('service', $requiredPending)); ?> not connected</strong>
            <?php $__currentLoopData = $requiredItems->filter(fn($i) => ($integrations[$i['slug']] ?? '') !== 'connected'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ri): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span style="opacity:.7;font-size:12px"><?php echo e($ri['name']); ?><?php echo e(!$loop->last ? ' · ' : ''); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <a href="<?php echo e(route('admin.integrations.category', 'stay_os')); ?>" class="hub-required__btn">
            <i class="ion ion-ios-settings"></i> Set Up Now
        </a>
    </div>
    <?php endif; ?>

    
    <div class="hub-section-label">Operating Systems</div>

    <div class="hub-os-grid">
        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $count      = $counts[$key] ?? 0;
            $total      = count($all[$key] ?? []);
            $pct        = $total > 0 ? round(($count / $total) * 100) : 0;
            $allConn    = $count === $total && $total > 0;
            $firstItem  = $all[$key][0] ?? null;
            $firstLogo  = $firstItem['logo_domain'] ?? null;
        ?>
        <a href="<?php echo e(route('admin.integrations.category', $key)); ?>"
           class="os-card <?php echo e($allConn ? 'os-card--all-connected' : ''); ?>">
            <div class="os-card__top">
                <div class="os-card__icon">
                    <?php if($firstLogo): ?>
                    <img src="https://logo.clearbit.com/<?php echo e($firstLogo); ?>"
                         alt="<?php echo e($meta['label']); ?>"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <span class="os-card__icon-fallback" style="color:<?php echo e($meta['color']); ?>;display:none">
                        <i class="<?php echo e($meta['icon']); ?>"></i>
                    </span>
                    <?php else: ?>
                    <span class="os-card__icon-fallback" style="color:<?php echo e($meta['color']); ?>">
                        <i class="<?php echo e($meta['icon']); ?>"></i>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="os-card__name"><?php echo e($meta['label']); ?></div>
                <div class="os-card__desc"><?php echo e($meta['desc']); ?></div>
            </div>

            <?php if($total > 0): ?>
            <div class="os-card__progress">
                <div class="os-card__progress-track">
                    <div class="os-card__progress-fill"
                         style="width:<?php echo e($pct); ?>%;background:<?php echo e($meta['color']); ?>"></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="os-card__foot">
                <span class="os-card__count <?php echo e($count > 0 ? 'has-any' : ''); ?>">
                    <?php echo e($count); ?> / <?php echo e($total); ?> connected
                </span>
                <i class="ion ion-ios-arrow-forward os-card__arrow"></i>
            </div>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <?php $allConnected = collect($integrations)->filter(fn($s) => $s === 'connected'); ?>
    <?php if($allConnected->count()): ?>
    <div class="hub-connected">
        <div class="hub-section-label">Active Connections</div>
        <div class="hub-connected-pills">
            <?php $__currentLoopData = $allConnected; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slug => $_): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $def = \Pro\Integrations\Services\IntegrationRegistry::find($slug); ?>
            <?php if($def): ?>
            <a href="<?php echo e(route('admin.integrations.category', $def['category'] ?? 'stay_os')); ?>"
               class="hub-pill">
                <span class="hub-pill__dot"></span>
                <?php if(!empty($def['logo_domain'])): ?>
                <img src="https://logo.clearbit.com/<?php echo e($def['logo_domain']); ?>"
                     style="width:14px;height:14px;object-fit:contain;border-radius:2px"
                     onerror="this.style.display='none'" alt="">
                <?php endif; ?>
                <?php echo e($def['name']); ?>

            </a>
            <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Integrations/Views/admin/hub.blade.php ENDPATH**/ ?>