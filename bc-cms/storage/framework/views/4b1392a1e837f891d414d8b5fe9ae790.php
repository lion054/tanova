<?php $__env->startSection('content'); ?>
<style>
/* ── Base ────────────────────────────────────────────── */
.tp * { box-sizing: border-box; }
.tp-back {
    display: inline-flex; align-items: center; gap: 6px; font-size: 12px;
    color: #aaa; text-decoration: none; margin-bottom: 20px; transition: color .12s;
}
.tp-back:hover { color: #0a0a0a; }

/* ── Page header ─────────────────────────────────────── */
.tp-view-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; margin-bottom: 20px; flex-wrap: wrap;
}
.tp-view-top-left { display: flex; align-items: center; gap: 14px; }
.tp-view-ref { font-size: 22px; font-weight: 800; color: #0a0a0a; letter-spacing: -.03em; }
.tp-view-meta { font-size: 12px; color: #aaa; margin-top: 3px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.tp-view-meta span { display: flex; align-items: center; gap: 4px; }

/* ── Status pill ─────────────────────────────────────── */
.tp-status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 100px; font-size: 12px; font-weight: 700;
    letter-spacing: .02em; flex-shrink: 0;
}
.tp-status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .5; }
.tp-pill-draft     { background: #f4f4f5; color: #71717a; }
.tp-pill-sent      { background: #eff6ff; color: #2563eb; }
.tp-pill-paid      { background: #f0fdf4; color: #16a34a; }
.tp-pill-accepted  { background: #f0fdf4; color: #16a34a; }
.tp-pill-cancelled { background: #fff1f2; color: #e11d48; }
.tp-pill-expired   { background: #fffbeb; color: #d97706; }

/* ── Action bar ──────────────────────────────────────── */
.tp-action-bar {
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    padding: 10px 14px; margin-bottom: 22px;
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.tp-action-divider {
    width: 1px; height: 28px; background: #ebebeb; flex-shrink: 0; margin: 0 2px;
}

/* Button variants */
.tp-ab-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 15px; border-radius: 7px; font-size: 12px; font-weight: 600;
    border: none; cursor: pointer; text-decoration: none !important;
    transition: all .12s; white-space: nowrap; letter-spacing: -.01em;
}
/* Primary */
.tp-ab-primary { background: #0a0a0a; color: #fff !important; }
.tp-ab-primary:hover { background: #222; }
/* Outlined */
.tp-ab-outline { background: #fff; color: #333 !important; border: 1.5px solid #e4e4e4; }
.tp-ab-outline:hover { border-color: #0a0a0a; color: #0a0a0a !important; }
/* WhatsApp */
.tp-ab-wa { background: #25d366; color: #fff !important; }
.tp-ab-wa:hover { background: #1ebe5d; }
/* Paid */
.tp-ab-paid { background: #f0fdf4; color: #16a34a !important; border: 1.5px solid #bbf7d0; }
.tp-ab-paid:hover { background: #dcfce7; border-color: #86efac; }

/* ── Document frame ──────────────────────────────────── */
.tp-doc-frame {
    background: #f2f2f2;
    background-image: radial-gradient(circle at 1px 1px, #ddd 1px, transparent 0);
    background-size: 20px 20px;
    padding: 40px 32px; border-radius: 12px;
    display: flex; justify-content: center; align-items: flex-start;
}
.tp-doc-inner {
    width: 100%; max-width: 720px; background: #fff;
    border-radius: 6px; overflow: hidden;
    box-shadow: 0 4px 32px rgba(0,0,0,.13), 0 1px 4px rgba(0,0,0,.06);
}

/* ── Toast ───────────────────────────────────────────── */
.tp-toast {
    position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(12px);
    background: #0a0a0a; color: #fff; padding: 11px 22px; border-radius: 100px;
    font-size: 13px; font-weight: 600; z-index: 99999; white-space: nowrap;
    opacity: 0; transition: opacity .25s, transform .25s; pointer-events: none;
}
.tp-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

/* ── Modals ──────────────────────────────────────────── */
.tp-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4);
    z-index: 9999; align-items: center; justify-content: center;
    backdrop-filter: blur(2px);
}
.tp-modal-overlay.open { display: flex; }
.tp-modal {
    background: #fff; border-radius: 12px; width: 100%; max-width: 420px;
    padding: 28px; box-shadow: 0 16px 56px rgba(0,0,0,.2); position: relative;
    animation: tp-modal-in .18s ease;
}
@keyframes tp-modal-in {
    from { opacity:0; transform:scale(.97) translateY(6px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}
.tp-modal-icon {
    width: 44px; height: 44px; border-radius: 10px; display: flex;
    align-items: center; justify-content: center; font-size: 20px; margin-bottom: 14px;
}
.tp-modal-icon.email { background: #eff6ff; color: #2563eb; }
.tp-modal-icon.wa    { background: #f0fdf4; color: #25d366; }
.tp-modal h3 { font-size: 16px; font-weight: 800; color: #0a0a0a; margin: 0 0 5px; letter-spacing: -.02em; }
.tp-modal p  { font-size: 13px; color: #888; margin: 0 0 20px; line-height: 1.5; }
.tp-modal label {
    display: block; font-size: 11px; font-weight: 700; color: #777;
    text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px;
}
.tp-modal input {
    width: 100%; padding: 10px 13px; border: 1.5px solid #e4e4e4;
    border-radius: 7px; font-size: 14px; color: #0a0a0a; outline: none;
    margin-bottom: 18px; transition: border-color .15s, box-shadow .15s;
}
.tp-modal input:focus { border-color: #0a0a0a; box-shadow: 0 0 0 3px rgba(10,10,10,.06); }
.tp-modal-error {
    display: none; font-size: 12px; color: #e11d48; background: #fff1f2;
    border-radius: 6px; padding: 9px 12px; margin-bottom: 14px; line-height: 1.5;
}
.tp-modal-actions { display: flex; gap: 8px; justify-content: flex-end; }
.tp-modal-cancel {
    padding: 9px 18px; border: 1.5px solid #e4e4e4; border-radius: 7px;
    font-size: 13px; font-weight: 600; color: #555; background: #fff;
    cursor: pointer; transition: all .12s;
}
.tp-modal-cancel:hover { border-color: #aaa; color: #0a0a0a; }
.tp-modal-send {
    padding: 9px 20px; border: none; border-radius: 7px;
    font-size: 13px; font-weight: 700; cursor: pointer; transition: all .12s;
    display: inline-flex; align-items: center; gap: 7px;
}
.tp-modal-send.email { background: #0a0a0a; color: #fff; }
.tp-modal-send.email:hover { background: #222; }
.tp-modal-send.wa    { background: #25d366; color: #fff; }
.tp-modal-send.wa:hover { background: #1ebe5d; }
.tp-modal-send:disabled { opacity: .6; cursor: not-allowed; }
</style>

<div class="tp">

<a href="<?php echo e(route('tourpay.vendor.index')); ?>" class="tp-back">
    <i class="icofont-arrow-left"></i> <?php echo e(__("Back to TourPay")); ?>

</a>


<div class="tp-view-top">
    <div class="tp-view-top-left">
        <div>
            <div class="tp-view-ref"><?php echo e($row->invoice_number); ?></div>
            <div class="tp-view-meta">
                <span><i class="icofont-file-document"></i> <?php echo e(ucfirst($row->type)); ?></span>
                <span>·</span>
                <span><i class="icofont-calendar"></i> <?php echo e($row->created_at->format('d M Y')); ?></span>
                <?php if($row->client_name): ?>
                <span>·</span>
                <span><i class="icofont-user"></i> <?php echo e($row->client_name); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="tp-status-pill tp-pill-<?php echo e($row->status); ?>"><?php echo e(ucfirst($row->status)); ?></div>
    </div>
    <div style="font-size:20px;font-weight:800;color:#0a0a0a;letter-spacing:-.03em;white-space:nowrap;">
        <span style="font-size:13px;font-weight:600;color:#aaa;margin-right:3px;vertical-align:.15em;"><?php echo e($row->currency); ?></span><?php echo e(number_format($row->total, 2)); ?>

    </div>
</div>

<?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<div class="tp-action-bar">

    
    <a href="<?php echo e(route('tourpay.vendor.pdf', $row->id)); ?>" class="tp-ab-btn tp-ab-primary" target="_blank">
        <i class="icofont-download"></i> <?php echo e(__("Download PDF")); ?>

    </a>

    <div class="tp-action-divider"></div>

    
    <button type="button" class="tp-ab-btn tp-ab-outline" onclick="openModal('email')">
        <i class="icofont-envelope"></i> <?php echo e(__("Email")); ?>

    </button>
    <button type="button" class="tp-ab-btn tp-ab-wa" onclick="openModal('wa')">
        <i class="icofont-brand-whatsapp"></i> <?php echo e(__("WhatsApp")); ?>

    </button>
    <button type="button" class="tp-ab-btn tp-ab-outline" onclick="copyPayLink()">
        <i class="icofont-link"></i> <?php echo e(__("Copy Link")); ?>

    </button>

    <div class="tp-action-divider"></div>

    
    <a href="<?php echo e(route('tourpay.vendor.edit', $row->id)); ?>" class="tp-ab-btn tp-ab-outline">
        <i class="icofont-edit"></i> <?php echo e(__("Edit")); ?>

    </a>

    <?php if(!in_array($row->status, ['paid','cancelled'])): ?>
    <form method="POST" action="<?php echo e(route('tourpay.vendor.mark-paid', $row->id)); ?>" style="display:contents;"
          onsubmit="return confirm('<?php echo e(__('Mark this invoice as paid?')); ?>')">
        <?php echo csrf_field(); ?>
        <button type="submit" class="tp-ab-btn tp-ab-paid">
            <i class="icofont-check-circled"></i> <?php echo e(__("Mark as Paid")); ?>

        </button>
    </form>
    <?php endif; ?>

</div>


<div class="tp-doc-frame">
    <div class="tp-doc-inner">
        <?php echo $__env->make('TourPay::pdf.template' . max(1, min(5, (int) $row->template)), ['row' => $row, 'preview' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>

</div>

<div class="tp-toast" id="tp-toast"></div>


<div class="tp-modal-overlay" id="modal-email" onclick="closeOnBg(event,'email')">
    <div class="tp-modal">
        <div class="tp-modal-icon email"><i class="icofont-envelope"></i></div>
        <h3><?php echo e(__("Send by Email")); ?></h3>
        <p><?php echo e(__("Invoice :n will be sent with a PDF attachment.", ['n' => $row->invoice_number])); ?></p>
        <form method="POST" action="<?php echo e(route('tourpay.vendor.send-email', $row->id)); ?>">
            <?php echo csrf_field(); ?>
            <label><?php echo e(__("Recipient Email")); ?></label>
            <input type="email" name="email" id="modal-email-input" value="<?php echo e($row->client_email); ?>" required placeholder="client@example.com">
            <div class="tp-modal-actions">
                <button type="button" class="tp-modal-cancel" onclick="closeModal('email')"><?php echo e(__("Cancel")); ?></button>
                <button type="submit" class="tp-modal-send email"><i class="icofont-send-mail"></i> <?php echo e(__("Send Email")); ?></button>
            </div>
        </form>
    </div>
</div>


<div class="tp-modal-overlay" id="modal-wa" onclick="closeOnBg(event,'wa')">
    <div class="tp-modal">
        <div class="tp-modal-icon wa"><i class="icofont-brand-whatsapp"></i></div>
        <h3><?php echo e(__("Send via WhatsApp")); ?></h3>
        <p><?php echo e(__("Sends the invoice link directly to the client via Vonage WhatsApp.")); ?></p>
        <label><?php echo e(__("WhatsApp Number")); ?> <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;font-size:10px;">+27831234567</span></label>
        <input type="tel" id="modal-wa-input" value="<?php echo e($row->client_phone); ?>" placeholder="+27831234567">
        <div class="tp-modal-error" id="wa-error"></div>
        <div class="tp-modal-actions">
            <button type="button" class="tp-modal-cancel" onclick="closeModal('wa')"><?php echo e(__("Cancel")); ?></button>
            <button type="button" class="tp-modal-send wa" id="wa-send-btn" onclick="sendWhatsApp()">
                <i class="icofont-brand-whatsapp"></i> <span id="wa-send-label"><?php echo e(__("Send")); ?></span>
            </button>
        </div>
    </div>
</div>

<script>
var payUrl = '<?php echo e(route('tourpay.pay', $row->pay_token)); ?>';

/* ── Modal helpers ── */
function openModal(type) {
    document.getElementById('modal-' + type).classList.add('open');
    var inp = document.getElementById('modal-' + type + '-input');
    if (inp) { setTimeout(function(){ inp.focus(); inp.select(); }, 80); }
}
function closeModal(type) {
    document.getElementById('modal-' + type).classList.remove('open');
}
function closeOnBg(e, type) {
    if (e.target === document.getElementById('modal-' + type)) closeModal(type);
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeModal('email'); closeModal('wa'); }
});

/* ── WhatsApp via Vonage ── */
function sendWhatsApp() {
    var phone  = document.getElementById('modal-wa-input').value.trim();
    var errBox = document.getElementById('wa-error');
    var btn    = document.getElementById('wa-send-btn');
    var label  = document.getElementById('wa-send-label');

    errBox.style.display = 'none';
    if (!phone) { document.getElementById('modal-wa-input').focus(); return; }

    btn.disabled = true;
    label.textContent = '<?php echo e(__("Sending…")); ?>';

    fetch('<?php echo e(route("tourpay.vendor.send-whatsapp", $row->id)); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
        body: JSON.stringify({ phone: phone })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        label.textContent = '<?php echo e(__("Send")); ?>';
        if (data.success) {
            closeModal('wa');
            showToast('<?php echo e(__("WhatsApp message sent!")); ?>');
        } else {
            errBox.textContent = data.error || '<?php echo e(__("Failed to send. Please try again.")); ?>';
            errBox.style.display = 'block';
        }
    })
    .catch(function() {
        btn.disabled = false;
        label.textContent = '<?php echo e(__("Send")); ?>';
        errBox.textContent = '<?php echo e(__("Network error. Please try again.")); ?>';
        errBox.style.display = 'block';
    });
}

/* ── Copy link ── */
function copyPayLink() {
    var copy = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function(){ showToast('<?php echo e(__("Link copied to clipboard!")); ?>'); });
        } else {
            var ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); document.body.removeChild(ta);
            showToast('<?php echo e(__("Link copied to clipboard!")); ?>');
        }
    };
    copy(payUrl);
}

/* ── Toast ── */
function showToast(msg) {
    var t = document.getElementById('tp-toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function(){ t.classList.remove('show'); }, 2800);
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/TourPay/Views/frontend/view.blade.php ENDPATH**/ ?>