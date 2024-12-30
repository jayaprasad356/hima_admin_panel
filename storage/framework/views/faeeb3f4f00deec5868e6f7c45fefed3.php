

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Coins List')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Coins List')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
         
            <div class="card-body">
                <!-- Filter by Status Form -->

                <!-- Table for user verifications -->
                <form action="<?php echo e(route('coins.updateStatus')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-success" name="status" value="1"><?php echo e(__('Update Coins')); ?></button>

                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table" id="pc-dt-simple">
                                <thead>
                                    <tr>
                                        <th><?php echo e(__('Check Box')); ?></th>
                                        <th><?php echo e(__('ID')); ?></th>
                                        <th><?php echo e(__('Price')); ?></th>
                                        <th><?php echo e(__('Coins')); ?></th>
                                        <th><?php echo e(__('Save')); ?></th>
                                        <th><?php echo e(__('Popular')); ?></th>
                                        <th><?php echo e(__('Best Offer')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $coins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr class="selectable-row">
                                            <td><input type="checkbox" class="user-checkbox" name="coin_ids[]" value="<?php echo e($coin->id); ?>"></td>
                                            <td><?php echo e($coin->id); ?></td>
                                            <td><?php echo e($coin->price); ?></td>
                                            <td><?php echo e($coin->coins); ?></td>
                                            <td><?php echo e($coin->save); ?></td>
                                            <td><?php echo e($coin->popular); ?></td>
                                            <td><?php echo e($coin->best_offer); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>

              
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
$(document).ready(function() {
    // Initialize DataTable (Optional, for sorting and pagination)
    $('#pc-dt-simple').DataTable();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/coins/index.blade.php ENDPATH**/ ?>