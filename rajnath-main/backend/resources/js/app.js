// CSP-safe Alpine build: no eval. Behaviour lives in named components, not inline expressions.
import Alpine from '@alpinejs/csp';

// Show/hide panel (mobile nav). Escape closes it and returns focus to the button (x-ref="opener").
Alpine.data('disclosure', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
    escape() {
        if (!this.open) { return; }
        this.open = false;
        if (this.$refs.opener) { this.$refs.opener.focus(); }
    },
    get expanded() { return this.open ? 'true' : 'false'; },
}));

// Admin off-canvas drawer (below lg). While closed on small screens its links are inert, so they
// are out of the Tab order and hidden from screen readers; on lg and up the sidebar is always shown.
Alpine.data('drawer', () => ({
    open: false, desktop: false, mq: null,
    init() {
        this.mq = window.matchMedia('(min-width: 1024px)');
        this.desktop = this.mq.matches;
        this.mq.addEventListener('change', (e) => { this.desktop = e.matches; if (e.matches) { this.open = false; } });
    },
    toggle() {
        this.open = !this.open;
        if (this.open) { this.$nextTick(() => { const f = this.$refs.drawer && this.$refs.drawer.querySelector('input, a'); if (f) { f.focus(); } }); }
    },
    close() { this.open = false; },
    escape() {
        if (!this.open) { return; }
        this.open = false;
        if (this.$refs.opener) { this.$refs.opener.focus(); }
    },
    get expanded() { return this.open ? 'true' : 'false'; },
    get drawerClass() { return this.open ? 'translate-x-0' : ''; },
    get drawerInert() { return !this.open && !this.desktop; },
}));

// Dropdown (disclosure navigation pattern): a button with aria-expanded that shows a panel of links
// or buttons. Click or Enter/Space toggles; ArrowDown/ArrowUp open it and move through the items;
// Home/End jump; Escape closes and returns focus to the button; clicking outside or tabbing away
// closes it. Only one dropdown or mega panel is open at a time.
let dropSeq = 0;
Alpine.data('dropdown', () => ({
    open: false, id: 0,
    init() { this.id = ++dropSeq; },
    get expanded() { return this.open ? 'true' : 'false'; },
    get chevron() { return this.open ? 'rotate-180' : ''; },
    items() { return this.$refs.panel ? [...this.$refs.panel.querySelectorAll('a[href], button:not([disabled])')] : []; },
    show(focus) {
        this.open = true;
        window.dispatchEvent(new CustomEvent('menu-open', { detail: 'd' + this.id }));
        if (focus) {
            this.$nextTick(() => {
                const list = this.items();
                if (!list.length) { return; }
                const current = list.find((el) => el.getAttribute('aria-current'));
                const target = focus === 'last' ? list[list.length - 1] : (focus === 'current' && current ? current : list[0]);
                target.focus();
            });
        }
    },
    toggle() { if (this.open) { this.open = false; } else { this.show(null); } },
    close() { this.open = false; },
    onOther(e) { if (e.detail !== 'd' + this.id) { this.open = false; } },
    escape() {
        if (!this.open) { return; }
        this.open = false;
        if (this.$refs.trigger) { this.$refs.trigger.focus(); }
    },
    focusOut(e) { if (this.open && e.relatedTarget && !this.$root.contains(e.relatedTarget)) { this.open = false; } },
    triggerKey(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); this.show('current'); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); this.show('last'); }
    },
    panelKey(e) {
        const list = this.items();
        const i = list.indexOf(document.activeElement);
        let next = null;
        if (e.key === 'ArrowDown') { next = list[(i + 1) % list.length]; }
        else if (e.key === 'ArrowUp') { next = list[(i - 1 + list.length) % list.length]; }
        else if (e.key === 'Home') { next = list[0]; }
        else if (e.key === 'End') { next = list[list.length - 1]; }
        if (next) { e.preventDefault(); next.focus(); }
    },
}));

// Shows only the settings of the SMS provider currently selected in the dropdown.
Alpine.data('smsPicker', () => ({
    driver: 'none',
    init() { this.driver = this.$el.dataset.driver || 'none'; },
    pick(e) { this.driver = e.target.value; },
    get isTwilio() { return this.driver === 'twilio'; },
    get isFast2sms() { return this.driver === 'fast2sms'; },
    get hasProvider() { return this.driver !== 'none'; },
}));

// Birth-place combobox (WAI-ARIA combobox pattern) backed by /places/search.
Alpine.data('placePicker', () => ({
    query: '', results: [], active: -1, open: false, placeId: '', status: '', timer: null, controller: null,
    init() {
        this.query = this.$el.dataset.label || '';
        this.placeId = this.$el.dataset.id || '';
    },
    get expanded() { return this.open ? 'true' : 'false'; },
    get activeId() { return this.active >= 0 ? 'place-opt-' + this.active : ''; },
    get hasResults() { return this.results.length > 0; },
    onInput() {
        this.placeId = '';
        clearTimeout(this.timer);
        if (this.query.trim().length < 2) { this.results = []; this.open = false; this.status = ''; return; }
        this.timer = setTimeout(() => this.fetch(), 220);
    },
    async fetch() {
        if (this.controller) this.controller.abort();
        this.controller = new AbortController();
        this.status = 'Searching…';
        try {
            const r = await fetch('/places/search?q=' + encodeURIComponent(this.query), { headers: { Accept: 'application/json' }, signal: this.controller.signal });
            if (!r.ok) throw new Error(String(r.status));
            const j = await r.json();
            this.results = j.data; this.active = -1; this.open = true;
            this.status = this.results.length ? this.results.length + ' places found. Use up and down arrows to choose.' : 'No matching place. Try the nearest larger town.';
        } catch (e) {
            if (e.name !== 'AbortError') { this.status = 'Could not search right now. Check your connection and try again.'; this.open = false; }
        }
    },
    onKey(e) {
        if (!this.open || !this.results.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); this.active = (this.active + 1) % this.results.length; }
        else if (e.key === 'ArrowUp') { e.preventDefault(); this.active = (this.active - 1 + this.results.length) % this.results.length; }
        else if (e.key === 'Enter' && this.active >= 0) { e.preventDefault(); this.select(this.active); }
        else if (e.key === 'Escape') { this.open = false; }
    },
    choose(e) { this.select(Number(e.currentTarget.dataset.index)); },
    select(i) {
        const p = this.results[i]; if (!p) return;
        this.query = p.label; this.placeId = String(p.id); this.open = false;
        this.status = 'Selected ' + p.label + ', time zone ' + p.tz + '.';
    },
    close() { setTimeout(() => { this.open = false; }, 150); },
}));

// Disables the "resend code" button until the cooldown ends.
Alpine.data('countdown', () => ({
    left: 0,
    init() {
        this.left = Number(this.$el.dataset.seconds || 0);
        const t = setInterval(() => { if (this.left > 0) this.left--; else clearInterval(t); }, 1000);
    },
    get waiting() { return this.left > 0; },
    get label() { return this.left > 0 ? 'Resend code in ' + this.left + 's' : 'Resend code'; },
}));

// Razorpay Checkout: opens the hosted payment sheet; on success posts ids + signature to the
// server, which verifies the signature and confirms the captured amount with Razorpay.
Alpine.data('razorpayCheckout', () => ({
    busy: false, error: '',
    pay() {
        const d = this.$el.dataset;
        if (!window.Razorpay) { this.error = 'The payment window could not load. Check your connection and try again.'; return; }
        this.busy = true; this.error = '';
        const rzp = new window.Razorpay({
            key: d.key, order_id: d.order, amount: Number(d.amount), currency: 'INR', name: d.name,
            prefill: { name: d.customer, email: d.email || undefined, contact: d.contact || undefined },
            theme: { color: '#141a3c' },
            handler: (res) => {
                this.$refs.pid.value = res.razorpay_payment_id; this.$refs.oid.value = res.razorpay_order_id; this.$refs.sig.value = res.razorpay_signature;
                this.$refs.verify.submit();
            },
            modal: { ondismiss: () => { this.busy = false; } },
        });
        rzp.on('payment.failed', (r) => { this.busy = false; this.error = (r.error && r.error.description) || 'Payment failed. You have not been charged; please try again.'; });
        rzp.open();
    },
}));

// Assistant chat. Text is always rendered with x-text (never HTML).
// AI chat (assistant page and the floating "Ask AI" widget). The conversation is kept in
// sessionStorage, so it carries on from page to page during the visit.
const CHAT_KEY = 'arj-chat-v1';
const loadChat = () => { try { return JSON.parse(sessionStorage.getItem(CHAT_KEY)) || {}; } catch (e) { return {}; } };
Alpine.data('assistant', () => ({
    messages: [], draft: '', busy: false, error: '', conversation: null, handoff: false, limited: false, notice: '',
    init() {
        const saved = loadChat();
        if (Array.isArray(saved.messages)) { this.messages = saved.messages.slice(-30); }
        this.conversation = saved.conversation || null;
        this.handoff = !!saved.handoff;
        this.notice = saved.notice || this.$root.dataset.notice || '';
        // "Ask AI about this" buttons: only the floating widget's chat answers them.
        if (this.$root.id === 'ai-panel') { window.addEventListener('arj-ask', (e) => { this.draft = e.detail.q; this.send(); }); }
        window.addEventListener('arj-chat-sync', () => { const s = loadChat(); if (Array.isArray(s.messages)) { this.messages = s.messages; this.conversation = s.conversation || null; } });
        this.$nextTick(() => this.scroll());
    },
    get empty() { return this.messages.length === 0; },
    get hasNotice() { return this.notice !== ''; },
    get cannotSend() { return this.busy || this.limited || this.draft.trim().length < 2; },
    save() {
        try { sessionStorage.setItem(CHAT_KEY, JSON.stringify({ messages: this.messages.slice(-30), conversation: this.conversation, handoff: this.handoff, notice: this.notice })); } catch (e) { /* private mode */ }
    },
    scroll() { const l = this.$refs.log; if (l) { l.scrollTop = l.scrollHeight; } },
    ask() { const q = this.$el.dataset.q; if (q) { this.draft = q; this.send(); } },
    clear() { this.messages = []; this.conversation = null; this.handoff = false; this.save(); },
    onKey(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.send(); } },
    async send() {
        const text = this.draft.trim();
        if (text.length < 2 || this.busy) return;
        this.messages.push({ id: Date.now(), role: 'user', content: text, cls: 'chat-user', actions: [], hasActions: false });
        this.draft = ''; this.busy = true; this.error = '';
        this.$nextTick(() => this.scroll());
        try {
            const r = await fetch(this.$root.dataset.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ message: text, conversation: this.conversation, page: (document.querySelector('meta[name="arj-page"]') || {}).content || null }),
            });
            const data = await r.json().catch(() => ({}));
            if (r.status === 429) { this.limited = true; this.error = (data.error && data.error.message) || 'Daily limit reached. Please try again tomorrow.'; return; }
            if (r.status === 419) { this.error = 'Your session expired. Please refresh the page and ask again.'; return; }
            if (r.status === 503) { this.error = 'Just a moment, please. The assistant is busy right now; try again in a minute or contact our team.'; this.handoff = true; return; }
            if (!r.ok) { this.error = (data.errors && data.errors.message && data.errors.message[0]) || 'Something went wrong. Please try again.'; return; }
            this.conversation = data.conversation;
            this.handoff = this.handoff || data.handoff;
            this.notice = data.notice || '';
            const actions = (data.actions || []).filter((a) => typeof a.url === 'string' && (a.url.startsWith(location.origin + '/') || a.url.startsWith('/')))
                .map((a, i) => ({ key: i, url: a.url, label: a.label, cls: a.type === 'pay' ? 'btn-gold' : 'btn-outline' }));
            this.messages.push({ id: Date.now() + 1, role: 'assistant', content: data.reply, cls: 'chat-bot', actions, hasActions: actions.length > 0 });
            this.save();
            window.dispatchEvent(new CustomEvent('arj-chat-sync'));
            this.$nextTick(() => this.scroll());
        } catch (e) {
            this.error = 'No connection. Please check your internet and try again.';
        } finally {
            this.busy = false;
        }
    },
}));

// Floating "Ask AI" button + chat panel, on every public and account page.
Alpine.data('aiWidget', () => ({
    open: false,
    toggle() { this.open = !this.open; if (this.open) { this.$nextTick(() => { const t = this.$root.querySelector('textarea'); if (t) { t.focus(); } }); } },
    close() { this.open = false; },
    escape() { if (this.open) { this.open = false; if (this.$refs.opener) { this.$refs.opener.focus(); } } },
    get expanded() { return this.open ? 'true' : 'false'; },
    get openerLabel() { return this.open ? 'Close the AI assistant' : 'Ask AI'; },
    init() { window.addEventListener('arj-ask', () => { this.open = true; }); },
}));

// "Ask AI about this" button (<x-ask-ai>): opens the chat and asks the button's question
// with the current page as context.
Alpine.data('askAi', () => ({
    go() { window.dispatchEvent(new CustomEvent('arj-ask', { detail: { q: this.$el.dataset.q } })); },
}));

// Scroll reveal for sections below the fold, and number counters (data-count="1,00,000+").
const motionOk = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (motionOk && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
            if (!e.isIntersecting) { return; }
            e.target.classList.remove('reveal-pending');
            e.target.querySelectorAll('[data-count]').forEach((el) => {
                const raw = el.dataset.count; const m = raw.match(/[\d,.]+/);
                if (!m) { return; }
                const target = parseFloat(m[0].replace(/,/g, '')); const dec = (m[0].split('.')[1] || '').length;
                const t0 = performance.now();
                const step = (t) => {
                    const p = Math.min(1, (t - t0) / 1400); const v = target * (1 - Math.pow(1 - p, 3));
                    el.textContent = raw.replace(m[0], dec ? v.toFixed(dec) : Math.round(v).toLocaleString('en-IN'));
                    if (p < 1) { requestAnimationFrame(step); } else { el.textContent = raw; }
                };
                requestAnimationFrame(step);
            });
            io.unobserve(e.target);
        });
    }, { rootMargin: '0px 0px -8% 0px' });
    document.querySelectorAll('.reveal').forEach((el) => {
        if (el.getBoundingClientRect().top > window.innerHeight * 0.92) { el.classList.add('reveal-pending'); }
        io.observe(el);
    });
}

// In-app consultation call (LiveKit). The SDK is loaded only on the call page.
Alpine.data('callRoom', () => ({
    state: 'idle', error: '', micOn: true, camOn: true, remoteCount: 0, room: null,
    get idle() { return this.state === 'idle' || this.state === 'ended'; },
    get connecting() { return this.state === 'connecting'; },
    get live() { return this.state === 'live'; },
    get ended() { return this.state === 'ended'; },
    get micLabel() { return this.micOn ? 'Mute' : 'Unmute'; },
    get camLabel() { return this.camOn ? 'Turn camera off' : 'Turn camera on'; },
    get waiting() { return this.live && this.remoteCount === 0; },
    get audioOnly() { return this.$root.dataset.audioOnly === '1'; },
    get camControl() { return this.live && this.$root.dataset.audioOnly !== '1'; },
    async join() {
        this.state = 'connecting'; this.error = '';
        try {
            const r = await fetch(this.$root.dataset.tokenUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const d = await r.json().catch(() => ({}));
            if (!r.ok) { this.state = 'idle'; this.error = (d.error && d.error.message) || 'Could not start the call.'; return; }
            const { Room, RoomEvent, Track } = await import('livekit-client');
            const room = new Room({ adaptiveStream: true, dynacast: true });
            this.room = room;
            const remote = this.$refs.remote, local = this.$refs.local;
            room.on(RoomEvent.TrackSubscribed, (track) => {
                const el = track.attach();
                el.dataset.sid = track.sid;
                if (track.kind === Track.Kind.Video) { el.className = 'h-full w-full rounded-2xl bg-ink-900 object-cover'; }
                remote.appendChild(el);
            });
            room.on(RoomEvent.TrackUnsubscribed, (track) => { track.detach().forEach((el) => el.remove()); });
            room.on(RoomEvent.ParticipantConnected, () => { this.remoteCount = room.remoteParticipants.size; });
            room.on(RoomEvent.ParticipantDisconnected, () => { this.remoteCount = room.remoteParticipants.size; });
            room.on(RoomEvent.Disconnected, () => { this.state = 'ended'; });
            await room.connect(d.url, d.token);
            await room.localParticipant.setMicrophoneEnabled(true);
            if (!d.audio_only) {
                const pub = await room.localParticipant.setCameraEnabled(true);
                if (pub && pub.track) { const el = pub.track.attach(); el.muted = true; el.className = 'h-full w-full rounded-xl object-cover'; local.appendChild(el); }
            } else { this.camOn = false; }
            this.remoteCount = room.remoteParticipants.size;
            this.state = 'live';
        } catch (e) {
            this.state = 'idle';
            this.error = (e && e.name === 'NotAllowedError') ? 'Please allow microphone/camera access in your browser and try again.' : 'Could not connect the call. Check your internet and try again.';
            if (this.room) { this.room.disconnect(); }
        }
    },
    async toggleMic() { this.micOn = !this.micOn; await this.room.localParticipant.setMicrophoneEnabled(this.micOn); },
    async toggleCam() { this.camOn = !this.camOn; await this.room.localParticipant.setCameraEnabled(this.camOn); if (!this.camOn) { this.$refs.local.innerHTML = ''; } else { const p = this.room.localParticipant.getTrackPublications().find((x) => x.kind === 'video'); if (p && p.track) { const el = p.track.attach(); el.muted = true; el.className = 'h-full w-full rounded-xl object-cover'; this.$refs.local.appendChild(el); } } },
    leave() { if (this.room) { this.room.disconnect(); } this.state = 'ended'; this.$refs.remote.innerHTML = ''; this.$refs.local.innerHTML = ''; },
}));

Alpine.data('printer', () => ({ print() { window.print(); } }));

// Admin sidebar filter: hides sections whose name does not contain the typed text. While a
// search is typed every group is expanded; clearing the box puts the groups back as they were.
Alpine.data('navFilter', () => ({
    filter(e) {
        const q = e.target.value.trim().toLowerCase();
        const nav = document.querySelector('[data-nav]');
        if (!nav) { return; }
        nav.querySelectorAll('[data-nav-item]').forEach((li) => { li.hidden = q !== '' && !li.dataset.label.includes(q); });
        nav.querySelectorAll('[data-nav-group]').forEach((g) => {
            g.hidden = [...g.querySelectorAll('[data-nav-item]')].every((li) => li.hidden);
            if (g.tagName !== 'DETAILS') { return; }
            if (q !== '') {
                if (g.dataset.wasOpen === undefined) { g.dataset.wasOpen = g.open ? '1' : '0'; }
                g.open = true;
            } else if (g.dataset.wasOpen !== undefined) {
                g.open = g.dataset.wasOpen === '1';
                delete g.dataset.wasOpen;
            }
        });
    },
}));

// Admin sidebar groups (<details>): remembers which groups the user expanded or collapsed in this
// browser. The group holding the current page is always expanded.
Alpine.data('navGroups', () => ({
    init() {
        const key = 'arj.admin.nav';
        let saved = {};
        try { saved = JSON.parse(localStorage.getItem(key) || '{}') || {}; } catch (e) { saved = {}; }
        this.$el.querySelectorAll('details[data-nav-group]').forEach((d) => {
            const name = d.dataset.group;
            if (d.dataset.active !== '1' && Object.prototype.hasOwnProperty.call(saved, name)) { d.open = saved[name] === true; }
            d.addEventListener('toggle', () => {
                if (d.dataset.wasOpen !== undefined) { return; }       // opened by the filter, not by the user
                saved[name] = d.open;
                try { localStorage.setItem(key, JSON.stringify(saved)); } catch (e) { /* private mode */ }
            });
        });
    },
}));

// Mega-menu item: opens on click, or on hover with a mouse; one panel open at a time;
// Escape closes and returns focus to the button; clicking outside or tabbing out closes.
let megaSeq = 0;
Alpine.data('megaItem', () => ({
    open: false, id: 0, timer: null, hoveredAt: 0,
    init() { this.id = ++megaSeq; },
    get expanded() { return this.open ? 'true' : 'false'; },
    get chevron() { return this.open ? 'rotate-180' : ''; },
    show() {
        clearTimeout(this.timer);
        this.open = true;
        window.dispatchEvent(new CustomEvent('menu-open', { detail: 'm' + this.id }));
    },
    // A click that lands just after hover opened the panel keeps it open instead of closing it.
    toggle() { if (this.open && Date.now() - this.hoveredAt > 400) { this.open = false; } else { this.show(); } },
    close() { clearTimeout(this.timer); this.open = false; },
    canHover() { return window.matchMedia('(hover: hover) and (pointer: fine)').matches; },
    hoverIn() { if (!this.canHover()) { return; } clearTimeout(this.timer); this.timer = setTimeout(() => { if (!this.open) { this.hoveredAt = Date.now(); } this.show(); }, 90); },
    hoverOut() { if (!this.canHover()) { return; } clearTimeout(this.timer); this.timer = setTimeout(() => { this.open = false; }, 160); },
    onOther(e) { if (e.detail !== 'm' + this.id) { this.close(); } },
    escape() { if (this.open) { this.close(); this.$refs.trigger.focus(); } },
    focusOut(e) { if (this.open && e.relatedTarget && !this.$root.contains(e.relatedTarget)) { this.close(); } },
}));

// Gallery viewer: thumbnails carry data-lb attributes; arrows, Escape and swipe navigate.
// YouTube loads (youtube-nocookie.com) only after the visitor presses play.
Alpine.data('lightbox', () => ({
    items: [], index: -1, playing: false, opener: null, touchX: null,
    init() {
        this.items = [...this.$el.querySelectorAll('[data-lb]')].map((el) => ({
            kind: el.dataset.kind, src: el.dataset.src, caption: el.dataset.caption || '', video: el.dataset.video || '',
        }));
    },
    get isOpen() { return this.index >= 0; },
    get current() { return this.items[this.index] || {}; },
    get isPhoto() { return this.isOpen && this.current.kind === 'photo'; },
    get isVideo() { return this.isOpen && this.current.kind === 'video'; },
    get showPlay() { return this.isVideo && !this.playing; },
    get showFrame() { return this.isVideo && this.playing; },
    get src() { return this.current.src || ''; },
    get caption() { return this.current.caption || ''; },
    get counter() { return this.isOpen ? `${this.index + 1} / ${this.items.length}` : ''; },
    get embed() { return this.showFrame && /^[A-Za-z0-9_-]{11}$/.test(this.current.video) ? `https://www.youtube-nocookie.com/embed/${this.current.video}?autoplay=1&rel=0` : ''; },
    openAt(e) {
        this.opener = e.currentTarget;
        this.index = Number(e.currentTarget.dataset.index);
        this.playing = false;
        document.documentElement.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.close && this.$refs.close.focus());
    },
    close() {
        this.index = -1;
        this.playing = false;
        document.documentElement.classList.remove('overflow-hidden');
        if (this.opener) { this.opener.focus(); }
    },
    go(step) {
        if (!this.items.length) { return; }
        this.index = (this.index + step + this.items.length) % this.items.length;
        this.playing = false;
    },
    next() { this.go(1); },
    prev() { this.go(-1); },
    play() { this.playing = true; },
    key(e) {
        if (!this.isOpen) { return; }
        if (e.key === 'Escape') { this.close(); } else if (e.key === 'ArrowRight') { this.next(); } else if (e.key === 'ArrowLeft') { this.prev(); }
    },
    touchStart(e) { this.touchX = e.changedTouches[0].clientX; },
    touchEnd(e) {
        if (this.touchX === null) { return; }
        const dx = e.changedTouches[0].clientX - this.touchX;
        this.touchX = null;
        if (Math.abs(dx) > 50) { if (dx < 0) { this.next(); } else { this.prev(); } }
    },
}));

window.Alpine = Alpine;
Alpine.start();

// PWA: offline fallback page + static asset caching (never caches account/admin/API responses).
if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === '127.0.0.1' || location.hostname === 'localhost')) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}


// Admin: a small "✨ AI" bar under every text box (write, improve, shorten, expand, Hindi,
// English, reply). It calls /admin/ai/assist and only fills the box; staff still press Save.
// Skip a box with data-no-ai. Secrets and passwords are never sent.
const aiBox = document.querySelector('meta[name="arj-admin-ai"]');
if (aiBox) {
    const endpoint = aiBox.content;
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const skip = /secret|password|service_account|token|key$/i;
    const contextFor = (ta) => {
        const form = ta.closest('form');
        const parts = [`Page: ${document.title}`];
        if (form) {
            form.querySelectorAll('input[type=text], input:not([type]), textarea, select').forEach((el) => {
                if (el === ta || !el.name || skip.test(el.name) || !el.value) { return; }
                const label = (form.querySelector(`label[for="${el.id}"]`) || {}).textContent || el.name;
                parts.push(`${label.trim()}: ${String(el.value).slice(0, 600)}`);
            });
        }
        return parts.join('\n').slice(0, 5800);
    };
    const actions = [['write', '✨ Write'], ['improve', 'Improve'], ['shorten', 'Shorter'], ['expand', 'More detail'], ['hindi', 'हिन्दी'], ['english', 'English']];
    document.querySelectorAll('textarea').forEach((ta) => {
        if (ta.dataset.noAi !== undefined || skip.test(ta.name || '') || ta.readOnly || ta.disabled) { return; }
        const bar = document.createElement('div');
        bar.className = 'mt-1.5 flex flex-wrap items-center gap-1.5 text-xs print:hidden';
        const status = document.createElement('span');
        status.className = 'text-ink-600';
        status.setAttribute('role', 'status');
        let previous = null;
        const undo = document.createElement('button');
        undo.type = 'button'; undo.textContent = 'Undo AI'; undo.hidden = true;
        undo.className = 'rounded-full border border-ink-200 bg-white px-2.5 py-1 font-medium text-ink-700 hover:bg-ivory-100';
        undo.addEventListener('click', () => { if (previous !== null) { ta.value = previous; previous = null; undo.hidden = true; status.textContent = ''; } });
        const list = /reply|note|body/.test(ta.name) && /ticket|enquir|review/.test(location.pathname) ? [['reply', '✨ Draft reply'], ...actions.slice(1)] : actions;
        list.forEach(([action, label]) => {
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = label;
            b.className = 'rounded-full border border-gold-300 bg-gold-50 px-2.5 py-1 font-medium text-gold-700 hover:bg-gold-100 disabled:opacity-50';
            b.addEventListener('click', async () => {
                if (action !== 'write' && action !== 'reply' && ta.value.trim().length < 3) { status.textContent = 'Type some text first, or use Write.'; return; }
                bar.querySelectorAll('button').forEach((x) => { x.disabled = true; });
                status.textContent = 'AI is writing…';
                try {
                    const r = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                        body: JSON.stringify({ action, field: ta.name || ta.id || 'text', text: ta.value, context: contextFor(ta) }),
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.text) { status.textContent = (data.error && data.error.message) || (r.status === 429 ? 'Too many requests. Wait a minute.' : 'AI could not answer. Try again.'); return; }
                    previous = ta.value; ta.value = data.text; undo.hidden = false;
                    ta.dispatchEvent(new Event('input', { bubbles: true }));
                    status.textContent = 'Draft added. Check it, then Save.';
                } catch (e) {
                    status.textContent = 'No connection.';
                } finally {
                    bar.querySelectorAll('button').forEach((x) => { x.disabled = false; });
                }
            });
            bar.appendChild(b);
        });
        bar.appendChild(undo); bar.appendChild(status);
        ta.insertAdjacentElement('afterend', bar);
    });
}
