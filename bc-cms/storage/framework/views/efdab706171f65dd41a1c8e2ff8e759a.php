<?php $__env->startPush('css'); ?>
<style>
.tn-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px; }
@media(max-width:900px){ .tn-stats{ grid-template-columns:repeat(2,1fr); } }

.tp-stat { background:#fff; border:1px solid #ebebeb; border-radius:10px; padding:18px 20px; position:relative; overflow:hidden; }
.tp-stat::after { content:''; position:absolute; bottom:-14px; right:-14px; width:56px; height:56px; border-radius:50%; opacity:.4; }
.tp-stat--default::after { background:#e5e7eb; }
.tp-stat--green::after   { background:#bbf7d0; }
.tp-stat--amber::after   { background:#fef08a; }
.tp-stat--blue::after    { background:#bfdbfe; }
.tp-stat__label { font-size:11px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#aaa; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.tp-stat__amount { font-size:26px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; line-height:1; }
.tp-stat__amount.green { color:#16a34a; } .tp-stat__amount.amber { color:#d97706; } .tp-stat__amount.blue { color:#2563eb; }
.tp-stat__sub { font-size:11px; color:#bbb; margin-top:5px; }

.tn-gen-drawer { background:#fff; border:1px solid #ebebeb; border-radius:10px; padding:24px; margin-bottom:20px; display:none; }
.tn-gen-drawer.open { display:block; }
.tn-gen-drawer__title { font-size:14px; font-weight:700; color:#0a0a0a; margin-bottom:18px; }

.tp-field { margin-bottom:14px; }
.tp-field label { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#777; margin-bottom:4px; }
.tp-field input, .tp-field select, .tp-field textarea { width:100%; border:1.5px solid #e8e8e8; border-radius:7px; padding:8px 12px; font-size:13px; color:#222; background:#fff; outline:none; transition:border-color .12s; }
.tp-field input:focus, .tp-field select:focus { border-color:#0a0a0a; }

.tn-filter { display:flex; align-items:center; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.tn-search { display:flex; align-items:center; gap:8px; background:#fff; border:1.5px solid #e4e4e4; border-radius:8px; padding:0 12px; height:38px; flex:1; min-width:180px; max-width:260px; transition:border-color .15s; }
.tn-search:focus-within { border-color:#0a0a0a; }
.tn-search input { border:none; outline:none; background:transparent; font-size:13px; color:#333; width:100%; }
.tn-search input::placeholder { color:#bbb; }
.tn-search i { color:#ccc; }
.tn-chips { display:flex; gap:4px; flex-wrap:wrap; }
.tn-chip { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:100px; border:1.5px solid #e4e4e4; font-size:11px; font-weight:600; color:#555; background:#fff; cursor:pointer; text-decoration:none; transition:all .12s; white-space:nowrap; }
.tn-chip:hover { border-color:#0a0a0a; color:#0a0a0a; }
.tn-chip.active { background:#0a0a0a; border-color:#0a0a0a; color:#fff; }
.tn-chip .n { background:#f0f0f0; color:#888; min-width:18px; height:18px; border-radius:100px; display:inline-flex; align-items:center; justify-content:center; font-size:10px; padding:0 4px; }
.tn-chip.active .n { background:rgba(255,255,255,.2); color:#fff; }

.tp-card { background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; }
.tp-table { width:100%; border-collapse:collapse; }
.tp-table thead tr { background:#fafafa; border-bottom:1px solid #f0f0f0; }
.tp-table th { padding:10px 16px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#bbb; white-space:nowrap; }
.tp-table td { padding:14px 16px; font-size:13px; color:#222; border-bottom:1px solid #f7f7f7; }
.tp-table tbody tr:last-child td { border-bottom:none; }
.tp-table tbody tr:hover { background:#fafafa; }

.tp-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:100px; font-size:11px; font-weight:600; white-space:nowrap; }
.tp-badge::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
.tp-badge--green { background:#f0fdf4; color:#16a34a; } .tp-badge--green::before { background:#16a34a; }
.tp-badge--amber { background:#fffbeb; color:#d97706; } .tp-badge--amber::before { background:#d97706; }
.tp-badge--gray  { background:#f4f4f5; color:#71717a; } .tp-badge--gray::before  { background:#71717a; }

.tp-ab-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:7px; font-size:12px; font-weight:600; border:none; cursor:pointer; text-decoration:none !important; transition:all .12s; white-space:nowrap; }
.tp-ab-btn--primary { background:#0a0a0a; color:#fff !important; }
.tp-ab-btn--primary:hover { background:#333; }
.tp-ab-btn--outline { background:#fff; border:1.5px solid #e0e0e0; color:#0a0a0a !important; }
.tp-ab-btn--outline:hover { border-color:#0a0a0a; }
.tp-ab-btn--ghost  { background:transparent; color:#555 !important; border:1.5px solid #e4e4e4; }
.tp-ab-btn--ghost:hover  { border-color:#0a0a0a; color:#0a0a0a !important; }

.tp-empty { text-align:center; padding:56px 20px; color:#aaa; font-size:13px; }
.tp-empty i { font-size:2rem; display:block; margin-bottom:12px; color:#e0e0e0; }

.portal-eyebrow { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#aaa; margin-bottom:4px; }
.portal-h1 { font-size:24px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; margin:0 0 2px; }
.portal-h1 em { font-style:normal; font-weight:400; color:#aaa; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="portal-eyebrow">Trip Builder</p>
            <h1 class="portal-h1">Tanova <em>— <?php echo e($hours); ?>h window</em></h1>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" action="<?php echo e(route('admin.tanova.expire-old')); ?>">
                <?php echo csrf_field(); ?>
                <button class="tp-ab-btn tp-ab-btn--ghost"
                        onclick="return confirm('Delete all created trips older than <?php echo e($hours); ?>h?')">
                    <i class="ion ion-ios-trash"></i> Clear Expired
                </button>
            </form>
            <button class="tp-ab-btn tp-ab-btn--primary" id="tn-gen-toggle">
                <i class="ion ion-ios-add-circle"></i> Generate New Trip
            </button>
        </div>
    </div>

    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php
        $allTrips  = \Pro\Tanova\Models\TanovaTrip::recent($hours);
        $cntAll    = $allTrips->count();
        $cntCreate = (clone $allTrips)->where('status','created')->count();
        $cntBooked = (clone $allTrips)->where('status','booked')->count();
    ?>
    <div class="tn-stats">
        <div class="tp-stat tp-stat--default">
            <div class="tp-stat__label"><i class="ion ion-ios-list"></i> Total</div>
            <div class="tp-stat__amount"><?php echo e($cntAll); ?></div>
            <div class="tp-stat__sub">trips in last <?php echo e($hours); ?>h</div>
        </div>
        <div class="tp-stat tp-stat--amber">
            <div class="tp-stat__label"><i class="ion ion-ios-time"></i> Awaiting</div>
            <div class="tp-stat__amount amber"><?php echo e($cntCreate); ?></div>
            <div class="tp-stat__sub">not yet booked</div>
        </div>
        <div class="tp-stat tp-stat--green">
            <div class="tp-stat__label"><i class="ion ion-ios-checkmark-circle"></i> Booked</div>
            <div class="tp-stat__amount green"><?php echo e($cntBooked); ?></div>
            <div class="tp-stat__sub">moved to bookings</div>
        </div>
        <div class="tp-stat tp-stat--blue">
            <div class="tp-stat__label"><i class="ion ion-ios-clock"></i> Expiry Window</div>
            <div class="tp-stat__amount blue"><?php echo e($hours); ?>h</div>
            <div class="tp-stat__sub">auto-cleanup after</div>
        </div>
    </div>

    
    <div class="tn-gen-drawer" id="tn-gen-drawer">
        <div class="tn-gen-drawer__title">
            <i class="ion ion-ios-compass" style="color:#2563eb;margin-right:6px"></i>
            Generate Tanova Itinerary Packages
        </div>
        <form method="POST" action="<?php echo e(route('admin.tanova.generate')); ?>">
            <?php echo csrf_field(); ?>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#aaa;margin-bottom:10px">Guest Details</div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="tp-field">
                        <label>Guest Full Name <span style="color:#e11d48">*</span></label>
                        <input type="text" name="guest_name" required placeholder="e.g. Sarah Johnson"
                               value="<?php echo e(old('guest_name')); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tp-field">
                        <label>Guest Email <span style="color:#e11d48">*</span></label>
                        <input type="email" name="guest_email" required placeholder="sarah@example.com"
                               value="<?php echo e(old('guest_email')); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tp-field">
                        <label>Guest Phone</label>
                        <input type="text" name="guest_phone" placeholder="+263 77 123 4567"
                               value="<?php echo e(old('guest_phone')); ?>">
                    </div>
                </div>
            </div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#aaa;margin-bottom:10px">Trip Details</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="tp-field">
                        <label>Destination</label>
                        <select name="location_id" required>
                            <option value="">— Select destination —</option>
                            <?php $__currentLoopData = $places; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($pl['id']); ?>"><?php echo e($pl['name']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="tp-field">
                        <label>Start Date</label>
                        <input type="date" name="start_date" required value="<?php echo e(old('start_date')); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="tp-field">
                        <label>End Date</label>
                        <input type="date" name="end_date" required value="<?php echo e(old('end_date')); ?>">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="tp-field">
                        <label>Guests</label>
                        <input type="number" name="guests" value="<?php echo e(old('guests', 2)); ?>" min="1" max="50" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="tp-field">
                        <label>Budget (USD total)</label>
                        <input type="number" name="budget" value="<?php echo e(old('budget', 2000)); ?>" min="100" step="50" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="tp-field">
                        <label>Stay Type</label>
                        <select name="stay_type">
                            <option value="">Any</option>
                            <option value="room">Room / Lodge</option>
                            <option value="apartment">Apartment / Self-catering</option>
                        </select>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="tp-ab-btn tp-ab-btn--primary">
                        <i class="ion ion-ios-compass"></i> Generate Packages
                    </button>
                    <button type="button" class="tp-ab-btn tp-ab-btn--ghost" id="tn-gen-cancel">Cancel</button>
                </div>
            </div>
        </form>
    </div>

    
    <?php $curStatus = request('status',''); $curDest = request('destination',''); ?>
    <div class="tn-filter">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap" style="flex:1">
            <div class="tn-search">
                <i class="ion ion-ios-search"></i>
                <input type="text" name="destination" value="<?php echo e($curDest); ?>" placeholder="Search destination…">
                <?php if($curStatus): ?><input type="hidden" name="status" value="<?php echo e($curStatus); ?>"><?php endif; ?>
            </div>
            <div class="tn-chips">
                <a href="?" class="tn-chip <?php echo e($curStatus==='' ? 'active':''); ?>">
                    All <span class="n"><?php echo e($cntAll); ?></span>
                </a>
                <a href="?status=created" class="tn-chip <?php echo e($curStatus==='created' ? 'active':''); ?>">
                    Awaiting <span class="n"><?php echo e($cntCreate); ?></span>
                </a>
                <a href="?status=booked" class="tn-chip <?php echo e($curStatus==='booked' ? 'active':''); ?>">
                    Booked <span class="n"><?php echo e($cntBooked); ?></span>
                </a>
            </div>
            <?php if($curDest): ?>
            <button type="submit" class="tp-ab-btn tp-ab-btn--ghost">
                <i class="ion ion-ios-search"></i> Search
            </button>
            <?php endif; ?>
        </form>
    </div>

    
    <div class="tp-card">
        <table class="tp-table">
            <thead>
                <tr>
                    <th>Trip</th>
                    <th>Guest</th>
                    <th>Destination</th>
                    <th>Dates</th>
                    <th>Guests</th>
                    <th>Est. Price</th>
                    <th>Status</th>
                    <th>Generated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $trips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>
                        <a href="<?php echo e(route('admin.tanova.show', $trip)); ?>"
                           style="font-weight:600;color:#0a0a0a;text-decoration:none">
                            <?php echo e($trip->title); ?>

                        </a>
                    </td>
                    <td>
                        <?php if($trip->guest_name): ?>
                            <div style="font-weight:600;font-size:13px"><?php echo e($trip->guest_name); ?></div>
                            <?php if($trip->guest_email): ?>
                            <div style="font-size:11px;color:#aaa"><?php echo e($trip->guest_email); ?></div>
                            <?php endif; ?>
                            <?php if($trip->guest_phone): ?>
                            <div style="font-size:11px;color:#aaa"><?php echo e($trip->guest_phone); ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#ddd;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($trip->destination); ?></td>
                    <td>
                        <?php echo e($trip->start_date?->format('d M')); ?> → <?php echo e($trip->end_date?->format('d M Y')); ?>

                        <div style="font-size:11px;color:#aaa"><?php echo e($trip->nightCount()); ?> nights</div>
                    </td>
                    <td><?php echo e($trip->guests); ?></td>
                    <td style="font-weight:600">
                        <?php echo e(number_format($trip->estimated_price, 0)); ?>

                        <span style="font-size:11px;color:#aaa;font-weight:400"> <?php echo e($trip->currency); ?></span>
                    </td>
                    <td>
                        <?php if($trip->status === 'created'): ?>
                            <span class="tp-badge tp-badge--amber">Awaiting Action</span>
                        <?php elseif($trip->status === 'booked'): ?>
                            <span class="tp-badge tp-badge--green">Booked</span>
                        <?php else: ?>
                            <span class="tp-badge tp-badge--gray"><?php echo e(ucfirst($trip->status)); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#aaa;font-size:12px"><?php echo e($trip->created_at?->diffForHumans()); ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?php echo e(route('admin.tanova.show', $trip)); ?>" class="tp-ab-btn tp-ab-btn--outline">
                                View
                            </a>
                            <?php if($trip->isMovable()): ?>
                            <form method="POST" action="<?php echo e(route('admin.tanova.move', $trip)); ?>" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <button class="tp-ab-btn tp-ab-btn--primary"
                                        onclick="return confirm('Move this trip to Bookings?')">
                                    Book
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="9">
                        <div class="tp-empty">
                            <i class="ion ion-ios-compass"></i>
                            No Tanova trips in the last <?php echo e($hours); ?> hours.
                            <div style="margin-top:12px">
                                <button class="tp-ab-btn tp-ab-btn--primary" id="tn-gen-toggle-empty">
                                    <i class="ion ion-ios-add-circle"></i> Generate First Trip
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if($trips->hasPages()): ?>
        <div style="padding:14px 16px;border-top:1px solid #f0f0f0"><?php echo e($trips->withQueryString()->links()); ?></div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
(function(){
    var drawer = document.getElementById('tn-gen-drawer');
    function open() { drawer.classList.add('open'); drawer.scrollIntoView({behavior:'smooth',block:'nearest'}); }
    function close() { drawer.classList.remove('open'); }
    var t = document.getElementById('tn-gen-toggle');
    var te = document.getElementById('tn-gen-toggle-empty');
    var c = document.getElementById('tn-gen-cancel');
    if(t)  t.addEventListener('click', open);
    if(te) te.addEventListener('click', open);
    if(c)  c.addEventListener('click', close);
    <?php if($errors->any()): ?> open(); <?php endif; ?>
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Tanova/Views/admin/index.blade.php ENDPATH**/ ?>