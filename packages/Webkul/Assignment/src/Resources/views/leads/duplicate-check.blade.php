@pushOnce('scripts')
    <script>
        (() => {
            const endpoint = @json(route('admin.leads.duplicates.check'));
            let timer = null;
            let banner = null;

            function ensureBanner() {
                if (banner) return banner;
                banner = document.createElement('div');
                banner.id = 'lead-duplicate-banner';
                banner.className = 'hidden rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100';
                const host = document.querySelector('form') || document.body;
                host.prepend(banner);
                return banner;
            }

            function readValue(selectors) {
                for (const selector of selectors) {
                    const el = document.querySelector(selector);
                    if (el && el.value) return el.value.trim();
                }
                return '';
            }

            function check() {
                const email = readValue(['input[name="person[emails][0][value]"]', 'input[name*="[emails]"][type="email"]', 'input[type="email"]']);
                const phone = readValue(['input[name="person[contact_numbers][0][value]"]', 'input[name*="contact_numbers"]']);
                const personId = readValue(['input[name="person_id"]', 'input[name="person[id]"]']);

                if (! email && ! phone && ! personId) {
                    ensureBanner().classList.add('hidden');
                    return;
                }

                const params = new URLSearchParams();
                if (email) params.set('email', email);
                if (phone) params.set('phone', phone);
                if (personId) params.set('person_id', personId);

                fetch(`${endpoint}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then((r) => r.json())
                    .then((payload) => {
                        const el = ensureBanner();
                        if (! payload.count) {
                            el.classList.add('hidden');
                            el.textContent = '';
                            return;
                        }
                        const titles = (payload.data || []).slice(0, 3).map((row) => row.title || ('#' + row.id)).join(', ');
                        el.textContent = `Possible duplicates found (${payload.count}): ${titles}`;
                        el.classList.remove('hidden');
                    })
                    .catch(() => {});
            }

            document.addEventListener('input', (event) => {
                const name = event.target?.getAttribute?.('name') || '';
                if (! /email|contact_number|person/i.test(name) && event.target?.type !== 'email') {
                    return;
                }
                clearTimeout(timer);
                timer = setTimeout(check, 450);
            });
        })();
    </script>
@endPushOnce
