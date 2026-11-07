@php
    $retry = (int) (($exception ?? null) && method_exists($exception, 'getHeaders') ? ($exception->getHeaders()['Retry-After'] ?? 0) : 0);
    $wait = $retry > 0 ? ($retry < 60 ? trans_choice(':count second|:count seconds', $retry) : trans_choice(':count minute|:count minutes', (int) ceil($retry / 60))) : null;
@endphp
<x-layouts.error code="429" :title="__('Too many attempts')"
    :message="$wait ? __('You have tried this too many times in a short while. Please wait :wait and try again.', ['wait' => $wait]) : __('You have tried this too many times in a short while. Please wait a minute and try again.')" />
