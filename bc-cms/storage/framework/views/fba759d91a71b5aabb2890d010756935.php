<?php $__env->startSection('title', 'Integrations & Channels'); ?>

<?php $__env->startSection('content'); ?>
<div class="integrations-page">
    <div class="page-header">
        <h1>Integrations & Channels</h1>
        <p>Connect your business channels to the Tanova chatbot</p>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="integrations-grid">
        <?php $__currentLoopData = ['whatsapp', 'facebook', 'telegram']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $channel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $integration = $integrations[$channel] ?? [];
                $connected = $integration['connected'] ?? false;
                $enabled = $integration['enabled'] ?? false;
            ?>

            <div class="integration-card <?php if($connected): ?> connected <?php endif; ?>">
                <div class="card-icon" style="color: <?php echo e($integration['color'] ?? '#666'); ?>">
                    <i class="icon-<?php echo e($integration['icon'] ?? 'link'); ?>"></i>
                </div>

                <div class="card-content">
                    <h3><?php echo e($integration['name']); ?></h3>
                    <p><?php echo e($integration['description']); ?></p>

                    <div class="card-status">
                        <?php if($connected): ?>
                            <span class="badge badge-success">
                                <i class="icon-check"></i> Connected
                            </span>
                        <?php else: ?>
                            <span class="badge badge-secondary">
                                Not Connected
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if($connected): ?>
                        <div class="card-info">
                            <?php if($channel === 'whatsapp' && $integration['phone']): ?>
                                <small><strong>Phone:</strong> <?php echo e($integration['phone']); ?></small>
                            <?php elseif($channel === 'facebook' && $integration['page_id']): ?>
                                <small><strong>Page ID:</strong> <?php echo e($integration['page_id']); ?></small>
                            <?php elseif($channel === 'telegram'): ?>
                                <small><strong>Status:</strong> Bot configured</small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-actions">
                    <?php if(!$connected): ?>
                        <a href="<?php echo e(route('user.integrations.' . $channel)); ?>" class="btn btn-primary btn-sm">
                            Setup
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary btn-sm"
                                onclick="testConnection('<?php echo e($channel); ?>')">
                            Test
                        </button>
                        <a href="<?php echo e(route('user.integrations.' . $channel)); ?>" class="btn btn-outline-primary btn-sm">
                            Edit
                        </a>
                        <button class="btn btn-outline-danger btn-sm"
                                onclick="disconnectChannel('<?php echo e($channel); ?>')">
                            Disconnect
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <!-- Documentation Links -->
    <div class="documentation-section">
        <h2>Setup Guides</h2>
        <div class="docs-grid">
            <div class="doc-card">
                <h4>📱 WhatsApp Business</h4>
                <p>Connect your WhatsApp Business account to receive messages from customers</p>
                <ol>
                    <li>Go to <a href="https://business.facebook.com" target="_blank">business.facebook.com</a></li>
                    <li>Create WhatsApp Business app</li>
                    <li>Get your credentials</li>
                    <li>Click "Setup" button above</li>
                </ol>
                <a href="<?php echo e(route('user.integrations.whatsapp')); ?>" class="btn btn-sm btn-outline-primary">
                    View Full Guide →
                </a>
            </div>

            <div class="doc-card">
                <h4>👥 Facebook Messenger</h4>
                <p>Connect your Facebook Page to chat with customers in Messenger</p>
                <ol>
                    <li>Go to <a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a></li>
                    <li>Create a new App</li>
                    <li>Connect your Facebook Page</li>
                    <li>Click "Setup" button above</li>
                </ol>
                <a href="<?php echo e(route('user.integrations.facebook')); ?>" class="btn btn-sm btn-outline-primary">
                    View Full Guide →
                </a>
            </div>

            <div class="doc-card">
                <h4>🤖 Telegram Bot</h4>
                <p>Create a Telegram bot to chat with customers on Telegram</p>
                <ol>
                    <li>Open Telegram</li>
                    <li>Search for @BotFather</li>
                    <li>Create your bot with /newbot</li>
                    <li>Click "Setup" button above</li>
                </ol>
                <a href="<?php echo e(route('user.integrations.telegram')); ?>" class="btn btn-sm btn-outline-primary">
                    View Full Guide →
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Disconnect Modal -->
<div class="modal fade" id="disconnectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Disconnect Channel?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure? You won't receive messages from this channel anymore.</p>
                <p><strong>Existing conversations will be preserved</strong> in your Concierge dashboard.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="disconnectForm" method="POST" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="channel" id="disconnectChannel">
                    <button type="submit" class="btn btn-danger">Disconnect</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.integrations-page {
    padding: 20px;
}

.page-header {
    margin-bottom: 40px;
}

.page-header h1 {
    margin: 0 0 10px 0;
    color: #333;
}

.page-header p {
    color: #666;
    margin: 0;
}

.integrations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 50px;
}

.integration-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.integration-card:hover {
    border-color: #FF6B35;
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.15);
}

.integration-card.connected {
    border-color: #4CAF50;
    background: #F1F8F5;
}

.card-icon {
    font-size: 36px;
    margin-bottom: 15px;
}

.card-content {
    flex: 1;
}

.card-content h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #333;
}

.card-content p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 13px;
    line-height: 1.5;
}

.card-status {
    margin-bottom: 15px;
}

.card-info {
    padding: 10px;
    background: rgba(0,0,0,0.02);
    border-radius: 4px;
    margin-bottom: 15px;
}

.card-info small {
    display: block;
    color: #666;
    font-size: 12px;
}

.card-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.card-actions .btn {
    flex: 1;
    min-width: 80px;
    font-size: 12px;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.badge-success {
    background: #4CAF50;
    color: white;
}

.badge-secondary {
    background: #e0e0e0;
    color: #666;
}

.documentation-section {
    margin-top: 50px;
}

.documentation-section h2 {
    margin-bottom: 30px;
    color: #333;
}

.docs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.doc-card {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
}

.doc-card h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.doc-card p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 13px;
}

.doc-card ol {
    margin: 0 0 15px 20px;
    padding: 0;
    color: #666;
    font-size: 13px;
}

.doc-card li {
    margin-bottom: 5px;
}

.doc-card a {
    color: #FF6B35;
    text-decoration: none;
}

.doc-card a:hover {
    text-decoration: underline;
}
</style>

<script>
function disconnectChannel(channel) {
    document.getElementById('disconnectChannel').value = channel;
    const modal = new bootstrap.Modal(document.getElementById('disconnectModal'));
    modal.show();
}

function testConnection(channel) {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = 'Testing...';

    fetch('<?php echo e(route("user.integrations.test")); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ channel: channel })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = 'Test';

        const alertClass = data.success ? 'alert-success' : 'alert-danger';
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show`;
        alert.innerHTML = `
            ${data.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const page = document.querySelector('.integrations-page');
        page.insertBefore(alert, page.firstChild);

        setTimeout(() => alert.remove(), 5000);
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = 'Test';
        console.error('Error:', error);
    });
}

document.getElementById('disconnectForm').addEventListener('submit', function(e) {
    const channel = document.getElementById('disconnectChannel').value;
    this.action = `/vendor/integrations/${channel}/disconnect`;
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('vendor.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/frontend/integrations/index.blade.php ENDPATH**/ ?>