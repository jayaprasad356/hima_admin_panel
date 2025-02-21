<?php echo e(Form::model($userVerification, ['route' => ['users-verification.update', $userVerification->id], 'method' => 'PUT'])); ?>


<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            <?php echo e(Form::label('describe_yourself', __('Describe Yourself'), ['class' => 'form-label'])); ?>

            <?php echo e(Form::textarea('describe_yourself', null, ['class' => 'form-control', 'rows' => 4])); ?>

        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
    <button type="submit" class="btn btn-primary"><?php echo e(__('Update Details')); ?></button>
</div>

<?php echo e(Form::close()); ?>

<?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/users-verification/edit.blade.php ENDPATH**/ ?>