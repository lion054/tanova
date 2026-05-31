
<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('dist/frontend/module/support/css/support.css?_v='.config('app.asset_version'))); ?>">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('Support::frontend.layouts.topic.search-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="topic-lists-wrap topic-detail mb-5">
        <div class="container">
            <div class="row mt-4">
                <div class="col-md-9">
                    <div class="mb-4">
                        <?php echo $__env->make('Layout::parts.bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div class="page-header">
                        <h1 class="text-24">
                            <i class="fa fa-file-text-o"></i>
                            <?php echo e($page_title); ?></h1>
                        <div class="ml-3 mt-2 topic-meta">
                            <?php if(!empty($is_agent)): ?>
                                <span>
                                    <i class="fa fa-user-o mr-1"></i> <?php echo e($row->customer->display_name ?? ''); ?>

                                </span>
                            <?php endif; ?>
                            <?php if($row->cat): ?>
                                <?php $cat_trans = $row->cat->translate() ?>
                                <span class="mr-3">
                                    <i class="fa fa-folder-o mr-1"></i>
                                    <a href="<?php echo e($row->cat->getDetailUrl()); ?>"><?php echo e($cat_trans->name ?? ''); ?></a>
                                </span>
                            <?php endif; ?>
                            <?php if($row->created_at): ?>
                                <span>
                                    <i class="fa fa-clock-o"></i> <?php echo e(human_time_diff(strtotime($row->created_at))); ?> ago
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="topic-content py-4">
                        <?php echo clean($row->content); ?>

                    </div>
                    <hr>
                    <?php echo $__env->make('Support::frontend.layouts.ticket.replies', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
                <div class="col-md-3">
                    <?php echo $__env->make('Support::frontend.layouts.ticket.detail-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script src="<?php echo e(asset('libs/tinymce/js/tinymce/tinymce.min.js')); ?>"></script>
    <script>
        $('#current_time').html(moment().format('hh:mm:ss A'));
        var options = {
            menubar: false,
            plugins: 'image link codesample table hr lists',
            toolbar: 'bold italic strikethrough permanentpen formatpainter | link image media | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | pagebreak codesample code | removeformat',
            image_advtab: false,
            image_caption: false,
            toolbar_drawer: 'sliding',
            relative_urls: false,
            height: 400,
            file_picker_types: 'image',
            paste_data_images: true,
            images_upload_handler: function(blobInfo, success, failure) {

                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                formData.append('is_private', 1);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                $.ajax({
                    url: '<?php echo e(route('media.store')); ?>',
                    data: formData,
                    dataType: 'json',
                    method: 'post',
                    processData: false,
                    contentType: false,
                    success: function(json) {
                        success(json.url);
                    },
                });
            },
        };
        var tmp = Object.assign({}, options);
        tmp.selector = '#reply_content2';
        tinymce.init(tmp);

        $('#reply_submit_btn2').on('click', function() {
            tinymce.activeEditor.uploadImages(function(success) {
                var myContent = tinymce.activeEditor.getContent();
                if (!myContent) {
                    $('#reply_content_invalid').show();
                    return;
                }
                document.getElementById('reply_form2').submit();
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('Layout::app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/ticket/detail.blade.php ENDPATH**/ ?>