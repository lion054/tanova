 <?php if(is_default_lang()): ?>
<?php $languages = \Modules\Language\Models\Language::getActive(); ?>

    <hr>
    <div class="panel">
        <div class="panel-title"><strong><?php echo e(__("Form Search Fields")); ?></strong></div>
        <div class="panel-body">
            <div class="form-group" >
                <label class="" ><?php echo e(__("Search Criteria")); ?></label>
                <div class="form-controls">
                    <div class="form-group-item">
                        <div class="g-items-header">
                            <div class="row">
                                <div class="col-md-7"><?php echo e(__("Search Field")); ?></div>
                                <div class="col-md-4"><?php echo e(__("Order")); ?></div>
                                <div class="col-md-1"></div>
                            </div>
                        </div>
                        <div class="g-items">
                            <?php
                            $property_search_fields = setting_item_array('property_search_fields');
                            $attributes = \Modules\Core\Models\Attributes::where('service', 'property')->with(['terms','translation'])->get();
                            $types = [
                                'service_name'=>__("Service name"),
                                'location'=>__("Location"),
                                'category'=>__("Category"),
                                'property_type'=>__("Property Type"),
                                'bathrooms'=>__("Bathrooms"),
                                'bedrooms'=>__("Bedrooms"),
                                'garages'=>__("Garages"),
                                'price'=>__("Price"),
                                'year_built'=>__("Year Built")
                            ];
                            foreach($attributes as $attr) {
                                $types[$attr->slug.'|'.$attr->id] = $attr->name;
                            }
                            ?>
                            <?php $__currentLoopData = $property_search_fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="item" data-number="<?php echo e($key); ?>">
                                    <div class="row">
                                        <div class="col-md-7">
                                            <?php if(!empty($languages) && setting_item('site_enable_multi_lang') && setting_item('site_locale')): ?>
                                                <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php $key_lang = setting_item('site_locale') != $language->locale ? "_".$language->locale : ""   ?>
                                                    <div class="g-lang">
                                                        <div class="title-lang"><?php echo e($language->name); ?></div>
                                                        <input type="text" name="property_search_fields[<?php echo e($key); ?>][title<?php echo e($key_lang); ?>]" value="<?php echo e($item['title'.$key_lang] ?? ''); ?>" class="form-control">
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php else: ?>
                                                <input type="text" name="property_search_fields[<?php echo e($key); ?>][title]" value="<?php echo e($item['title'] ?? ""); ?>" class="form-control">

                                            <?php endif; ?>
                                            <select name="property_search_fields[<?php echo e($key); ?>][field]" class="custom-select">
                                                <option value=""><?php echo e(__("-- Select field type --")); ?></option>
                                                <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type=>$name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option <?php if($item['field'] == $type): ?> selected <?php endif; ?> value="<?php echo e($type); ?>"><?php echo e($name); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" name="property_search_fields[<?php echo e($key); ?>][position]" min="0" value="<?php echo e($item['position'] ?? 0); ?>" class="form-control">
                                        </div>
                                        <div class="col-md-1">
                                            <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <div class="text-right">
                            <span class="btn btn-info btn-sm btn-add-item"><i class="icon ion-ios-add-circle-outline"></i> <?php echo e(__('Add item')); ?></span>
                        </div>
                        <div class="g-more hide">
                            <div class="item" data-number="__number__">
                                <div class="row">
                                    <div class="col-md-7">
                                        <?php if(!empty($languages) && setting_item('site_enable_multi_lang') && setting_item('site_locale')): ?>
                                            <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php $key_lang = setting_item('site_locale') != $language->locale ? "_".$language->locale : ""   ?>
                                                <div class="g-lang">
                                                    <div class="title-lang"><?php echo e($language->name); ?></div>
                                                    <input type="text" __name__="property_search_fields[__number__][title<?php echo e($key_lang); ?>]" value="" class="form-control">

                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php else: ?>
                                            <input type="text" __name__="property_search_fields[__number__][title]" value="" class="form-control">

                                        <?php endif; ?>
                                        <select __name__="property_search_fields[__number__][field]" class="custom-select">
                                            <option value=""><?php echo e(__("-- Select field type --")); ?></option>
                                            <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type=>$name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($type); ?>"><?php echo e($name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="number" __name__="property_search_fields[__number__][position]" min="0"  class="form-control">
                                    </div>
                                    <div class="col-md-1">
                                        <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 <?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Property/Views/admin/settings/form-search.blade.php ENDPATH**/ ?>