<div class="topic-sidebars list-cats widget_category">
    <a
        href="<?php echo e(route('support.ticket.index')); ?>"
        class="mb-4 btn-block btn btn-primary btn-lg">
        <i class="fa fa-question-circle-o"></i> <?php echo e(__("Support tickets")); ?></a>
    <div class="widget mb-5">
        <div class="widget-title">
            <h4><?php echo e(__("Categories")); ?></h4>
        </div>
        <ul>
            <li>
                <a href="<?php echo e(route('support.topic.index')); ?>">
                    <span></span> <?php echo e(__("All categories")); ?></a>
            </li>
            <?php
            $categories = \Pro\Support\Models\TopicCat::query()->with('translation')->get()->toTree();
            $traverse = function ($categories, $prefix = '') use (&$traverse) {
                foreach ($categories as $category) {
                    $trans = $category->translate();
                    $selected = '';
                    printf("<li class='%s' ><a href='%s'><span></span> %s</a></li>", $selected ? 'selected' : '', $category->getDetailUrl(), $prefix . ' ' . $trans->name);
                    $traverse($category->children, $prefix . '-');
                }
            };
            $traverse($categories);
            ?>
        </ul>
    </div>
    <div class="widget list-topics  mb-5">
        <div class="widget-title ">
            <h4><?php echo e(__("Popular Topics")); ?></h4>
        </div>
        <div class="">
            <?php
            $topics = \Pro\Support\Models\Topic::query()->orderByDesc('views')->orderByDesc('id')->limit(10)->with('translation')->get();
            ?>
            <?php $__currentLoopData = $topics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $topic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $tran = $topic->translate() ?>
                <a
                    class="topic mb-2 d-flex justify-content-between mb-2 pb-2"
                    href="<?php echo e($topic->getDetailUrl()); ?>">
                    <?php echo e($tran->title); ?>

                    <?php if($topic->views): ?>
                        <span>
                            <span class="badge badge-pill badge-light"><?php echo e($topic->views); ?></span>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/layouts/topic/sidebar.blade.php ENDPATH**/ ?>