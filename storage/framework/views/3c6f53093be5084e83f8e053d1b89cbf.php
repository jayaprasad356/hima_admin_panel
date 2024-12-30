

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Edit App Settings')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Edit App Settings')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5><?php echo e(__('Edit App Settings')); ?></h5>
            </div>
            <div class="card-body">
        <form action="<?php echo e(route('appsettings.update', $appsettings->id)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="form-group">
                <label for="link">Link</label>
                <input type="text" class="form-control" id="link" name="link" value="<?php echo e(old('link', $appsettings->link)); ?>" required>
            </div>

            <div class="form-group">
                <label for="app_version">App Version</label>
                <input type="text" class="form-control" id="app_version" name="app_version" value="<?php echo e(old('app_version', $appsettings->app_version)); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="10" required><?php echo old('description', $appsettings->description); ?></textarea>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
<script src="//cdn.ckeditor.com/4.21.0/full-all/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        CKEDITOR.replace('privacy_policy', {
            extraPlugins: 'colorbutton'
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/appsettings/edit.blade.php ENDPATH**/ ?>