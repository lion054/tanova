<?php if(is_default_lang()): ?>
<div class="panel">
    <div class="panel-title"><strong><?php echo e(__('Tanova Scheduling')); ?></strong></div>
    <div class="panel-body">

        <div class="form-group">
            <label class="control-label"><?php echo e(__('Duration (hours)')); ?></label>
            <input type="number" name="duration" min="0.5" max="24" step="0.5"
                   value="<?php echo e($row->duration ?? ''); ?>" class="form-control" style="max-width:120px"
                   placeholder="e.g. 3">
            <small class="form-text text-muted">Total hours from start to end including transfers.</small>
        </div>

        <div class="form-group">
            <label class="control-label"><?php echo e(__('Time Slot')); ?></label>
            <select name="time_slot" class="form-control" style="max-width:220px">
                <option value="">-- Select --</option>
                <option value="1" <?php if(($row->time_slot ?? '') == 1): ?> selected <?php endif; ?>>1 — Morning (07:00–12:00)</option>
                <option value="2" <?php if(($row->time_slot ?? '') == 2): ?> selected <?php endif; ?>>2 — Afternoon (12:00–17:00)</option>
                <option value="3" <?php if(($row->time_slot ?? '') == 3): ?> selected <?php endif; ?>>3 — Evening / Sunset (17:00+)</option>
            </select>
            <small class="form-text text-muted">When in the day this activity operates. Tanova will not double-book the same slot.</small>
        </div>

        <div class="form-group">
            <label class="control-label"><?php echo e(__('Zone')); ?></label>
            <select name="zone" class="form-control" style="max-width:260px">
                <option value="">-- Select --</option>
                <option value="1" <?php if(($row->zone ?? '') == 1): ?> selected <?php endif; ?>>1 — Must Do</option>
                <option value="2" <?php if(($row->zone ?? '') == 2): ?> selected <?php endif; ?>>A</option>
                <option value="3" <?php if(($row->zone ?? '') == 3): ?> selected <?php endif; ?>>B</option>
                <option value="4" <?php if(($row->zone ?? '') == 4): ?> selected <?php endif; ?>>C</option>
                <option value="5" <?php if(($row->zone ?? '') == 5): ?> selected <?php endif; ?>>D</option>
                <option value="6" <?php if(($row->zone ?? '') == 6): ?> selected <?php endif; ?>>E</option>
                <option value="7" <?php if(($row->zone ?? '') == 7): ?> selected <?php endif; ?>>F</option>
                <option value="8" <?php if(($row->zone ?? '') == 8): ?> selected <?php endif; ?>>G</option>
                <option value="9" <?php if(($row->zone ?? '') == 9): ?> selected <?php endif; ?>>H</option>
            </select>
            <small class="form-text text-muted">
                Tanova groups same-zone activities on the same day. Zone 1 (Must Do) appears in every itinerary.
                Zones A–H are geographic areas — guests won't cross town twice in a day.
            </small>
        </div>

    </div>
</div>

<div class="panel">
    <div class="panel-title"><strong><?php echo e(__('Tanova Rules')); ?></strong></div>
    <div class="panel-body">
        <div class="alert alert-info" style="font-size:13px">
            <strong>How Tanova uses these fields:</strong><br>
            <ul style="margin:6px 0 0 16px;padding:0">
                <li><strong>Duration</strong> — prevents over-scheduling a day (e.g. 7h rafting fills morning + afternoon).</li>
                <li><strong>Time Slot</strong> — prevents double-booking the same part of the day.</li>
                <li><strong>Zone</strong> — groups nearby activities together; Zone 1 is always included.</li>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Tour/Views/admin/tour/tanova.blade.php ENDPATH**/ ?>