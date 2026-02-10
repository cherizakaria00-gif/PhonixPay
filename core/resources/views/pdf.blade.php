<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ __(ucfirst($pageTitle)) }}</title>
  
    <style>
        .pdf-table{
            width: 100% !important;
        }
        .pdf-table td{
            text-align: center
        }
        .pdf-table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        .bg-black{
            background-color: black;
            color: #fff;
        }
        table{
            border: 1px solid;
            overflow: hidden;
        }
        table td, table th {
            border: 1px solid;
            padding: 0 5px;
        }
        .pdf-header {
            position: relative;
            border-bottom: 1px dashed #000;
        }
        .pdf-header .logo  img {
            max-width: 400px;
            max-height: 100px; 
        }
        .list--row::after {
            content: '';
            display: block;
            clear: both;
        }
        .content {
            float: right;
            position: relative;
            padding-bottom: 10px;;
            color: #{{ gs('base_color') }}
        }
        .content__text {
            display: block;
        }
        .site {
            display: block;
            margin-bottom: 0;
            font-family: 'Copperplate Gothic Bold', sans-serif;
            font-weight: 500;
            text-transform: uppercase;
        }
        .pdf-footer {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 40px;
            text-align: center; 
            font-family: 'Copperplate Gothic Bold', sans-serif;
            font-weight: 500;
            text-transform: uppercase;
            color: #{{ gs('base_color') }}; 
        }
    </style>
</head>

<body>
    <div style="margin-bottom:35px; display: block; min-height: 150px; width: 100% " class="pdf-header list--row">
        <div style="float:left; width: 50%">
            <div class="logo">
                <img src="{{ siteLogo('dark') }}" alt="@lang('Logo')">
            </div> 

            <div>
                @if(request()->routeIs('user*'))
                    @php $user = auth()->user(); @endphp
                    <h4 style="margin-bottom: 0px">
                        {{ $user->fullname }}
                    </h4>
                    <div>
                        {{ @$user->address->address }}{{ @$user->address->address ? ',' : null }}
                        {{ @$user->address->city }}{{ @$user->address->city ? ',' : null }}
                        {{ @$user->address->state }}{{ @$user->address->state ? ',' : null }}
                        {{ @$user->address->zip }}{{ @$user->address->zip ? ',' : null }}
                        {{ @$user->address->country }}
                    </div>
                @endif
                <p>@lang('This statement') ({{ $pageTitle }}) @lang('has been downloaded at') <br> {{ showDateTime(now()) }} </p>
            </div>
            
        </div>
        <div class="content" style="float:right; width: 25%;">
            <p class="site">{{ __(gs('site_name')) }}</p>
            @php echo nl2br(@$pdfContent->header); @endphp
        </div>
    </div>
    <div class="table-content" style="display: block">
        @php echo $html; @endphp
    </div>
    <div class="pdf-footer">
        @php echo nl2br(@$pdfContent->footer); @endphp
    </div>
</body>
</html>