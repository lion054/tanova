<?php $__env->startPush('css'); ?>
<style>
/* ── Wetu Itineraries ────────────────────────────────────────── */
.wetu-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
@media (max-width: 860px) { .wetu-stats { grid-template-columns: repeat(2, 1fr); } }

.tp-stat {
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    padding: 16px 18px; position: relative; overflow: hidden;
}
.tp-stat::after {
    content: ''; position: absolute; bottom: -14px; right: -14px;
    width: 52px; height: 52px; border-radius: 50%; opacity: .35; background: #e5e7eb;
}
.tp-stat--green::after  { background: #bbf7d0; }
.tp-stat--amber::after  { background: #fef08a; }
.tp-stat--red::after    { background: #fecaca; }
.tp-stat__label {
    font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    color: #aaa; margin-bottom: 6px; display: flex; align-items: center; gap: 5px;
}
.tp-stat__amount { font-size: 22px; font-weight: 800; color: #0a0a0a; letter-spacing: -.03em; line-height: 1; }
.tp-stat__amount.green { color: #16a34a; }
.tp-stat__amount.amber { color: #d97706; }
.tp-stat__amount.red   { color: #e11d48; }
.tp-stat__sub { font-size: 11px; color: #bbb; margin-top: 4px; }

/* Type filter tabs */
.wetu-type-tabs { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 16px; }
.wetu-tab {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 100px; border: 1.5px solid #e4e4e4;
    font-size: 12px; font-weight: 600; color: #555; background: #fff;
    cursor: pointer; transition: all .12s; white-space: nowrap;
}
.wetu-tab:hover { border-color: #0a0a0a; color: #0a0a0a; }
.wetu-tab.active { background: #0a0a0a; border-color: #0a0a0a; color: #fff; }

/* Filter bar */
.wetu-filters { display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
.tp-filter-field {
    display: flex; align-items: center; gap: 8px;
    background: #fff; border: 1.5px solid #e4e4e4; border-radius: 8px;
    padding: 0 12px; height: 38px; transition: border-color .15s;
}
.tp-filter-field:focus-within { border-color: #0a0a0a; }
.tp-filter-field input,
.tp-filter-field select {
    border: none; outline: none; background: transparent;
    font-size: 13px; color: #333; height: 100%;
}
.tp-filter-field input::placeholder { color: #bbb; }
.tp-filter-field .tf-icon { color: #ccc; font-size: 1rem; flex-shrink: 0; }
.tf-search { flex: 1; min-width: 200px; }
.tf-select { min-width: 160px; }

/* Table card */
.wetu-table-card { background: #fff; border: 1px solid #ebebeb; border-radius: 10px; overflow: hidden; }

.tp-table { width: 100%; border-collapse: collapse; }
.tp-table thead { background: #fafafa; border-bottom: 1px solid #f0f0f0; }
.tp-table th {
    padding: 10px 16px; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em; color: #bbb; text-align: left;
}
.tp-table td {
    padding: 13px 16px; font-size: 13px; color: #222;
    border-bottom: 1px solid #f7f7f7; vertical-align: middle;
}
.tp-table tbody tr:last-child td { border-bottom: none; }
.tp-table tbody tr:hover td { background: #fafafa; transition: background .08s; }

/* Badges */
.tp-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 9px; border-radius: 100px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
}
.tp-badge__dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: .6; flex-shrink: 0; }
.tp-badge--booked   { background: #f0fdf4; color: #16a34a; }
.tp-badge--quoted   { background: #fffbeb; color: #d97706; }
.tp-badge--cancelled{ background: #fff1f2; color: #e11d48; }
.tp-badge--none     { background: #f4f4f5; color: #71717a; }

/* Night pill */
.night-pill {
    display: inline-block; padding: 2px 8px; border-radius: 100px;
    background: #f4f4f5; color: #555; font-size: 11px; font-weight: 600;
}

/* Action icon buttons */
.row-actions { display: flex; gap: 4px; }
.icon-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 6px; border: none;
    background: transparent; color: #bbb; cursor: pointer;
    transition: background .1s, color .1s; font-size: .9rem;
    text-decoration: none !important;
}
.icon-btn:hover { background: #f5f5f5; color: #0a0a0a; }
.icon-btn--import { background: #f0fdf4; color: #16a34a; }
.icon-btn--import:hover { background: #dcfce7; }

/* Wetu Connect card */
.wetu-connect-card {
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    padding: 16px 20px; margin-top: 20px;
    display: flex; align-items: flex-start; gap: 14px;
    border-left: 3px solid;
}
.wetu-connect-card--ok  { border-left-color: #16a34a; }
.wetu-connect-card--off { border-left-color: #e4e4e4; }
.wetu-connect-card__icon { font-size: 1.3rem; flex-shrink: 0; margin-top: 1px; }
.wetu-connect-card--ok  .wetu-connect-card__icon { color: #16a34a; }
.wetu-connect-card--off .wetu-connect-card__icon { color: #bbb; }
.wetu-connect-card__body { flex: 1; }
.wetu-connect-card__title { font-size: 13px; font-weight: 700; color: #0a0a0a; margin-bottom: 3px; }
.wetu-connect-card__desc  { font-size: 13px; color: #888; line-height: 1.5; }

/* Empty state */
.wetu-empty { text-align: center; padding: 48px 24px; }
.wetu-empty i { font-size: 2.5rem; color: #d0d0d0; display: block; margin-bottom: 12px; }
.wetu-empty__title { font-size: 14px; font-weight: 600; color: #aaa; margin-bottom: 4px; }
.wetu-empty__sub   { font-size: 13px; color: #ccc; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="portal-eyebrow">Wetu Integration</p>
            <h1 class="portal-h1">Itineraries <em>from Wetu</em></h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="POST" action="<?php echo e(route('admin.integrations.wetu.sync')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="tp-ab-btn tp-ab-btn--outline">
                    <i class="ion ion-ios-refresh"></i> Sync Itineraries
                </button>
            </form>
            <a href="<?php echo e(route('admin.integrations.category', 'exp_os')); ?>" class="tp-ab-btn tp-ab-btn--ghost"
               style="text-decoration:none">
                <i class="ion ion-ios-arrow-back"></i> Exp OS
            </a>
        </div>
    </div>

    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php
        $itin      = collect($itineraries);
        $cBooked   = $itin->whereIn('BookingStatus', ['Booked','Paid','Travelled'])->count();
        $cQuoted   = $itin->whereIn('BookingStatus', ['Quoted','AwaitingQuotation','Provisional'])->count();
        $cCancelled= $itin->whereIn('BookingStatus', ['Cancelled','DidNotBook'])->count();
    ?>
    <div class="wetu-stats">
        <div class="tp-stat">
            <div class="tp-stat__label"><i class="ion ion-ios-map"></i> Total</div>
            <div class="tp-stat__amount"><?php echo e($total); ?></div>
            <div class="tp-stat__sub">itineraries in your account</div>
        </div>
        <div class="tp-stat tp-stat--green">
            <div class="tp-stat__label"><i class="ion ion-ios-checkmark-circle"></i> Confirmed</div>
            <div class="tp-stat__amount green"><?php echo e($cBooked); ?></div>
            <div class="tp-stat__sub">Booked · Paid · Travelled</div>
        </div>
        <div class="tp-stat tp-stat--amber">
            <div class="tp-stat__label"><i class="ion ion-ios-time"></i> In Progress</div>
            <div class="tp-stat__amount amber"><?php echo e($cQuoted); ?></div>
            <div class="tp-stat__sub">Quoted · Awaiting quotation</div>
        </div>
        <div class="tp-stat tp-stat--red">
            <div class="tp-stat__label"><i class="ion ion-ios-close-circle"></i> Cancelled</div>
            <div class="tp-stat__amount red"><?php echo e($cCancelled); ?></div>
            <div class="tp-stat__sub">Cancelled · Did not book</div>
        </div>
    </div>

    
    <div class="wetu-type-tabs">
        <button class="wetu-tab active" data-type="all">All</button>
        <button class="wetu-tab" data-type="Personal">Personal</button>
        <button class="wetu-tab" data-type="Sample">Sample / Template</button>
        <button class="wetu-tab" data-type="DayTour">Day Tours</button>
        <button class="wetu-tab" data-type="MultiDayTour">Multi-Day Tours</button>
    </div>

    
    <form method="GET" class="wetu-filters">
        <div class="tp-filter-field tf-search">
            <i class="ion ion-ios-search tf-icon"></i>
            <input type="text" name="search" value="<?php echo e($filters['search'] ?? ''); ?>"
                   placeholder="Search by name or client…">
        </div>
        <div class="tp-filter-field tf-select">
            <select name="booking_status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php $__currentLoopData = ['None'=>'None','Quoted'=>'Quoted','Booked'=>'Booked','Paid'=>'Paid','Travelled'=>'Travelled','Cancelled'=>'Cancelled','AwaitingQuotation'=>'Awaiting Quote']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $lbl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($val); ?>" <?php if(($filters['booking_status'] ?? '') === $val): echo 'selected'; endif; ?>><?php echo e($lbl); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <button type="submit" class="tp-ab-btn tp-ab-btn--outline">Apply</button>
        <?php if(array_filter($filters)): ?>
        <a href="<?php echo e(route('admin.integrations.wetu.itineraries')); ?>" class="tp-ab-btn tp-ab-btn--ghost"
           style="text-decoration:none">Clear</a>
        <?php endif; ?>
    </form>

    
    <div class="wetu-table-card">
        <?php if(empty($itineraries)): ?>
        <div class="wetu-empty">
            <i class="ion ion-ios-map"></i>
            <div class="wetu-empty__title">No itineraries found</div>
            <div class="wetu-empty__sub">Try syncing or adjusting your filters.</div>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="tp-table" id="wetu-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Client</th>
                        <th>Start Date</th>
                        <th>Nights</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Modified</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $itineraries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $id       = $it['Identifier']    ?? $it['identifier']     ?? '';
                        $name     = $it['Name']          ?? $it['name']           ?? '—';
                        $client   = $it['ClientName']    ?? $it['client_name']    ?? '—';
                        $startRaw = $it['StartDate']     ?? $it['start_date']     ?? null;
                        $nights   = ($it['Days']         ?? $it['days']           ?? 1) - 1;
                        $type     = $it['Type']          ?? $it['type']           ?? 'Unknown';
                        $bStatus  = $it['BookingStatus'] ?? $it['booking_status'] ?? 'None';
                        $modified = $it['LastModified']  ?? $it['last_modified']  ?? null;

                        $badgeClass = match(true) {
                            in_array($bStatus, ['Booked','Paid','Travelled'])              => 'tp-badge--booked',
                            in_array($bStatus, ['Quoted','AwaitingQuotation','Provisional'])=> 'tp-badge--quoted',
                            in_array($bStatus, ['Cancelled','DidNotBook'])                 => 'tp-badge--cancelled',
                            default                                                         => 'tp-badge--none',
                        };
                    ?>
                    <tr data-type="<?php echo e($type); ?>">
                        <td>
                            <div style="font-weight:600;color:#0a0a0a"><?php echo e($name); ?></div>
                            <?php if($id): ?><div style="font-size:11px;color:#ccc;margin-top:2px;font-family:monospace"><?php echo e($id); ?></div><?php endif; ?>
                        </td>
                        <td style="color:#555"><?php echo e($client); ?></td>
                        <td style="color:#555;white-space:nowrap">
                            <?php echo e($startRaw ? \Carbon\Carbon::parse($startRaw)->format('d M Y') : '—'); ?>

                        </td>
                        <td>
                            <?php if($nights > 0): ?>
                                <span class="night-pill"><?php echo e($nights); ?>n</span>
                            <?php else: ?>
                                <span style="color:#ddd">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="tp-badge tp-badge--none" style="font-size:10px"><?php echo e($type); ?></span>
                        </td>
                        <td>
                            <span class="tp-badge <?php echo e($badgeClass); ?>">
                                <span class="tp-badge__dot"></span>
                                <?php echo e($bStatus); ?>

                            </span>
                        </td>
                        <td style="font-size:12px;color:#bbb;white-space:nowrap">
                            <?php echo e($modified ? \Carbon\Carbon::parse($modified)->diffForHumans() : '—'); ?>

                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="https://wetu.com/Itinerary/<?php echo e($id); ?>" target="_blank"
                                   class="icon-btn" title="View on Wetu">
                                    <i class="ion ion-ios-open"></i>
                                </a>
                                <?php if($id): ?>
                                <form method="POST" action="<?php echo e(route('admin.integrations.wetu.import', $id)); ?>"
                                      class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="icon-btn icon-btn--import"
                                            onclick="return confirm('Import &quot;<?php echo e(addslashes($name)); ?>&quot; to Tanova?')"
                                            title="Import to Tanova">
                                        <i class="ion ion-ios-download"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    
    <?php $connectOk = $connectOk ?? false; ?>
    <div class="wetu-connect-card <?php echo e($connectOk ? 'wetu-connect-card--ok' : 'wetu-connect-card--off'); ?>">
        <i class="ion <?php echo e($connectOk ? 'ion-ios-checkmark-circle' : 'ion-ios-information-circle'); ?> wetu-connect-card__icon"></i>
        <div class="wetu-connect-card__body">
            <div class="wetu-connect-card__title">
                <?php echo e($connectOk ? 'Wetu Connect API active' : 'Wetu Connect API not configured'); ?>

            </div>
            <div class="wetu-connect-card__desc">
                <?php if($connectOk): ?>
                    Confirmed Tsoka bookings are pushed to Wetu automatically when you use
                    <em>Move to Bookings</em> in Tanova. Agents see them in their itinerary builder.
                <?php else: ?>
                    Add your Wetu Connect API key to enable pushing confirmed bookings back to Wetu.
                    <a href="<?php echo e(route('admin.integrations.category', 'exp_os')); ?>" style="font-weight:600">Configure in Exp OS →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
(function () {
    var tabs  = document.querySelectorAll('.wetu-tab');
    var rows  = document.querySelectorAll('#wetu-table tbody tr');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');
            var type = this.dataset.type;
            rows.forEach(function (row) {
                row.style.display = (type === 'all' || row.dataset.type === type) ? '' : 'none';
            });
        });
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Integrations/Views/admin/wetu/itineraries.blade.php ENDPATH**/ ?>