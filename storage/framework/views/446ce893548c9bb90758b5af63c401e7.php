

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Payments List')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Payments List')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter Form for Date Selection -->
                <form action="<?php echo e(route('payments.downloadBulkInvoice')); ?>" method="GET" id="downloadInvoiceForm">
                    <?php echo csrf_field(); ?>
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="start_date"><?php echo e(__('Start Date')); ?></label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo e(request()->get('start_date')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date"><?php echo e(__('End Date')); ?></label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo e(request()->get('end_date')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success d-block w-100" id="downloadInvoicesBtn"><?php echo e(__('Download Invoices')); ?></button>
                        </div>
                    </div>
                </form>

                <div class="card-body table-border-style mt-3">
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('ID')); ?></th>
                                    <th><?php echo e(__('Name')); ?></th>
                                    <th><?php echo e(__('Mobile')); ?></th>
                                    <th><?php echo e(__('Type')); ?></th>
                                    <th><?php echo e(__('Coins')); ?></th>
                                    <th><?php echo e(__('Amount')); ?></th>
                                    <th><?php echo e(__('Payment Type')); ?></th>
                                    <th><?php echo e(__('Invoice No')); ?></th>
                                    <th><?php echo e(__('Datetime')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($payment->id); ?></td>
                                        <td><?php echo e(ucfirst($payment->users->name ?? '')); ?></td>
                                        <td><?php echo e($payment->users->mobile ?? ''); ?></td>
                                        <td><?php echo e($payment->type); ?></td>
                                        <td><?php echo e($payment->coins); ?></td>
                                        <td><?php echo e($payment->amount); ?></td>
                                        <td><?php echo e($payment->payment_type); ?></td>
                                        <td><?php echo e($payment->invoice_no); ?></td>
                                        <td><?php echo e($payment->datetime); ?></td>
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
document.getElementById("downloadInvoiceForm").addEventListener("click", function () {
    let startDate = document.getElementById("start_date").value;
    let endDate = document.getElementById("end_date").value;

    if (!startDate || !endDate) {
        alert("Please select both start and end dates.");
        return;
    }

    window.location.href = "<?php echo e(route('payments.downloadBulkInvoice')); ?>?start_date=" + encodeURIComponent(startDate) + "&end_date=" + encodeURIComponent(endDate);

});

</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/payments/index.blade.php ENDPATH**/ ?>