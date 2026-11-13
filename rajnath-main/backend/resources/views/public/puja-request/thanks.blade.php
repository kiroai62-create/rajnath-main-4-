<x-layouts.public :title="__('Puja request received')" robots="noindex,nofollow" :jsonld="false">
    <div class="container-page max-w-2xl py-16">
        <div class="card p-6 text-center sm:p-10" role="status">
            <span aria-hidden="true" class="mx-auto grid size-14 place-items-center rounded-full bg-green-50 text-2xl text-success-700 ring-1 ring-green-200">✓</span>
            <h1 class="mt-5 text-2xl">{{ __('Thank you') }}, <span translate="no">{{ $r->name }}</span></h1>
            <p class="mt-3 text-ink-600">{{ __('We have received your puja request.') }} <span translate="no">({{ $r->puja_type }})</span></p>
            <p class="mt-2 text-ink-600">{{ __('Our team will call you to fix the date, place and dakshina.') }} <span translate="no">{{ \App\Support\Logging\Redactor::mask($r->phone_e164) }}</span></p>
            <p class="mt-2 text-sm text-ink-600">{{ __('Reference') }}: <span class="font-mono" translate="no">{{ substr($r->ref, -8) }}</span></p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('home') }}" class="btn-outline">{{ __('Back to home') }}</a>
                @guest<a href="{{ route('account.sign-in') }}" class="btn-primary">{{ __('Create an account to track requests') }}</a>@endguest
                @auth<a href="{{ route('account.puja.index') }}" class="btn-primary">{{ __('See my puja requests') }}</a>@endauth
            </div>
        </div>
    </div>
</x-layouts.public>
