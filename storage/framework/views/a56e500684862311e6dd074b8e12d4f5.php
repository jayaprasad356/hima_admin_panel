

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Add Screen Notifications')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('screen_notifications.index')); ?>"><?php echo e(__('Screen Notifications')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Add Screen Notifications')); ?></li>
<?php $__env->stopSection(); ?>

<style>
    #title {
    font-weight: bold;
}
</style>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5><?php echo e(__('Add New Screen Notifications')); ?></h5>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('screen_notifications.store')); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>

                    <div class="form-group">
                        <label for="title"><?php echo e(__('Title')); ?></label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description"><?php echo e(__('Description')); ?></label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="gender"><?php echo e(__('Gender')); ?></label>
                        <select name="gender" id="gender" class="form-control">
                            <option value="all"><?php echo e(__('All')); ?></option>
                            <option value="male"><?php echo e(__('Male')); ?></option>
                            <option value="female"><?php echo e(__('Female')); ?></option>
                        </select>
                    </div>

                    <!-- Language Dropdown -->
                    <div class="form-group mt-3">
                        <label for="language" class="form-label"><?php echo e(__('Language')); ?></label>
                        <select name="language" class="form-control" required>
                            <option value='all' <?php echo e(old('language') == 'all' ? 'selected' : ''); ?>>All</option>
                            <option value='Hindi' <?php echo e(old('language') == 'Hindi' ? 'selected' : ''); ?>>Hindi</option>
                            <option value='Telugu' <?php echo e(old('language') == 'Telugu' ? 'selected' : ''); ?>>Telugu</option>
                            <option value='Malayalam' <?php echo e(old('language') == 'Malayalam' ? 'selected' : ''); ?>>Malayalam</option>
                            <option value='Kannada' <?php echo e(old('language') == 'Kannada' ? 'selected' : ''); ?>>Kannada</option>
                            <option value='Punjabi' <?php echo e(old('language') == 'Punjabi' ? 'selected' : ''); ?>>Punjabi</option>
                            <option value='Tamil' <?php echo e(old('language') == 'Tamil' ? 'selected' : ''); ?>>Tamil</option>
                        </select>
                    </div>

                    <!-- Datetime Field -->
                    <div class="form-group mt-3">
                        <label for="datetime"><?php echo e(__('Datetime')); ?></label>
                        <input type="datetime-local" id="datetime" name="datetime" class="form-control" required>
                    </div>

                    <!-- Logo Field -->
                    <div class="form-group mt-3">
                        <label for="logo"><?php echo e(__('Logo (Optional)')); ?></label>
                        <input type="file" id="logo" name="logo" class="form-control">
                    </div>

                    <!-- Image Field -->
                    <div class="form-group mt-3">
                        <label for="image"><?php echo e(__('Image (Optional)')); ?></label>
                        <input type="file" id="image" name="image" class="form-control">
                    </div>

                    <!-- Save Button -->
                    <div class="form-group mt-4 text-center">
                        <button type="submit" class="btn btn-primary"><?php echo e(__('Save')); ?></button>
                        <a href="<?php echo e(route('screen_notifications.index')); ?>" class="btn btn-secondary"><?php echo e(__('Cancel')); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/screen_notifications/create.blade.php ENDPATH**/ ?>