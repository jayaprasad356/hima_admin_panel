

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Add Notifications')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('notifications.index')); ?>"><?php echo e(__('Notifications')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Add Notifications')); ?></li>
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
                <h5><?php echo e(__('Add New Notifications')); ?></h5>
            </div>
            <div class="card-body">
            <form action="<?php echo e(route('notifications.store')); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="gender"><?php echo e(__('Gender')); ?></label>
                    <select name="gender" id="gender" class="form-control">
                        <option value="all"><?php echo e(__('All')); ?></option>
                        <option value="male"><?php echo e(__('Male')); ?></option>
                        <option value="female"><?php echo e(__('Female')); ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="language"><?php echo e(__('Language')); ?></label>
                    <select name="language" id="language" class="form-control">
                        <option value="all"><?php echo e(__('All')); ?></option>
                        <option value="Hindi"><?php echo e(__('Hindi')); ?></option>
                        <option value="Telugu"><?php echo e(__('Telugu')); ?></option>
                        <option value="Malayalam"><?php echo e(__('Malayalam')); ?></option>
                        <option value="Kannada"><?php echo e(__('Kannada')); ?></option>
                        <option value="Punjabi"><?php echo e(__('Punjabi')); ?></option>
                        <option value="Tamil"><?php echo e(__('Tamil')); ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title"><strong><?php echo e(__('Title')); ?></strong></label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description"><?php echo e(__('Description')); ?></label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-group mt-3">
                        <label for="logo"><?php echo e(__('Logo (optional)')); ?></label>
                        <input type="file" id="logo" name="logo" class="form-control">
                    </div>

                    <!-- Image Field -->
                    <div class="form-group mt-3">
                        <label for="image"><?php echo e(__('Image (optional)')); ?></label>
                        <input type="file" id="image" name="image" class="form-control">
                    </div> 

                <div class="form-group mt-4 text-center">
                    <button type="submit" class="btn btn-primary"><?php echo e(__('Send Notification')); ?></button>
                    <a href="<?php echo e(route('notifications.index')); ?>" class="btn btn-secondary"><?php echo e(__('Cancel')); ?></a>
                </div>
            </form>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('scripts'); ?>

<script>
    document.getElementById('title').addEventListener('input', function () {
        this.style.fontWeight = 'bold'; // Make the text bold
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/notifications/create.blade.php ENDPATH**/ ?>