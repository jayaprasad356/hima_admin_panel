

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Transactions List')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Transactions List')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter by Type Form -->
                <form action="<?php echo e(route('transactions.index')); ?>" method="GET" class="mb-3" id="filterForm">
                    <div class="row align-items-end">
                    <div class="col-md-2">
                            <label for="per_page"><?php echo e(__('Show Entries')); ?></label>
                            <select name="per_page" id="per_page" class="form-control" onchange="this.form.submit()">
                                <?php $__currentLoopData = [10, 25, 50, 100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $limit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($limit); ?>" <?php echo e(request('per_page', 10) == $limit ? 'selected' : ''); ?>>
                                        <?php echo e($limit); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="type"><?php echo e(__('Filter by Type')); ?></label>
                            <select name="type" id="type" class="form-control" onchange="document.getElementById('filterForm').submit();">
                                <option value=""><?php echo e(__('All')); ?></option>
                                <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($type); ?>" <?php echo e(request('type') == $type ? 'selected' : ''); ?>>
                                        <?php echo e(ucfirst($type)); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_date"><?php echo e(__('Filter by Date')); ?></label>
                            <input type="date" name="filter_date" id="filter_date" class="form-control" value="<?php echo e(request()->get('filter_date')); ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label"><?php echo e(__('Search Users')); ?></label>
                            <input type="text" name="search" id="search" class="form-control"
                                value="<?php echo e(request('search')); ?>" placeholder="Enter Name, Mobile"
                                onkeydown="if(event.key === 'Enter') this.form.submit();">
                        </div>
                    </div>
                </form>

                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('Actions')); ?></th>
                                    <th><?php echo e(__('ID')); ?></th>
                                    <th><?php echo e(__('Name')); ?></th>
                                    <th><?php echo e(__('Mobile')); ?></th>
                                    <th><?php echo e(__('Type')); ?></th>
                                    <th><?php echo e(__('Coins')); ?></th>
                                    <th><?php echo e(__('Amount')); ?></th>
                                    <th><?php echo e(__('Payment Type')); ?></th>
                                    <th><?php echo e(__('Datetime')); ?></th>
                                    <th><?php echo e(__('Download')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="selectable-row">
                                    <td class="Action">
                                    <div class="action-btn bg-danger ms-2">
                                                <?php echo Form::open(['method' => 'DELETE', 'route' => ['transactions.destroy', $transaction->id], 'id' => 'delete-form-' . $transaction->id]); ?>

                                                    <a href="#" class="btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="<?php echo e(__('Delete')); ?>"
                                                    onclick="confirmDelete(event, '<?php echo e($transaction->id); ?>')">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                <?php echo Form::close(); ?>

                                            </div>
                                    </td>
                                        <td><?php echo e($transaction->id); ?></td>
                                        <td><?php echo e(ucfirst($transaction->users->name ?? '')); ?></td>
                                        <td><?php echo e($transaction->users->mobile ?? ''); ?></td>
                                        <td><?php echo e($transaction->type); ?></td>
                                        <td><?php echo e($transaction->coins); ?></td>
                                        <td><?php echo e($transaction->amount); ?></td>
                                        <td><?php echo e($transaction->payment_type); ?></td>
                                        <td><?php echo e($transaction->datetime); ?></td>
                                        <td>
                                            <?php if($transaction->type == 'add_coins'): ?>
                                                <a href="<?php echo e(route('transactions.download', $transaction->id)); ?>" class="btn btn-primary btn-sm">
                                                    <?php echo e(__('Download Invoice')); ?>

                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="mb-0">
                                Showing 
                                <strong><?php echo e($transactions->firstItem()); ?></strong> 
                                to 
                                <strong><?php echo e($transactions->lastItem()); ?></strong> 
                                of 
                                <strong><?php echo e($transactions->total()); ?></strong> transactions
                            </p>
                        </div>
                        <div>
                            <?php echo e($transactions->appends(request()->query())->links('pagination::bootstrap-4')); ?>

                        </div>
                    </div>
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
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel-3\resources\views/transactions/index.blade.php ENDPATH**/ ?>