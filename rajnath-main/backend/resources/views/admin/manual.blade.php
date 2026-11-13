@php
    $sections = [
        'start' => ['Getting started', [
            'Sign in at <code>'.e(route('staff.login')).'</code> with your email and password. The first admin is created automatically when the website is deployed; the email and password are printed at the end of the deploy.',
            'Open <a href="'.e(route('admin.account.show')).'">My account</a> straight away: choose your own password and turn on <strong>two-factor sign-in</strong> (scan the QR code with Google Authenticator or Microsoft Authenticator).',
            'The left menu shows only the sections your role allows. The owner sees everything. Use the "Find a section" box at the top of the menu to jump to a screen.',
            'Some sensitive saves (payment keys, team, settings, languages) ask you to confirm your password again. This is normal and lasts 10 minutes.',
            'Forgot the admin password? On the server run <code>docker compose -f deploy/docker-compose.yml exec app php artisan arj:ensure-admin --reset --email=you@example.com</code>. A new password is printed and two-factor sign-in is turned off.',
        ]],
        'setup' => ['First-time setup checklist', [
            '<a href="'.e(route('admin.settings.edit')).'">Settings</a>: brand name, phone, WhatsApp, email, address, office hours.',
            '<a href="'.e(route('admin.providers.edit')).'">Email &amp; SMS</a>: SMTP and SMS keys, then press <em>Send test</em>.',
            '<a href="'.e(route('admin.payments.settings')).'">Payments setup</a>: Razorpay keys, GSTIN, courier and payouts.',
            '<a href="'.e(route('admin.assistant.edit')).'">AI assistant</a>: add a Groq, Gemini, Cloudflare or OpenAI key and switch the provider on. The same key powers the chat assistant, Rashifal writing, the ✨ AI buttons and website translation.',
            '<a href="'.e(route('admin.languages.index')).'">Languages</a>: choose the languages visitors can pick and press <em>Translate the whole website now</em>.',
            '<a href="'.e(route('admin.legal.index')).'">Legal pages</a>: publish the refund and shipping policies before going live.',
        ]],
        'puja' => ['Puja requests', [
            'Online puja and chadhava are no longer sold on the website. Visitors fill in the form at <code>'.e(route('puja-request.create')).'</code> (menu: <em>Puja &amp; remedies › Request a puja</em>). Old puja and chadhava pages open this form.',
            'Each request arrives in <a href="'.e(route('admin.puja-requests.index')).'">Puja requests</a> with the puja, purpose, place (online, home or temple), preferred dates, names for the sankalp, gotra and birth details. Staff who handle bookings get an alert for each new request.',
            'Open a request, press <strong>Call</strong> or <strong>WhatsApp</strong>, agree the details, then set the status to <strong>Date fixed</strong> with the date, time, priest, dakshina and (for online pujas) the live video link. A signed-in customer is notified and sees it under <em>My account › Puja requests</em>.',
            'After the puja set the status to <strong>Completed</strong>. Use <strong>Cancelled</strong> or <strong>Spam</strong> to clear the list. <em>Upcoming pujas</em> shows every fixed date in order.',
            'At the bottom of the Puja requests page you can edit the list of pujas in the drop-down, the text at the top of the form, and pause new requests.',
            'Export CSV downloads all requests for a spreadsheet.',
        ]],
        'languages' => ['Languages and translation', [
            'Every page has a globe button at the top. English is the default; visitors can switch to हिन्दी, বাংলা, मराठी, తెలుగు, தமிழ், ગુજરાતી, اردو or ಕನ್ನಡ, and the choice is remembered.',
            'Text is translated by AI with the key from <a href="'.e(route('admin.assistant.edit')).'">AI assistant</a> and saved, so each sentence is translated only once. Until a sentence is translated it is shown in English.',
            '<a href="'.e(route('admin.languages.index')).'">Languages</a> shows progress per language. <em>Translate now</em> runs the waiting text immediately; <em>Translate the whole website now</em> opens every page in every language in the background (10–30 minutes).',
            'To correct a translation, choose the language under <em>Edit translations</em>, search for the English text, change it and press Save. AI never overwrites a line you edited. Empty a line and save to let AI translate it again.',
            'Untick a language under Settings to hide it from visitors. Personal details (customer names, account pages) are never sent to AI.',
        ]],
        'analytics' => ['Analytics: visitors, free tools and the app', [
            '<a href="'.e(route('admin.analytics.website')).'">Website visitors</a>: visitors, visits, page views, time spent, bounce rate, people on the site right now, most viewed pages, where visits start, <strong>what people click</strong>, countries, devices, systems, browsers, languages, other websites sending visitors and campaigns. Choose Today, 7 days, 30 days, 90 days, 12 months or your own dates.',
            '<a href="'.e(route('admin.analytics.tools')).'">Free tools &amp; Kundli</a>: how many Kundlis, matchings and other calculations were made, by how many people, from the website or the app, and how many <strong>PDFs were downloaded</strong>. Below is the full <strong>history</strong>: name, birth details, result, country and device. Open a row to see the full result, the visitor\'s recent activity, or download the PDF. Export CSV downloads it all.',
            '<a href="'.e(route('admin.analytics.app')).'">Mobile app</a>: app opens per day, phones using the app, most used features, calculators used in the app, versions, countries and languages. Screens viewed and versions fill in with the next app build.',
            'Only staff with the "calculator history" permission (owner and administrator) see names and birth details; every view is in the audit log. Birth details are stored encrypted.',
            'Settings at the bottom of Website visitors: switch counting on or off, respect "Do Not Track", switch the calculator history off, and choose after how many days old data is deleted (default 395).',
            'Countries come from a free IP list (DB-IP) loaded on each deploy and every month. No visitor IP address is saved or sent to another company.',
        ]],
        'pdf' => ['PDF templates', [
            'Every calculator result has a <strong>Download PDF</strong> button. <a href="'.e(route('admin.pdf-templates.index')).'">PDF templates</a> sets how the PDF looks.',
            'Edit the <strong>default template</strong> first: title, colour, text under the title, closing box (for example "Book a consultation"), small print, watermark, paper size, and whether to include birth details, Kundli charts and your contact details.',
            'Open a single calculator to give it its own template, leave out sections, or switch its PDF off. "Use the default template again" removes its own settings.',
            'Press Preview to see a sample with made-up details, or Download sample PDF.',
        ]],
        'rashifal' => ['Rashifal', [
            '<a href="'.e(route('admin.rashifal.live')).'">Rashifal</a> shows exactly what the website shows today for all 12 signs. Choose the type (Rashifal, Nakshatra, Chinese, Numerology, Tarot), period, topic and language.',
            'Each sign shows who wrote it (AI, built-in engine or staff). Change the text and press Save: it is live at once, and AI will not replace it. The earlier text is kept in the audit log.',
            '"All entries &amp; write new" opens the full list, where you can write a Rashifal for a future date and publish drafts.',
        ]],
        'customers' => ['Customers, enquiries and bookings', [
            '<a href="'.e(route('admin.enquiries.index')).'">Enquiries</a>: messages from the contact and service forms. Call back, then mark Contacted or Closed.',
            '<a href="'.e(route('admin.ops.bookings')).'">Bookings &amp; hours</a>: consultation slots, weekly hours, leave days and booked consultations.',
            '<a href="'.e(route('admin.ops.tickets')).'">Support</a>: customer help requests; reply from the ticket page.',
            '<a href="'.e(route('admin.customers.index')).'">Customers</a>: look up an account, its orders and bookings; restrict an abusive account.',
            '<a href="'.e(route('admin.reviews.index')).'">Reviews</a>: approve or hide reviews before they show on the website.',
            '<a href="'.e(route('admin.privacy.index')).'">Privacy requests</a>: data export and deletion requests from customers.',
        ]],
        'fulfilment' => ['Reports, palmistry and gem orders', [
            '<a href="'.e(route('admin.ops.reports')).'">Reports to write</a>: paid kundli and other reports; write or upload the PDF and send it.',
            '<a href="'.e(route('admin.palm.index')).'">Palmistry</a>: palm photo readings; claim one, write the reading or ask for better photos.',
            '<a href="'.e(route('admin.store.orders')).'">Gem orders</a>: pack, book the courier and update the shipment.',
            '<a href="'.e(route('admin.ops.pujas')).'">Old puja bookings</a>: only for puja orders paid before online puja was switched off.',
        ]],
        'catalogue' => ['Services, store and prices', [
            '<a href="'.e(route('admin.catalogue.index')).'">Catalogue</a>: consultations, reports and other services, their prices, modes and text. Set a service to Active to show it, Draft or Paused to hide it.',
            '<a href="'.e(route('admin.store.products')).'">Gem store</a>: products, photos, stock and prices. Use ✨ AI to improve product text.',
            '<a href="'.e(route('admin.coupons.index')).'">Coupons</a>: discount codes with limits and dates.',
        ]],
        'website' => ['Website content', [
            '<a href="'.e(route('admin.website.edit')).'">Website &amp; home page</a>: announcement bar, home page sections and banners.',
            '<a href="'.e(route('admin.profile.edit')).'">About &amp; awards</a>, <a href="'.e(route('admin.gallery.index')).'">Gallery</a>, <a href="'.e(route('admin.blog.index')).'">Articles</a> <a href="'.e(route('admin.faqs.index')).'">FAQs</a> edit the matching public pages. The footer "Source code" link is switched on or off in Settings.',
            '<a href="'.e(route('admin.app.edit')).'">Mobile app</a> and <a href="'.e(route('admin.broadcast.index')).'">Push messages</a> control the Android app and send notifications.',
            'Under every text box in the admin there is a ✨ AI bar to write, shorten or translate the text.',
        ]],
        'team' => ['Team and access', [
            '<a href="'.e(route('admin.team.index')).'">Team</a>: <em>Add staff member now</em> creates an account with a password you choose; <em>Invite</em> creates a one-time link where they choose their own.',
            'Roles: <strong>owner</strong> (everything), <strong>administrator</strong> (all daily work and settings), <strong>finance</strong>, <strong>support</strong>, <strong>content</strong> roles and <strong>fulfilment</strong>. Give each person only what they need.',
            'Under <em>Manage</em> you can change roles, reset a password, turn off two-factor sign-in for someone who lost their phone, or suspend the account (signs them out at once).',
            'The last owner cannot be suspended or lose the owner role. Only an owner can change another owner.',
            '<a href="'.e(route('admin.audit.index')).'">Audit log</a> records every sign-in and change, with who did it and when.',
        ]],
        'money' => ['Money', [
            '<a href="'.e(route('admin.finance.index')).'">Finance</a>: orders, refunds, invoices and reconciliation with Razorpay.',
            'Refunds, payouts and commission changes are proposed and approved in separate steps; see the Finance screen for what is waiting.',
        ]],
        'system' => ['Keeping the site healthy', [
            '<a href="'.e(route('admin.health')).'">Operational health</a> shows the queue, scheduler, email, AI and disk. Fix anything red.',
            'Backups: the server runs <code>deploy/backup.sh</code> every night when the cron line from the install is added. Copy <code>/opt/arj-backups</code> off the server regularly.',
            'Updating: upload the new code over the old folder (keep <code>deploy/.env.production</code>) and run <code>deploy/deploy.sh</code>. It backs up, migrates and restarts; your admin account and data are kept.',
        ]],
    ];
@endphp
<x-layouts.admin title="User manual">
    <div class="grid gap-6 lg:grid-cols-[14rem_1fr]">
        <nav aria-label="Manual contents" class="lg:sticky lg:top-24 lg:h-fit">
            <ul class="space-y-1 text-sm">
                @foreach ($sections as $id => [$title])
                    <li><a href="#{{ $id }}" class="block rounded-lg px-3 py-2 no-underline hover:bg-white">{{ $title }}</a></li>
                @endforeach
            </ul>
        </nav>
        <div class="max-w-3xl space-y-6">
            @foreach ($sections as $id => [$title, $points])
                <section id="{{ $id }}" class="card scroll-mt-24 p-5 sm:p-6" aria-labelledby="{{ $id }}-h">
                    <h2 id="{{ $id }}-h" class="text-lg">{{ $title }}</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-ink-800">
                        @foreach ($points as $p)<li>{!! $p !!}</li>@endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
