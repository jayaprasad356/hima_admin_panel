{{ Form::model($whatsapplink, ['route' => ['whatsapplinks.update', $whatsapplink->id], 'method' => 'PUT', 'id' => 'editForm']) }}
<div class="modal-body">
    <div class="row">
        <!-- Link Input -->
        <div class="form-group col-md-12">
            {{ Form::label('link', __('Link'), ['class' => 'form-label']) }}
            {{ Form::text('link', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>

        <!-- Language Dropdown -->
        <div class="form-group col-md-12 mt-3">
            {{ Form::label('language', __('Language'), ['class' => 'form-label']) }}
            {{ Form::select('language', [
                'Hindi' => __('Hindi'),
                'Telugu' => __('Telugu'),
                'Malayalam' => __('Malayalam'),
                'Kannada' => __('Kannada'),
                'Punjabi' => __('Punjabi'),
                'Tamil' => __('Tamil')
            ], null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <button type="submit" class="btn btn-primary">{{ __('Update WhatsApp Link') }}</button>
</div>
{{ Form::close() }}
