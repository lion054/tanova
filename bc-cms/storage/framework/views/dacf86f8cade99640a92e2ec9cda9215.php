<div class="support-topic-search-banner">
    <div class="bc_banner" style="background-image: url('/uploads/demo/tour/banner-search.jpg')">
        <div class="container text-center">
            <h2 class="banner-title text-white text-heading text-center text-36"><?php echo e(__('How Can We Help?')); ?></h2>
            <p class="banner-desc text-white  sub-heading text-center text-18"><?php echo e(__('Find out more topics')); ?></p>
            <a href="<?php echo e(route('support.ticket.index')); ?>" class="btn btn-primary" type="submit">
                <i class="fa fa-support"></i> <?php echo e(__('Support Tickets')); ?></a>
        </div>
    </div>
    <div class="bc_form_search">
        <div class="container">
            <div class="row">
                <div class="col-md-2"></div>
                <div class="col-md-8">
                    <div class=" g-form-control">
                        <form class="banner-search form bc_form" method="get"
                            action="<?php echo e(route('support.topic.index')); ?>">
                            <div class="g-field-search">
                                <div class="form-group">
                                    <div class="form-content">
                                        <input type="text" placeholder="<?php echo e(__('Search topic...')); ?>"
                                            class="form-control" value="<?php echo e(request('s')); ?>" name="s">
                                    </div>
                                </div>
                            </div>
                            <div class="g-button-submit">
                                <button class="btn btn-primary btn-search" type="submit"><?php echo e(__('Search')); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/layouts/topic/search-form.blade.php ENDPATH**/ ?>