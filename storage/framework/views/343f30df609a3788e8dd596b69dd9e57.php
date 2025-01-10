

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('UserCalls List')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('UserCalls List')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter by Type Form -->
                <form action="<?php echo e(route('usercalls.index')); ?>" method="GET" class="mb-3">
                    <div class="row align-items-end">
                        <!-- Existing Status Filter -->
                        <div class="col-md-3">
                    <label for="type"><?php echo e(__('Filter by Type')); ?></label>
                    <select name="type" id="type" class="form-control status-filter" onchange="this.form.submit()">
                        <option value=""><?php echo e(__('All')); ?></option>
                        <option value="audio" <?php echo e(request()->get('type') == 'audio' ? 'selected' : ''); ?>><?php echo e(__('Audio')); ?></option>
                        <option value="video" <?php echo e(request()->get('type') == 'video' ? 'selected' : ''); ?>><?php echo e(__('Video')); ?></option>
                    </select>
                     </div>
                </form>
                </div>
                <div class="table-responsive">
                    <table class="table" id="pc-dt-simple">
                        <thead>
                            <tr>
                                <th><?php echo e(__('ID')); ?></th>
                                <th><?php echo e(__('User Name')); ?></th>
                                <th><?php echo e(__('Call User Name')); ?></th>
                                <th><?php echo e(__('Type')); ?></th>
                                <th><?php echo e(__('Started Time')); ?></th>
                                <th><?php echo e(__('Ended Time')); ?></th>
                                <th><?php echo e(__('Coins Spend')); ?></th>
                                <th><?php echo e(__('Income')); ?></th>
                                <th><?php echo e(__('Datetime')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $usercalls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $usercall): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($usercall->id); ?></td>
                                    <td><?php echo e(ucfirst($usercall->user->name ?? '')); ?></td>
                                    <td><?php echo e(ucfirst($usercall->callusers->name ?? '')); ?></td>
                                    <td><?php echo e(ucfirst($usercall->type)); ?></td>
                                    <td><?php echo e($usercall->started_time); ?></td>
                                    <td><?php echo e($usercall->ended_time); ?></td>
                                    <td><?php echo e($usercall->coins_spend); ?></td>
                                    <td><?php echo e($usercall->income); ?></td>
                                    <td><?php echo e($usercall->datetime); ?></td>
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
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#pc-dt-simple').DataTable();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/usercalls/index.blade.php ENDPATH**/ ?>