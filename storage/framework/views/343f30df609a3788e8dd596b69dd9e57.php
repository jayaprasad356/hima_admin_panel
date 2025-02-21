

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('UserCalls List')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('UserCalls List')); ?></li>
<?php $__env->stopSection(); ?>

<style>
.pagination .page-item .page-link {
    color: #d67291 !important; /* Lighter pink shade */
    border: none !important;
    background: transparent !important;
    font-size: 14px; /* Decrease font size */
    padding: 10px 10px; /* Reduce padding */
    font-weight: bold;
}

.pagination .page-item.active .page-link {
    background: #f2f2f2 !important; /* Softer background */
    color: #d67291 !important; /* Keep lighter pink color */
    box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.15); /* Softer shadow */
    border-radius: 4px;
    font-size: 14px; /* Smaller font size */
    padding: 5px 8px;
}

.pagination .page-item .page-link:hover {
    background: rgba(214, 114, 145, 0.1) !important; /* Light hover effect */
    border-radius: 4px;
}

.pagination .page-item.disabled .page-link {
    color: #ccc !important;
}

</style>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter by Type and Buttons in the same row -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <!-- Filter by Type Form -->
                    <form action="<?php echo e(route('usercalls.index')); ?>" method="GET" class="d-flex align-items-center">
                        <div class="me-5">
                            <label for="type"><?php echo e(__('Filter by Type')); ?></label>
                            <select name="type" id="type" class="form-control status-filter" onchange="this.form.submit()">
                                <option value=""><?php echo e(__('All')); ?></option>
                                <option value="audio" <?php echo e(request()->get('type') == 'audio' ? 'selected' : ''); ?>><?php echo e(__('Audio')); ?></option>
                                <option value="video" <?php echo e(request()->get('type') == 'video' ? 'selected' : ''); ?>><?php echo e(__('Video')); ?></option>
                            </select>
                        </div>

                        <div class="me-5">
                            <label for="language"><?php echo e(__('Filter by Language')); ?></label>
                            <select name="language" id="language" class="form-control status-filter" onchange="this.form.submit()">
                            <option value="all" <?php echo e(request('language') == 'all' ? 'selected' : ''); ?>>All</option>
                            <option value="Tamil" <?php echo e(request('language') == 'Tamil' ? 'selected' : ''); ?>>Tamil</option>
                            <option value="Telugu" <?php echo e(request('language') == 'Telugu' ? 'selected' : ''); ?>>Telugu</option>
                            <option value="Hindi" <?php echo e(request('language') == 'Hindi' ? 'selected' : ''); ?>>Hindi</option>
                            <option value="Kannada" <?php echo e(request('language') == 'Kannada' ? 'selected' : ''); ?>>Kannada</option>
                            <option value="Punjabi" <?php echo e(request('language') == 'Punjabi' ? 'selected' : ''); ?>>Punjabi</option>
                            <option value="Malayalam" <?php echo e(request('language') == 'Malayalam' ? 'selected' : ''); ?>>Malayalam</option>
                            </select>
                        </div>

                        <div class="me-2">
                            <label for="filter_date"><?php echo e(__('Filter by Date')); ?></label>
                            <input type="date" name="filter_date" id="filter_date" class="form-control" 
                             value="<?php echo e(request()->get('filter_date')); ?>" onchange="this.form.submit()">
                        </div>
                    </form>

                    <!-- Buttons aligned to the right -->
                    <div>
                        <!-- Reset Audio Call Form -->
                        <form action="<?php echo e(route('usercalls.updateuser')); ?>" method="POST" style="display: inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="audio_status" value="0">
                            <button type="submit" class="btn btn-warning me-2"><?php echo e(__('Reset Audio Call')); ?></button>
                        </form>

                        <!-- Reset Video Call Form -->
                        <form action="<?php echo e(route('usercalls.updateuser')); ?>" method="POST" style="display: inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="video_status" value="0">
                            <button type="submit" class="btn btn-danger"><?php echo e(__('Reset Video Call')); ?></button>
                        </form>
                    </div>
                </div>
                     <!-- Search Box -->
                <form action="<?php echo e(route('usercalls.index')); ?>" method="GET" class="mb-3">
                <div class="col-md-3 ms-auto">
                    <label for="search"><?php echo e(__('Search')); ?></label>
                    <input type="text" name="search" id="search" class="form-control" 
                    value="<?php echo e(request()->get('search')); ?>" placeholder="Enter Name" onkeyup="startFilterTimer()">
                </div>
                </form>
                <br>
                <!-- Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo e(__('ID')); ?></th>
                                <th><?php echo e(__('User Name')); ?></th>
                                <th><?php echo e(__('Call User Name')); ?></th>
                                <th><?php echo e(__('Type')); ?></th>
                                <th><?php echo e(__('Language')); ?></th>
                                <th><?php echo e(__('Started Time')); ?></th>
                                <th><?php echo e(__('Ended Time')); ?></th>
                                <th><?php echo e(__('Call Duration')); ?></th>
                                <th><?php echo e(__('User Coins')); ?></th>
                                <th><?php echo e(__('Coins Spend')); ?></th>
                                <th><?php echo e(__('Income')); ?></th>
                                <th><?php echo e(__('Datetime')); ?></th>
                                <th><?php echo e(__('Update Current Ended Time')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $usercalls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $usercall): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($usercall->id); ?></td>
                                    <td><?php echo e(ucfirst($usercall->user->name ?? '')); ?></td>
                                    <td><?php echo e(ucfirst($usercall->callusers->name ?? '')); ?></td>
                                    <td><?php echo e(ucfirst($usercall->type)); ?></td>
                                    <td><?php echo e(ucfirst($usercall->user->language ?? '')); ?></td>
                                    <td><?php echo e($usercall->started_time); ?></td>
                                    <td><?php echo e($usercall->ended_time); ?></td>
                                    <td><?php echo e($usercall->duration); ?></td>
                                    <td><?php echo e($usercall->coins); ?></td>
                                    <td><?php echo e($usercall->coins_spend); ?></td>
                                    <td><?php echo e($usercall->income); ?></td>
                                    <td><?php echo e($usercall->datetime); ?></td>
                                    <td><?php echo e($usercall->update_current_endedtime); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <p class="m-3">Showing <?php echo e($usercalls->firstItem()); ?> to <?php echo e($usercalls->lastItem()); ?> of <?php echo e($usercalls->total()); ?> entries</p>
                        <?php echo e($usercalls->appends(request()->except('page'))->links('pagination::bootstrap-4')); ?>

                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    let filterTimer;

    $(document).ready(function () {
        $('#search').on('input', function () {
            clearTimeout(filterTimer); // Clear previous timer
            filterTimer = setTimeout(() => {
                $('form').submit(); // Auto-submit form after 3 seconds
            }, 3000); // 3 seconds delay
        });
    });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/usercalls/index.blade.php ENDPATH**/ ?>