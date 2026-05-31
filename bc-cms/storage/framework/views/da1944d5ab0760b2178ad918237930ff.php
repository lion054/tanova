
<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('dist/frontend/module/support/css/support.css?_v='.config('app.asset_version'))); ?>">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('Support::frontend.layouts.topic.search-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="container pb-5 support-page-create">
        <div class="row">
            <div class="col-md-2"></div>
            <div class="col-md-8">
                <div class="mb-4">
                    <?php echo $__env->make('Layout::parts.bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
                <div class="text-center"><h2><?php echo e(__("Create new ticket")); ?></h2></div>
                <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <form id="ticket_form" action="<?php echo e(route('support.ticket.store')); ?>" method="post">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label><?php echo e(__("Title")); ?>

                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" required class="form-control" name="title" value="<?php echo e(old('title')); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo e(__("Category")); ?>

                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" name="cat_id" required>
                            <?php
                            $traverse = function ($categories, $prefix = '') use (&$traverse) {
                                foreach ($categories as $category) {
                                    $trans = $category->translate();
                                    $selected = old('cat_id') == $category->id;
                                    printf("<option %s value='%s' >%s</li>", $selected ? 'selected' : '', $category->id, $prefix . ' ' . $trans->name);
                                    $traverse($category->children, $prefix . '-');
                                }
                            };
                            $traverse($categories);
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo e(__("Content")); ?>

                            <span class="text-danger">*</span>
                        </label>
                        <textarea name="content" id="ticket_content" class="form-control" cols="30" rows="10"><?php echo e(old('content')); ?></textarea>
                        <div class="invalid-feedback" id="ticket_content_invalid">
                            <?php echo e(__("Please provide ticket content")); ?>

                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo \App\Helpers\ReCaptchaEngine::captcha('ticket'); ?>

                    </div>
                    <div class="form-group d-inline-block">
                        <p>
                            <button type="submit" id="ticket_submit_btn" class="btn btn-primary btn-lg">
                                <i class="fa fa-send"></i> <?php echo e(__("Create ticket")); ?></button>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script src="<?php echo e(asset('libs/tinymce/js/tinymce/tinymce.min.js')); ?>"></script>
    <script>
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
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            },
        };
        var tmp = Object.assign({}, options);
        tmp.selector = '#ticket_content';
        tinymce.init(tmp);

        $('#ticket_submit_btn').on('click', function(e) {
            e.preventDefault();
            tinymce.activeEditor.uploadImages(function(success) {
                var myContent = tinymce.activeEditor.getContent();
                // Form validation
                var form = document.getElementById('ticket_form');
                if (form.checkValidity() !== false && myContent) {
                    document.getElementById('ticket_form').submit();
                }
                form.classList.add('was-validated');

                if (!myContent) {
                    $('#ticket_content_invalid').show();
                    return;
                }
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('Layout::app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/ticket/create.blade.php ENDPATH**/ ?>