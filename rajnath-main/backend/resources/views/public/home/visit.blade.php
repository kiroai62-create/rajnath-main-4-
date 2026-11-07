    {{-- Visit --}}
    <section class="container-page reveal pt-16" aria-labelledby="visit-h">
        <div class="card grid overflow-hidden ring-1 ring-gold-200 md:grid-cols-2">
            <x-photo src="images/owner/office-wide-960.jpg" alt="Acharya Ji at work in his Kankarbagh office" class="h-full min-h-56 w-full object-cover" />
            <div class="p-6 sm:p-8">
                <p class="eyebrow">{{ __('Visit or call') }}</p>
                @php $biz = app(\App\Support\Business::class); @endphp
                <h2 id="visit-h" class="mt-2 text-2xl">{{ __($biz->address()['locality']) }}</h2>
                <p class="mt-3 text-ink-600">{{ $biz->addressLine() }}</p>
                <p class="mt-3 text-sm text-ink-600">{{ $biz->hoursLine() }}<br>{{ __('Online consultations by video or audio call.') }}</p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="tel:{{ $site('contact.phone') }}" class="btn-primary">{{ __('Call :phone', ['phone' => $site('contact.phone')]) }}</a>
                    <a href="{{ route('contact') }}" class="btn-outline">{{ __('Directions & contact') }}</a>
                </div>
            </div>
        </div>
    </section>
