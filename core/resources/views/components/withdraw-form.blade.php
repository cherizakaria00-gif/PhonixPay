<div class="row">
    @foreach($formData as $data)
        <div class="col-md-{{ @$data->width ?? '12' }}">
            <div class="form-group">
                <div class="justify-content-between d-flex flex-wrap">
                    <label class="form-label">
                        {{ __($data->name) }} @if(@$data->instruction) <span data-bs-toggle="tooltip" data-bs-title="{{ __($data->instruction) }}"  data-bs-placement="top" ><i class="fas fa-info-circle"></i></span> @endif @if($data->is_required == 'required' && ($data->type == 'checkbox' || $data->type == 'radio')) <span class="text--danger">*</span> @endif 
                    </label>
                    @if($data->type == 'file')
                        @foreach($userData as $file) 
                            @if($data->name == $file->name && $file->type == 'file' && $file->value)
                                <a href="{{ route('user.withdraw.download.attachment', encrypt($file->value)) }}">@lang('Download attachment')</a> 
                            @endif
                        @endforeach
                    @endif
                </div>
                @if($data->type == 'text')
                    <input type="text"
                        class="form-control form--control"
                        name="{{ $data->label }}"
                        @if($data->is_required == 'required') required @endif

                        @foreach($userData as $text) 
                            @if($data->name == $text->name && $text->type == 'text')
                                value="{{ old($data->label) ?? $text->value }}"
                            @endif
                        @endforeach
                    >
                @elseif($data->type == 'url')
                    <input type="url"
                        class="form-control form--control"
                        name="{{ $data->label }}"
                        @if($data->is_required == 'required') required @endif

                        @foreach($userData as $text) 
                            @if($data->name == $text->name && $text->type == 'url')
                                value="{{ old($data->label) ?? $text->value }}"
                            @endif
                        @endforeach
                    >
                @elseif($data->type == 'email')
                    <input type="email"
                        class="form-control form--control"
                        name="{{ $data->label }}"
                        @if($data->is_required == 'required') required @endif

                        @foreach($userData as $text) 
                            @if($data->name == $text->name && $text->type == 'email')
                                value="{{ old($data->label) ?? $text->value }}"
                            @endif
                        @endforeach
                    >
                @elseif($data->type == 'datetime')
                    <input type="datetime-local"
                        class="form-control form--control"
                        name="{{ $data->label }}"
                        @if($data->is_required == 'required') required @endif

                        @foreach($userData as $text) 
                            @if($data->name == $text->name && $text->type == 'datetime')
                                value="{{ old($data->label) ?? $text->value }}"
                            @endif
                        @endforeach
                    >
                @elseif($data->type == 'date')
                    <input type="date"
                        class="form-control form--control"
                        name="{{ $data->label }}"
                        @if($data->is_required == 'required') required @endif

                        @foreach($userData as $text) 
                            @if($data->name == $text->name && $text->type == 'date')
                                value="{{ old($data->label) ?? $text->value }}"
                            @endif
                        @endforeach
                    >
                @elseif($data->type == 'time')
                    <input type="time"
                        class="form-control form--control"
                        name="{{ $data->label }}"
                        @if($data->is_required == 'required') required @endif

                        @foreach($userData as $text) 
                            @if($data->name == $text->name && $text->type == 'time')
                                value="{{ old($data->label) ?? $text->value }}"
                            @endif
                        @endforeach
                    >
                @elseif($data->type == 'number')
                    <input type="number"
                        class="form-control form--control"
                        name="{{ $data->label }}"
                        @if($data->is_required == 'required') required @endif

                        @foreach($userData as $text) 
                            @if($data->name == $text->name && $text->type == 'number')
                                value="{{ old($data->label) ?? $text->value }}"
                            @endif
                        @endforeach
                    >
                @elseif($data->type == 'textarea')
                    @php
                        $textareaVal = null;
                        foreach($userData as $textarea){ 
                            if($data->name == $textarea->name && $textarea->type == 'textarea'){ 
                                $textareaVal = old($data->label) ?? $textarea->value;
                            }
                        }
                    @endphp
                    <textarea
                        class="form-control form--control"
                        name="{{ $data->label }}"
                        @if($data->is_required == 'required') required @endif
                    >{{ $textareaVal }}</textarea>
                @elseif($data->type == 'select')
                    <select 
                        class="form-select form--control select2" 
                        data-minimum-results-for-search="-1"
                        name="{{ $data->label }}"
                        @if($data->is_required == 'required') required @endif
                    >
                        <option value="">@lang('Select One')</option>
                        @foreach($data->options as $item)
                            <option value="{{ $item }}"
                                @foreach($userData as $select)
                                    @if($item == $select->value && $select->type == 'select')
                                    @selected(true)
                                    @endif
                                @endforeach
                            >
                                {{ __($item) }}
                            </option>
                        @endforeach
                    </select>
                @elseif($data->type == 'checkbox')
                    @foreach($data->options as $option) 
                        <div class="form-check">
                            <input
                                class="form-check-input exclude"
                                name="{{ $data->label }}[]"
                                type="checkbox"
                                value="{{ $option }}"
                                id="{{ $data->label }}_{{ titleToKey($option) }}"
                                @foreach($userData as $checkbox)  
                                    @if(gettype($checkbox->value) == 'array')
                                        @foreach($checkbox->value as $checkboxVal)
                                            @if($option == $checkboxVal && $checkbox->type == 'checkbox')
                                                @checked(true)
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                            >
                            <label class="form-check-label" for="{{ $data->label }}_{{ titleToKey($option) }}">{{ $option }}</label>
                        </div>
                    @endforeach
                @elseif($data->type == 'radio')
                    @foreach($data->options as $option)
                        <div class="form-check">
                            <input
                            class="form-check-input exclude"
                            name="{{ $data->label }}"
                            type="radio"
                            value="{{ $option }}"
                            id="{{ $data->label }}_{{ titleToKey($option) }}"
                            @foreach($userData as $radio) 
                                @if($option == $radio->value && $radio->type == 'radio')
                                    @checked(true)
                                @endif
                            @endforeach
                            >
                            <label class="form-check-label" for="{{ $data->label }}_{{ titleToKey($option) }}">{{ $option }}</label>
                        </div>
                    @endforeach
                @elseif($data->type == 'file')
                    <input
                    type="file"
                    class="form-control form--control"
                    name="{{ $data->label }}"
   
                    @if($data->type == 'file')
                        @foreach($userData as $file) 
                            @if($data->name == $file->name && $file->type == 'file' && !$file->value && $data->is_required == 'required')
                                required
                            @endif
                        @endforeach
                    @endif

                    accept="@foreach(explode(',',$data->extensions) as $ext) .{{ $ext }}, @endforeach"
                    >
                    <pre class="text--base mt-1">@lang('Supported mimes'): {{ $data->extensions }}</pre>
                @endif
            </div>
        </div>
    @endforeach
</div>