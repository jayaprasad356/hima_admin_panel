

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Manage Screen Notifications')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Screen Notifications')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('action-button'); ?>
    <a href="<?php echo e(route('screen_notifications.create')); ?>" data-bs-toggle="tooltip" title="<?php echo e(__('Create New Screen Notifications')); ?>" class="btn btn-sm btn-primary">
        <i class="ti ti-plus"></i> <?php echo e(__('Add New Screen Notifications')); ?>

    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <form action="<?php echo e(route('screen_notifications.index')); ?>" method="GET" class="mb-3">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label for="filter_date"><?php echo e(__('Filter by Date')); ?></label>
                            <input type="date" name="filter_date" id="filter_date" class="form-control" value="<?php echo e(request()->get('filter_date')); ?>" onchange="this.form.submit()">
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table" id="pc-dt-simple">
                        <thead>
                            <tr>
                            <th width="300px"><?php echo e(__('Actions')); ?></th>
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
                            <?php $__currentLoopData = $screen_notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $screen_notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                <td class="Action">
                                         <span>
                                            <!-- Edit Button -->
                                            <div class="action-btn bg-info ms-2">
                                                <a href="#" data-url="<?php echo e(route('screen_notifications.edit', $screen_notification->id)); ?>"
                                                data-ajax-popup="true" data-title="<?php echo e(__('Edit Screen Notification')); ?>"
                                                   class="btn btn-sm align-items-center" data-bs-toggle="tooltip" title="<?php echo e(__('Edit')); ?>">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                            </div>
                                            <!-- Delete Button -->
                                            <div class="action-btn bg-danger ms-2">
                                                <?php echo Form::open(['method' => 'DELETE', 'route' => ['screen_notifications.destroy', $screen_notification->id], 'id' => 'delete-form-' . $screen_notification->id]); ?>

                                                    <a href="#" class="btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="<?php echo e(__('Delete')); ?>"
                                                    onclick="confirmDelete(event, '<?php echo e($screen_notification->id); ?>')">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                <?php echo Form::close(); ?>

                                            </div>
                                        </span>
                                    </td>
                                    <td><?php echo e($screen_notification->id); ?></td>  
                                    <td><?php echo e($screen_notification->title); ?></td>
                                    <td><?php echo e($screen_notification->description); ?></td>
                                    <td><?php echo e($screen_notification->gender); ?></td>
                                    <td><?php echo e(ucfirst($screen_notification->language)); ?></td>
                                    <td><?php echo e($screen_notification->datetime); ?></td>
                                    <td>
                                        <?php if(!empty($screen_notification->logo)): ?>
                                            <img src="<?php echo e(asset('storage/app/public/' . $screen_notification->logo)); ?>" class="img-fluid" width="50px">
                                        <?php else: ?>
                                            <?php echo e(__('No Logo')); ?>

                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($screen_notification->image)): ?>
                                            <img src="<?php echo e(asset('storage/app/public/' . $screen_notification->image)); ?>" class="img-fluid" width="50px">
                                        <?php else: ?>
                                            <?php echo e(__('No Image')); ?>

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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable with default search functionality
        $('#pc-dt-simple').DataTable();
    });

    // Confirmation for delete action
    function confirmDelete(event, speechTextId) {
        event.preventDefault(); // Prevent the default form submission

        // Show a confirmation dialog
        if (confirm("Are you sure you want to delete this speech text?")) {
            // If the user clicks "Yes", submit the delete form
            document.getElementById('delete-form-' + speechTextId).submit();
        }
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/screen_notifications/index.blade.php ENDPATH**/ ?>