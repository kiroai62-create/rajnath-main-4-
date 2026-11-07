@php $staffArea = request()->is('admin', 'admin/*', 'staff/*'); @endphp
<x-layouts.error code="401" :title="__('Please sign in')"
    :message="__('You need to be signed in to see this page.')">
    <x-slot:actions>
        <a href="{{ $staffArea ? route('staff.login') : route('account.sign-in') }}" class="btn-primary">{{ __('Sign in') }}</a>
        <a href="{{ url('/') }}" class="btn-outline">{{ __('Go to the home page') }}</a>
    </x-slot:actions>
</x-layouts.error>
