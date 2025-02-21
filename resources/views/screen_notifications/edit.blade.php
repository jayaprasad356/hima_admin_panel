{{ Form::model($screen_notifications, ['route' => ['screen_notifications.update', $screen_notifications->id], 'method' => 'PUT']) }}
<style>
    #title {
    font-weight: bold;
}

</style>
<div class="modal-body">
    <div class="row">
        <!-- Text Input -->
        <div class="form-group col-md-12">
            {{ Form::label('title', __('Title'), ['class' => 'form-label']) }}
            {{ Form::text('title', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>

        <!-- Description Input -->
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3', 'required' => 'required']) }}
        </div>

        <!-- Gender Dropdown -->
        <div class="form-group col-md-12 mt-3">
            {{ Form::label('gender', __('Gender'), ['class' => 'form-label']) }}
            {{ Form::select('gender', [
                'all' => __('all'),
                'male' => __('male'),
                'female' => __('female'),
            ], null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>

        <!-- Language Dropdown -->
        <div class="form-group col-md-12 mt-3">
            {{ Form::label('language', __('Language'), ['class' => 'form-label']) }}
            {{ Form::select('language', [
                'all' => __('all'),
                'Hindi' => __('Hindi'),
                'Telugu' => __('Telugu'),
                'Malayalam' => __('Malayalam'),
                'Kannada' => __('Kannada'),
                'Punjabi' => __('Punjabi'),
                'Tamil' => __('Tamil')
            ], null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>

        <!-- Datetime Input -->
        <div class="form-group col-md-12 mt-3">
            {{ Form::label('datetime', __('Datetime'), ['class' => 'form-label']) }}
            {{ Form::datetimeLocal('datetime', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update Screen Notifications') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
