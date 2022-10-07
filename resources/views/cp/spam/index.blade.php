@extends('statamic::layout')
@section('title', __('Spam'))

@section('content')
    @php
        $spam = collect(\Illuminate\Support\Facades\Storage::allFiles('_spam'))->map(function ($path) {
            $formHandle = explode('/', $path)[1];
            $rawYaml = \Illuminate\Support\Facades\Storage::get($path);
            return [
                'path' => $path,
                'fileName' => basename($path),
                'formHandle' => $formHandle,
                'data' => \Statamic\Facades\YAML::parse($rawYaml)
            ];
        });
    @endphp
    <ul class="content">
        @foreach($spam as $idx => $s)
            <li class="card">
                <p>Form : {{ \Illuminate\Support\Str::title($s['formHandle']) }}</p>
                <details>
                    <summary class="cursor-pointer">[{{ $idx }}] Data ></summary>
{{--                    <pre>@json($s['data'], JSON_PRETTY_PRINT)</pre>--}}
                    <pre>
                        @php
                            $jsonStringData = json_encode($s['data'], JSON_PRETTY_PRINT);
                        @endphp
                        {{ $jsonStringData }}
                    </pre>
                </details>
            </li>
        @endforeach
    </ul>
@stop
