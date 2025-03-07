

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Manage Personal Notifications')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Personal Notifications')); ?></li>
<?php $__env->stopSection(); ?>



<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <form action="<?php echo e(route('personal_notifications.index')); ?>" method="GET" class="mb-3">
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
                                <th><?php echo e(__('ID')); ?></th>
                                <th><?php echo e(__('Name')); ?></th>
                                <th><?php echo e(__('Title')); ?></th>
                                <th><?php echo e(__('Description')); ?></th>
                                <th><?php echo e(__('Datetime')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $personal_notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $personal_notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($personal_notification->id); ?></td>  
                                    <td><?php echo e($personal_notification->users->name); ?></td> 
                                    <td><?php echo e($personal_notification->title); ?></td>
                                    <td><?php echo e($personal_notification->description); ?></td>
                                    <td><?php echo e($personal_notification->datetime); ?></td>
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

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/personal_notifications/index.blade.php ENDPATH**/ ?>