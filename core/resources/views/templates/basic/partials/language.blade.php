@if(gs('multi_language'))
    @php
        $language = App\Models\Language::all();
    @endphp

    <div class="custom--dropdown ms-2">
        @foreach($language as $lang)
            @if(session('lang')==$lang->code)
                <div class="custom--dropdown__selected dropdown-list__item">
                    <div class="thumb"> <img class="flag" src="{{ getImage(getFilePath('language').'/'.$lang->image,getFileSize('language')) }}"></div>
                    <span class="text">{{ __($lang->name) }}</span>
                </div>
            @endif
        @endforeach
        <ul class="dropdown-list">    
            @foreach($language as $item)
                <li class="dropdown-list__item langSel" data-value="{{ $item->code }}">
                    <div class="thumb"> <img class="flag" src="{{ getImage(getFilePath('language').'/'.$item->image,getFileSize('language')) }}"></div>
                    <span class="text">{{ __($item->name) }}</span>
                </li>
            @endforeach
        </ul>
    </div>
 
    @push('script')
        <script>
            (function($) {
                "use strict";
                $(".langSel").on("click", function () {
                    window.location.href = "{{ route('home') }}/change/" + $(this).data('value');
                });
            })(jQuery)
        </script>
    @endpush
@endif
