<x-layouts.error code="503" :title="__('We will be right back')" :reference="true"
    :message="__('The website is down for scheduled maintenance or is briefly overloaded. Please try again in a few minutes.')">
    <x-slot:actions>
        <a href="{{ request()->fullUrl() }}" class="btn-primary">{{ __('Try again') }}</a>
    </x-slot:actions>
</x-layouts.error>
