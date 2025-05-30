<?php echo e(Form::model($gifts, ['route' => ['gifts.update', $gifts->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data'])); ?>

<div class="modal-body">
    <div class="row">
        <!-- Avatar Image Upload -->
        <div class="form-group col-md-12">
            <?php echo e(Form::label('gift_icon', __('Gift Icon'), ['class' => 'form-label'])); ?>

            <div class="mb-2">
                <img src="<?php echo e(asset('storage/app/public/' . $gifts->gift_icon)); ?>" class="img-thumbnail" width="100" alt="Gift Icon">
            </div>
            <input type="file" name="gift_icon" class="form-control">
        </div>
        <div class="form-group col-md-12">
            <?php echo e(Form::label('coins', __('Coins'), ['class' => 'form-label'])); ?>

            <?php echo e(Form::number('coins', null, ['class' => 'form-control', 'required'])); ?>

        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="<?php echo e(__('Cancel')); ?>" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="<?php echo e(__('Update Gifts')); ?>" class="btn btn-primary">
</div>
<?php echo e(Form::close()); ?>

<?php /**PATH C:\xampp\htdocs\hima_admin_panel-1\resources\views/gifts/edit.blade.php ENDPATH**/ ?>