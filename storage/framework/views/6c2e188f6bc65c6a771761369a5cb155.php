
<?php echo e(Form::model($screen_notifications, ['route' => ['screen_notifications.update', $screen_notifications->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data'])); ?>

<style>
    #title {
    font-weight: bold;
}

</style>
<div class="modal-body">
    <div class="row">
        <!-- Text Input -->
        <div class="form-group col-md-12">
            <?php echo e(Form::label('title', __('Title'), ['class' => 'form-label'])); ?>

            <?php echo e(Form::text('title', null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>

        <!-- Description Input -->
        <div class="form-group col-md-12">
            <?php echo e(Form::label('description', __('Description'), ['class' => 'form-label'])); ?>

            <?php echo e(Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3', 'required' => 'required'])); ?>

        </div>

        <!-- Gender Dropdown -->
        <div class="form-group col-md-12 mt-3">
            <?php echo e(Form::label('gender', __('Gender'), ['class' => 'form-label'])); ?>

            <?php echo e(Form::select('gender', [
                'all' => __('all'),
                'male' => __('male'),
                'female' => __('female'),
            ], null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>

        <!-- Language Dropdown -->
        <div class="form-group col-md-12 mt-3">
            <?php echo e(Form::label('language', __('Language'), ['class' => 'form-label'])); ?>

            <?php echo e(Form::select('language', [
                'all' => __('all'),
                'Hindi' => __('Hindi'),
                'Telugu' => __('Telugu'),
                'Malayalam' => __('Malayalam'),
                'Kannada' => __('Kannada'),
                'Punjabi' => __('Punjabi'),
                'Tamil' => __('Tamil')
            ], null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>

        <!-- Datetime Input -->
        <div class="form-group col-md-12 mt-3">
            <?php echo e(Form::label('datetime', __('Datetime'), ['class' => 'form-label'])); ?>

            <?php echo e(Form::datetimeLocal('datetime', null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>

   

        <div class="form-group col-md-12">
            <?php echo e(Form::label('logo', __('logo (Optional)'), ['class' => 'form-label'])); ?>

            <div class="mb-2">
                <img src="<?php echo e(asset('storage/app/public/' . $screen_notifications->logo)); ?>" class="img-thumbnail" width="100" alt="Gift Icon">
            </div>
            <input type="file" name="logo" class="form-control">
        </div>

        <div class="form-group col-md-12">
            <?php echo e(Form::label('image', __('Image (Optional)'), ['class' => 'form-label'])); ?>

            <div class="mb-2">
                <img src="<?php echo e(asset('storage/app/public/' . $screen_notifications->image)); ?>" class="img-thumbnail" width="100" alt="Gift Icon">
            </div>
            <input type="file" name="image" class="form-control">
        </div>

      
            
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="<?php echo e(__('Cancel')); ?>" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="<?php echo e(__('Update Screen Notifications')); ?>" class="btn btn-primary">
</div>
<?php echo e(Form::close()); ?>

<?php /**PATH C:\xampp\htdocs\hima_admin_panel\resources\views/screen_notifications/edit.blade.php ENDPATH**/ ?>