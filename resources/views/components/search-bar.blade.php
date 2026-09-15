@props([
    'action',                 // where the search form submits
    'suggest' => null,        // JSON endpoint for type-ahead; omit for a plain search box
    'placeholder' => 'Search...',
    'name' => 'q',
    'value' => null,
    'hidden' => [],           // filters to carry through the search, e.g. ['category' => request('category')]
])

@php $value = $value ?? request($name); @endphp

<form method="GET" action="{{ $action }}" class="search-bar-wrap" data-search-form
      @if ($suggest) data-suggest-url="{{ $suggest }}" @endif autocomplete="off">
    <i class="ti ti-search"></i>

    @foreach ($hidden as $key => $hiddenValue)
        <input type="hidden" name="{{ $key }}" value="{{ $hiddenValue }}">
    @endforeach

    <input type="text" name="{{ $name }}" value="{{ $value }}" class="search-input"
           placeholder="{{ $placeholder }}" role="combobox" aria-expanded="false" aria-autocomplete="list">

    @if ($value)
        <a href="{{ $action }}{{ count(array_filter($hidden)) ? '?'.http_build_query(array_filter($hidden)) : '' }}"
           class="search-clear" title="Clear search" aria-label="Clear search">
            <i class="ti ti-x"></i>
        </a>
    @endif

    <div class="suggest-box" role="listbox" hidden></div>
</form>

@once
    <style>
        .search-clear {
            position:absolute; right:14px; top:50%; transform:translateY(-50%);
            width:26px; height:26px; border-radius:50%; display:flex; align-items:center;
            justify-content:center; color:var(--muted); text-decoration:none; font-size:14px;
        }
        .search-clear:hover { background:var(--panel-bg); color:var(--text); }

        .suggest-box {
            position:absolute; top:calc(100% + 6px); left:0; right:0; z-index:60;
            background:var(--card-bg); border:1px solid var(--glass-border); border-radius:14px;
            padding:6px; box-shadow:0 14px 32px rgba(0,0,0,.16); max-height:320px; overflow-y:auto;
        }
        .suggest-item {
            display:flex; align-items:center; gap:10px; width:100%; padding:9px 11px;
            border:none; background:none; border-radius:10px; cursor:pointer; text-align:left;
        }
        .suggest-item:hover, .suggest-item.highlighted { background:var(--panel-bg); }
        .suggest-item > i { font-size:16px; color:var(--primary); width:18px; text-align:center; flex-shrink:0; }
        .suggest-item .s-text { min-width:0; flex:1; }
        .suggest-item .s-label {
            display:block; font-size:12.5px; font-weight:600; color:var(--text);
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .suggest-item .s-label mark { background:rgba(23,88,131,.16); color:var(--primary); padding:0 1px; border-radius:3px; }
        .suggest-item .s-meta { font-size:10.5px; color:var(--muted); }
        .suggest-item .s-go { font-size:13px; color:var(--muted); flex-shrink:0; }
        .suggest-empty { padding:12px; font-size:11.5px; color:var(--muted); text-align:center; }
    </style>

    <script>
        /**
         * Type-ahead for every search bar on the page. Suggestions come from the
         * form's data-suggest-url; picking one either fills the box and searches, or
         * follows the filter shortcut the server attached to that row.
         */
        (function () {
            const MIN_CHARS = 2;

            // Deferred: this block renders with the first search bar, before any later one exists.
            document.addEventListener('DOMContentLoaded', () =>
                document.querySelectorAll('form[data-search-form][data-suggest-url]').forEach(setup));

            function setup(form) {
                const input = form.querySelector('.search-input');
                const box = form.querySelector('.suggest-box');
                const url = form.dataset.suggestUrl;
                let timer = null, controller = null, items = [], highlighted = -1;

                input.addEventListener('input', () => {
                    clearTimeout(timer);
                    const term = input.value.trim();
                    if (term.length < MIN_CHARS) return close();
                    timer = setTimeout(() => fetchSuggestions(term), 180);
                });

                input.addEventListener('keydown', e => {
                    if (box.hidden) return;
                    if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
                    else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
                    else if (e.key === 'Enter' && highlighted > -1) { e.preventDefault(); choose(items[highlighted]); }
                    else if (e.key === 'Escape') { close(); }
                });

                input.addEventListener('focus', () => {
                    if (input.value.trim().length >= MIN_CHARS && items.length) open();
                });

                document.addEventListener('click', e => {
                    if (!form.contains(e.target)) close();
                });

                function fetchSuggestions(term) {
                    if (controller) controller.abort();
                    controller = new AbortController();

                    fetch(url + '?q=' + encodeURIComponent(term), {
                        signal: controller.signal,
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    })
                        .then(r => r.ok ? r.json() : [])
                        .then(data => render(data, term))
                        .catch(() => {}); // aborted or offline — leave the box as it is
                }

                function render(data, term) {
                    items = data;
                    highlighted = -1;
                    box.innerHTML = '';

                    if (!data.length) {
                        box.innerHTML = '<div class="suggest-empty">No matches — press Enter to search anyway.</div>';
                        return open();
                    }

                    data.forEach((item, index) => {
                        const row = document.createElement('button');
                        row.type = 'button';
                        row.className = 'suggest-item';
                        row.setAttribute('role', 'option');
                        row.innerHTML =
                            '<i class="ti ' + (item.icon || 'ti-search') + '"></i>' +
                            '<span class="s-text">' +
                                '<span class="s-label">' + highlight(item.label, term) + '</span>' +
                                (item.meta ? '<span class="s-meta">' + escapeHtml(item.meta) + '</span>' : '') +
                            '</span>' +
                            (item.url ? '<i class="ti ti-filter s-go"></i>' : '');
                        row.addEventListener('click', () => choose(item));
                        row.addEventListener('mouseenter', () => setHighlight(index));
                        box.appendChild(row);
                    });

                    open();
                }

                function choose(item) {
                    if (!item) return;
                    if (item.url) { window.location.href = item.url; return; }
                    input.value = item.value ?? item.label;
                    close();
                    form.submit();
                }

                function move(step) {
                    if (!items.length) return;
                    setHighlight((highlighted + step + items.length) % items.length);
                }

                function setHighlight(index) {
                    highlighted = index;
                    box.querySelectorAll('.suggest-item').forEach((row, i) =>
                        row.classList.toggle('highlighted', i === index));
                }

                function open() {
                    box.hidden = false;
                    input.setAttribute('aria-expanded', 'true');
                }

                function close() {
                    box.hidden = true;
                    highlighted = -1;
                    input.setAttribute('aria-expanded', 'false');
                }
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text ?? '';
                return div.innerHTML;
            }

            function highlight(label, term) {
                const safe = escapeHtml(label);
                const needle = escapeHtml(term);
                const at = safe.toLowerCase().indexOf(needle.toLowerCase());
                if (at === -1) return safe;

                return safe.slice(0, at) + '<mark>' + safe.slice(at, at + needle.length) + '</mark>' + safe.slice(at + needle.length);
            }
        })();
    </script>
@endonce
