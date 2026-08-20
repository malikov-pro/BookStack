@push('head')
    <link rel="stylesheet" href="{{ versioned_asset('dist/markdown-yfm.css') }}">
@endpush

<div id="markdown-yfm-editor"
     component="markdown-yfm-editor"
     option:markdown-yfm-editor:page-id="{{ $model->id ?? 0 }}"
     option:markdown-yfm-editor:image-upload-error-text="{{ trans('errors.image_upload_error') }}"
     option:markdown-yfm-editor:server-upload-limit-text="{{ trans('errors.server_upload_limit') }}"
     class="markdown-yfm-editor flex-fill flex-container-column">

    <div class="editor-toolbar flex-container-row items-stretch justify-space-between">
        <div class="editor-toolbar-label text-mono bold px-m py-xs flex-container-row items-center flex">
            <span>{{ trans('entities.pages_md_editor') }}</span>
        </div>
        <div class="buttons flex-container-row items-stretch">
            @if(config('services.drawio'))
                <button class="text-button" type="button" data-action="insertDrawing" title="{{ trans('entities.pages_md_insert_drawing') }}">@icon('drawing')</button>
            @endif
            <button class="text-button" type="button" data-action="insertImage" title="{{ trans('entities.pages_md_insert_image') }}">@icon('image')</button>
            <button class="text-button" type="button" data-action="insertLink" title="{{ trans('entities.pages_md_insert_link') }}">@icon('link')</button>
            <button class="text-button" type="button" data-action="fullscreen" title="{{ trans('common.fullscreen') }}">@icon('fullscreen')</button>
        </div>
    </div>

    <textarea refs="markdown-yfm-editor@input"
              class="markdown-yfm-editor-input"
              name="markdown"
              rows="5">@if(isset($model) || old('markdown')){{ old('markdown') ?? ($model->markdown === '' ? $model->html : $model->markdown) }}@endif</textarea>

    <div refs="markdown-yfm-editor@mount"
         class="markdown-yfm-editor-mount"
         dir="{{ $locale->htmlDirection() }}"></div>
</div>

@if($errors->has('markdown'))
    <div class="text-neg text-small">{{ $errors->first('markdown') }}</div>
@endif
