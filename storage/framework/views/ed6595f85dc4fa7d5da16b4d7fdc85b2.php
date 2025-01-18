

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Add Notifications')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('notifications.index')); ?>"><?php echo e(__('Notifications')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Add Notifications')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5><?php echo e(__('Add New Notifications')); ?></h5>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('notifications.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>

                    <!-- Gender Selection -->
                    <div class="form-group">
                        <label for="gender"><?php echo e(__('Select Gender')); ?></label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value=""><?php echo e(__('Select Gender')); ?></option>
                            <option value="male"><?php echo e(__('Male')); ?></option>
                            <option value="female"><?php echo e(__('Female')); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title"><?php echo e(__('Title')); ?></label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    
                    <!-- Text Input -->
                    <div class="form-group">
                        <label for="description" class="form-label"><?php echo e(__('Description')); ?></label>
                        <textarea name="description" class="form-control" rows="3" required><?php echo e(old('description')); ?></textarea>
                    </div>

                    <!-- Save Button -->
                    <div class="form-group mt-4 text-center">
                        <button type="submit" class="btn btn-primary"><?php echo e(__('Save')); ?></button>
                        <a href="<?php echo e(route('speech_texts.index')); ?>" class="btn btn-secondary"><?php echo e(__('Cancel')); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/notifications/create.blade.php ENDPATH**/ ?>