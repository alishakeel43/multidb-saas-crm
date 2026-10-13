<script>
    (function () {
        if (window.__maAttributionInstalled) {
            return;
        }

        window.__maAttributionInstalled = true;

        function read(search) {
            var params = new URLSearchParams(search || '');
            var keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
            var data = {};

            keys.forEach(function (key) {
                if (params.get(key)) {
                    data[key] = params.get(key);
                }
            });

            return data;
        }

        var data = read(window.location.search);

        try {
            var referrer = new URL(document.referrer);
            var fromReferrer = read(referrer.search);

            Object.keys(fromReferrer).forEach(function (key) {
                if (!data[key]) {
                    data[key] = fromReferrer[key];
                }
            });
        } catch (error) {}

        data.ma_referrer = document.referrer || '';
        data.ma_landing_url = window.location.href;

        document.addEventListener('submit', function (event) {
            var form = event.target;

            if (!form || !form.appendChild) {
                return;
            }

            Object.keys(data).forEach(function (key) {
                if (!data[key]) {
                    return;
                }

                var input = form.querySelector('input[name="' + key + '"]');

                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    form.appendChild(input);
                }

                input.value = data[key];
            });
        }, true);
    })();
</script>
