<div class="form-group">
    <label><?php echo e(__("Name")); ?></label>
    <input type="text" value="<?php echo e($translation->name); ?>" placeholder="<?php echo e(__("Category name")); ?>" name="name" class="form-control">
</div>
<?php if(is_default_lang()): ?>
    <div class="form-group">
        <label><?php echo e(__("Parent")); ?></label>
        <select name="parent_id" class="form-control">
            <option value=""><?php echo e(__("-- Please Select --")); ?></option>
            <?php
            $traverse = function ($categories, $prefix = '') use (&$traverse, $row) {
                foreach ($categories as $category) {
                    if ($category->id == $row->id) {
                        continue;
                    }
                    $selected = '';
                    if ($row->parent_id == $category->id)
                        $selected = 'selected';
                    printf("<option value='%s' %s>%s</option>", $category->id, $selected, $prefix . ' ' . $category->name);
                    $traverse($category->children, $prefix . '-');
                }
            };
            $traverse($parents);
            ?>
        </select>
    </div>
<?php endif; ?>
<div class="form-group">
    <label><?php echo e(__('Class Icon')); ?> - <?php echo __("get icon in <a href=':link_1' target='_blank'>fontawesome.com</a> or <a href=':link_2' target='_blank'>icofont.com</a>",['link_1'=>'https://fontawesome.com/v4.7.0/icons/','link_2'=>'https://icofont.com/icons']); ?></label>
    <input type="text" value="<?php echo e($row->icon); ?>" placeholder="<?php echo e(__("Ex: fa fa-facebook")); ?>" name="icon" class="form-control">
</div>


    
    
        
    

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Property/Views/admin/category/form.blade.php ENDPATH**/ ?>