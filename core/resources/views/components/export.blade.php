
@if(request()->routeIs('admin.*'))
    @php
        $class = 'h-45 export btn-outline--primary';
    @endphp
@endif

<div class="dropdown">
    <button class="{{ @$class }} btn btn-sm btn-outline--base dropdown-toggle d-flex align-items-center" type="button" id="exportMenuButton" 
        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
    >
        @lang('Export as')
    </button>
    <div class="dropdown-menu p-0" aria-labelledby="exportMenuButton">
        <a class="dropdown-item" href="{{ appendQuery('export_type', 'excel') }}">
            <i class="las la-file-excel"></i> @lang('Excel')
        </a>
        <a class="dropdown-item" href="{{ appendQuery('export_type', 'csv') }}">
            <i class="las la-file-csv"></i> @lang('Csv')
        </a>
        <a class="dropdown-item" href="{{ appendQuery('export_type', 'pdf') }}">
            <i class="las la-file-pdf"></i> @lang('PDF')
        </a>
    </div>
</div>

@push('style')
    <style>
        .export{
            padding: 12px 15px;
        }
    </style>
@endpush