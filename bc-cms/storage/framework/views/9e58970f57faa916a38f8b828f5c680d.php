<div class="panel">
    <div class="panel-title"><strong><?php echo e(__('Locations')); ?></strong></div>
    <div class="panel-body">
        <?php if(is_default_lang()): ?>
            <div class="form-group">
                <label class="control-label"><?php echo e(__('Location')); ?></label>
                <div class="">
                    <select name="location_id" class="form-control">
                        <option value=""><?php echo e(__('-- Please Select --')); ?></option>
                        <?php
                        $traverse = function ($locations, $prefix = '') use (&$traverse, $row) {
                            foreach ($locations as $location) {
                                $selected = '';
                                if ($row->location_id == $location->id) {
                                    $selected = 'selected';
                                }
                                printf("<option value='%s' %s>%s</option>", $location->id, $selected, $prefix . ' ' . $location->name);
                                $traverse($location->children, $prefix . '-');
                            }
                        };
                        $traverse($job_location);
                        ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label class="control-label"><?php echo e(__('Real address')); ?></label>
            <input type="text" name="address" class="form-control" placeholder="<?php echo e(__('Real address')); ?>"
                value="<?php echo e($translation->address); ?>">
        </div>
        <?php if(is_default_lang()): ?>
            <div class="form-group">
                <label class="control-label"><?php echo e(__('Map Engine')); ?></label>
                <div class="control-map-group">
                    <div id="map_content"></div>
                    <input type="text" placeholder="<?php echo e(__('Search by name...')); ?>" class="bc_searchbox form-control"
                        autocomplete="off" onkeydown="return event.key !== 'Enter';">
                    <div class="g-control">
                        <div class="form-group">
                            <label><?php echo e(__('Map Lat')); ?>:</label>
                            <input type="text" name="map_lat" class="form-control" value="<?php echo e($row->map_lat); ?>"
                                onkeydown="return event.key !== 'Enter';">
                        </div>
                        <div class="form-group">
                            <label><?php echo e(__('Map Lng')); ?>:</label>
                            <input type="text" name="map_lng" class="form-control" value="<?php echo e($row->map_lng); ?>"
                                onkeydown="return event.key !== 'Enter';">
                        </div>
                        <div class="form-group">
                            <label><?php echo e(__('Map Zoom')); ?>:</label>
                            <input type="text" name="map_zoom" class="form-control"
                                value="<?php echo e($row->map_zoom ?? '8'); ?>" onkeydown="return event.key !== 'Enter';">
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Course/Views/admin/course/location.blade.php ENDPATH**/ ?>