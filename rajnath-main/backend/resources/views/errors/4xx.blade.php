@php $status = ($exception ?? null) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400; @endphp
<x-layouts.error :code="$status" :title="__('We could not handle that request')"
    :message="__('The request was not valid for this page. Go back and try again, or start again from the home page.')" />
