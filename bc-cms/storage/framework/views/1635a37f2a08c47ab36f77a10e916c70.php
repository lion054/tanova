<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php $__currentLoopData = $destinations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $destination): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <url>
        <loc><?php echo e(url('/destination/' . $destination->slug . '?id=' . $destination->id)); ?></loc>
        <lastmod><?php echo e($destination->updated_at->toAtomString()); ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</urlset>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/sitemap/destinations.blade.php ENDPATH**/ ?>