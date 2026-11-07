    {{-- Why online, and common questions (also FAQPage data) --}}
    <section class="bg-ivory-100 py-16" aria-labelledby="why-h">
        <div class="container-page reveal grid gap-10 lg:grid-cols-2">
            <div>
                <p class="eyebrow">{{ __('Why Astro Rajnath Jha') }}</p>
                <h2 id="why-h" class="mt-2 text-2xl sm:text-3xl">{{ __('Traditional Jyotish, explained plainly') }}</h2>
                <div class="mt-5 space-y-4 leading-7 text-ink-800">
                    <p>{{ __('Every reading starts from your exact birth details and the classical Parashari method. Acharya Ji explains what the chart shows, what is likely to help, and what is simply a matter of time — without fear and without expensive remedies.') }}</p>
                    <p>{{ __('You can meet him in Patna, speak by audio or video from anywhere in the world, or send a question and receive a written answer. Pujas are performed with full vidhi on a shubh muhurat, with photos in your account.') }}</p>
                </div>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ([__('Ph.D. in Astrology, 30+ years of practice'), __('Personal attention to every chart'), __('Simple, affordable remedies'), __('Hindi, Maithili, Sanskrit and English'), __('Private and confidential'), __('Secure payments, GST invoices')] as $pt)
                        <li class="flex items-start gap-2 text-sm"><svg aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-gold-700" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>{{ $pt }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="text-xl">{{ __('Questions people ask') }}</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($homeFaqs as [$q, $a])
                        <details class="card group p-4 open:ring-1 open:ring-gold-300"><summary class="cursor-pointer font-semibold marker:text-saffron-500">{{ $q }}</summary><p class="mt-2 text-sm leading-6 text-ink-700">{{ $a }}</p></details>
                    @endforeach
                </div>
                <a href="{{ route('faq') }}" class="btn-outline mt-5">{{ __('All questions') }}</a>
            </div>
        </div>
    </section>
