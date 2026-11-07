@php $status = ($exception ?? null) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500; @endphp
<x-layouts.error :code="$status" :title="__('Something went wrong')" :reference="true"
    :message="__('The service is having trouble right now. Our team has been notified. Please try again in a few minutes.')" />
