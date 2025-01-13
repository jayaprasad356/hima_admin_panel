

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

                     <!-- User Dropdown -->
                     <div class="form-group">
                        <label for="user_id"><?php echo e(__('Select User')); ?></label>
                        <select id="user_id" name="user_id" class="form-control select2" required>
                            <option value=""><?php echo e(__('Select User')); ?></option>
                            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($user->id); ?>"><?php echo e($user->name); ?> (<?php echo e($user->mobile); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                <!-- User Details -->
                <div id="user-details" class="mt-3" style="display: none;">
                    <p><strong><?php echo e(__('Name:')); ?></strong> <span id="user-name"></span></p>
                    <p><strong><?php echo e(__('Email:')); ?></strong> <span id="user-email"></span></p>
                    <p><strong><?php echo e(__('Mobile:')); ?></strong> <span id="user-mobile"></span></p>
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

<script>
    document.getElementById('user_id').addEventListener('change', function () {
    const userId = this.value;

    if (userId) {
        fetch(`/users/${userId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('user-details').style.display = 'block';
                document.getElementById('user-name').textContent = data.name;
                document.getElementById('user-email').textContent = data.email;
                document.getElementById('user-mobile').textContent = data.mobile;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('user-details').style.display = 'none';
            });
    } else {
        document.getElementById('user-details').style.display = 'none';
    }
});

</script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "<?php echo e(__('Select User')); ?>",
            allowClear: true
        });
    });
</script>
<script>
    $('#user_id').select2({
        placeholder: "<?php echo e(__('Select User')); ?>",
        allowClear: true,
        ajax: {
            url: "<?php echo e(route('search.users')); ?>",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            id: item.id,
                            text: item.name + ' (' + item.mobile + ')'
                        };
                    })
                };
            },
            cache: true
        }
    });
</script>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/notifications/create.blade.php ENDPATH**/ ?>