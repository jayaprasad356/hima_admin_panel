

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Notifications List')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Notifications List')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('action-button'); ?>
    <a href="<?php echo e(route('notifications.create')); ?>" data-bs-toggle="tooltip" title="<?php echo e(__('Create New notifications')); ?>" class="btn btn-sm btn-primary">
        <i class="ti ti-plus"></i> <?php echo e(__('Add New Notifications')); ?>

    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-xl-12">

        <!-- SUCCESS MESSAGE ALERT -->
        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo session('success'); ?>  <!-- Display full message with filters -->
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <!-- Notifications Table -->
                <div class="table-border-style">
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('Actions')); ?></th>
                                    <th><?php echo e(__('ID')); ?></th>
                                    <th><?php echo e(__('Title')); ?></th>
                                    <th><?php echo e(__('Description')); ?></th>
                                    <th><?php echo e(__('Gender')); ?></th>
                                    <th><?php echo e(__('Language')); ?></th>
                                    <th><?php echo e(__('Datetime')); ?></th>
                                    <th><?php echo e(__('Logo')); ?></th>
                                    <th><?php echo e(__('Image')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="Action">
                                            <span>
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="#" data-url="<?php echo e(route('notifications.edit', $notification->id)); ?>" data-ajax-popup="true" data-title="<?php echo e(__('Edit notifications')); ?>"
                                                    class="btn btn-sm align-items-center" data-bs-toggle="tooltip" title="<?php echo e(__('Edit')); ?>">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-danger ms-2">
                                                    <?php echo Form::open(['method' => 'DELETE', 'route' => ['notifications.destroy', $notification->id], 'id' => 'delete-form-' . $notification->id]); ?>

                                                        <button type="button" class="btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="<?php echo e(__('Delete')); ?>"
                                                        onclick="confirmDelete(event, '<?php echo e($notification->id); ?>')">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </button>
                                                    <?php echo Form::close(); ?>

                                                </div>
                                            </span>
                                        </td>
                                        <td><?php echo e($notification->id); ?></td>
                                        <td><?php echo e($notification->title); ?></td>
                                        <td><?php echo e($notification->description); ?></td>
                                        <td><?php echo e($notification->gender); ?></td>
                                        <td><?php echo e($notification->language); ?></td>
                                        <td><?php echo e($notification->datetime); ?></td>
                                        <td>
                                            <?php if(!empty($notification->logo)): ?>
                                                <img src="<?php echo e(asset('storage/app/public/' . $notification->logo)); ?>" class="rounded-circle" width="35" height="35" alt="<?php echo e($notification->title); ?>">
                                            <?php else: ?>
                                                <img src="<?php echo e(asset('assets/img/placeholder.jpg')); ?>" class="rounded-circle" width="35" height="35" alt="<?php echo e($notification->title); ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($notification->image)): ?>
                                                <img src="<?php echo e(asset('storage/app/public/' . $notification->image)); ?>" class="rounded-circle" width="35" height="35" alt="<?php echo e($notification->title); ?>">
                                            <?php else: ?>
                                                <img src="<?php echo e(asset('assets/img/placeholder.jpg')); ?>" class="rounded-circle" width="35" height="35" alt="<?php echo e($notification->title); ?>">
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#pc-dt-simple').DataTable();
    });

    function confirmDelete(event, notificationId) {
        event.preventDefault();
        if (confirm('Are you sure you want to delete this notification?')) {
            document.getElementById('delete-form-' + notificationId).submit();
        }
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/notifications/index.blade.php ENDPATH**/ ?>