(function () {
    var root = document.getElementById('admin-search');
    var trigger = document.getElementById('admin-search-open');
    var catalogNode = document.getElementById('admin-search-catalog');
    if (!root || !trigger || !catalogNode) return;

    var input = root.querySelector('[data-admin-search-input]');
    var clearBtn = root.querySelector('[data-admin-search-clear]');
    var list = root.querySelector('[data-admin-search-list]');
    var count = root.querySelector('[data-admin-search-count]');
    var shortcut = root.querySelector('[data-admin-search-shortcut]');
    var triggerShortcut = document.querySelector('[data-admin-search-trigger-shortcut]');
    var adminId = root.getAttribute('data-admin-id');
    var storageKey = 'bitex-admin-search:' + adminId;
    var items = [];

    try {
        items = JSON.parse(catalogNode.textContent || '[]');
    } catch (error) {
        items = [];
    }

    var active = 0;
    var visible = [];
    var store = loadStore();
    var mac = /Mac|iPhone|iPad/.test(navigator.platform || '');
    var shortcutLabel = mac ? '⌘K' : 'Ctrl K';
    if (shortcut) shortcut.textContent = shortcutLabel;
    if (triggerShortcut) triggerShortcut.textContent = shortcutLabel;

    trigger.addEventListener('click', open);
    root.querySelector('[data-admin-search-close]').addEventListener('click', close);
    input.addEventListener('input', function () {
        active = 0;
        render();
    });
    clearBtn.addEventListener('click', function () {
        input.value = '';
        active = 0;
        input.focus();
        render();
    });

    document.addEventListener('keydown', function (event) {
        var key = (event.key || '').toLowerCase();
        if ((event.ctrlKey || event.metaKey) && event.code === 'KeyK') {
            event.preventDefault();
            root.hidden ? open() : close();
            return;
        }
        if (root.hidden) return;
        if (key === 'escape') {
            event.preventDefault();
            close();
        } else if (key === 'arrowdown') {
            event.preventDefault();
            move(1);
        } else if (key === 'arrowup') {
            event.preventDefault();
            move(-1);
        } else if (key === 'enter') {
            event.preventDefault();
            choose(visible[active]);
        }
    });

    function open() {
        root.hidden = false;
        document.body.classList.add('admin-search-open');
        trigger.setAttribute('aria-expanded', 'true');
        active = 0;
        render();
        input.focus();
        input.select();
    }

    function close() {
        if (root.hidden) return;
        root.hidden = true;
        document.body.classList.remove('admin-search-open');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.focus();
    }

    function move(step) {
        if (!visible.length) return;
        active = (active + step + visible.length) % visible.length;
        paintActive();
    }

    function render() {
        var query = normalize(input.value);
        var tokens = query ? query.split(' ').filter(Boolean) : [];
        clearBtn.hidden = input.value.length === 0;
        input.closest('.admin-search__field').classList.toggle('has-query', input.value.length > 0);
        list.textContent = '';
        visible = [];

        if (!tokens.length) {
            renderHome();
        } else {
            renderResults(tokens);
        }

        if (!visible.length) {
            list.appendChild(emptyState(tokens));
        }

        count.textContent = tokens.length ? visible.length + ' نتیجه' : '';
        paintActive();
    }

    function renderHome() {
        var frequent = items
            .filter(function (item) { return (store.counts[item.id] || 0) >= 2; })
            .sort(function (a, b) { return (store.counts[b.id] || 0) - (store.counts[a.id] || 0); })
            .slice(0, 4);
        var frequentIds = frequent.map(function (item) { return item.id; });
        var history = idsToItems(store.history).filter(function (item) {
            return frequentIds.indexOf(item.id) === -1;
        }).slice(0, 4);
        var used = {};
        history.concat(frequent).forEach(function (item) { used[item.id] = true; });
        var popular = items
            .filter(function (item) { return item.popular > 0 && !used[item.id]; })
            .sort(function (a, b) { return b.popular - a.popular; })
            .slice(0, 6);

        addSection('تاریخچه', history, history.length ? clearHistoryButton() : null);
        addSection('پر جستجو', frequent, null);
        addSection('پرکاربرد', popular, null);
    }

    function renderResults(tokens) {
        var ranked = items.map(function (item, index) {
            return { item: item, index: index, score: scoreItem(item, tokens) };
        }).filter(function (row) {
            return row.score > 0;
        }).sort(function (a, b) {
            return b.score - a.score || b.item.popular - a.item.popular || a.index - b.index;
        }).slice(0, 18);

        if (!ranked.length) return;
        addSection('نتایج', ranked.map(function (row) { return row.item; }), null, tokens);
    }

    function addSection(title, rows, extra, tokens) {
        if (!rows.length) return;
        var head = el('div', 'admin-search__section');
        head.appendChild(el('span', null, title));
        if (extra) head.appendChild(extra);
        list.appendChild(head);
        rows.forEach(function (item) {
            visible.push(item);
            list.appendChild(row(item, tokens || []));
        });
    }

    function row(item, tokens) {
        var button = el('button', 'admin-search__item');
        button.type = 'button';
        button.setAttribute('role', 'option');
        var icon = el('span', 'admin-search__icon');
        icon.appendChild(iconEl(item.icon));
        var copy = el('span', 'admin-search__copy');
        var title = el('span', 'admin-search__title');
        title.appendChild(highlight(item.title, tokens));
        var group = el('span', 'admin-search__group');
        group.textContent = item.group;
        copy.appendChild(title);
        copy.appendChild(group);
        button.appendChild(icon);
        button.appendChild(copy);
        button.appendChild(el('kbd', 'admin-search__enter', 'Enter'));
        button.addEventListener('click', function () { choose(item); });
        button.addEventListener('mousemove', function () {
            var index = visible.indexOf(item);
            if (index !== -1 && index !== active) {
                active = index;
                paintActive();
            }
        });
        return button;
    }

    function emptyState(tokens) {
        var box = el('div', 'admin-search__empty');
        box.appendChild(iconEl('fa-regular fa-magnifying-glass'));
        var text = el('p');
        text.textContent = tokens.length ? 'موردی با این عبارت پیدا نشد' : 'بخشی برای نمایش ندارید';
        box.appendChild(text);
        return box;
    }

    function clearHistoryButton() {
        var button = el('button', 'admin-search__section-action', 'پاک کردن');
        button.type = 'button';
        button.addEventListener('click', function (event) {
            event.preventDefault();
            store.history = [];
            saveStore();
            active = 0;
            render();
            input.focus();
        });
        return button;
    }

    function choose(item) {
        if (!item) return;
        store.history = [item.id].concat(store.history.filter(function (id) { return id !== item.id; })).slice(0, 8);
        store.counts[item.id] = (store.counts[item.id] || 0) + 1;
        saveStore();
        window.location.assign(item.url);
    }

    function paintActive() {
        var nodes = list.querySelectorAll('.admin-search__item');
        Array.prototype.forEach.call(nodes, function (node, index) {
            var on = index === active;
            node.classList.toggle('is-active', on);
            if (on) node.scrollIntoView({ block: 'nearest' });
        });
    }

    function scoreItem(item, tokens) {
        var title = normalize(item.title);
        var group = normalize(item.group);
        var keywords = normalize(item.keywords);
        var hay = title + ' ' + group + ' ' + keywords;
        var score = 0;
        for (var i = 0; i < tokens.length; i++) {
            var token = tokens[i];
            if (hay.indexOf(token) === -1) return 0;
            if (title === token) score += 120;
            else if (title.indexOf(token) === 0) score += 80;
            else if (title.indexOf(token) !== -1) score += 55;
            else if (keywords.indexOf(token) !== -1) score += 35;
            else score += 15;
        }
        return score;
    }

    function highlight(text, tokens) {
        var frag = document.createDocumentFragment();
        var chars = Array.from(text);
        if (!tokens.length) {
            frag.appendChild(document.createTextNode(text));
            return frag;
        }
        var folded = '';
        var map = [];
        chars.forEach(function (ch, index) {
            var foldedChar = normalize(ch);
            for (var i = 0; i < foldedChar.length; i++) {
                folded += foldedChar.charAt(i);
                map.push(index);
            }
        });
        var marks = chars.map(function () { return false; });
        tokens.forEach(function (token) {
            var from = 0;
            while (from < folded.length) {
                var at = folded.indexOf(token, from);
                if (at < 0) break;
                for (var k = at; k < at + token.length && k < map.length; k++) marks[map[k]] = true;
                from = at + token.length;
            }
        });
        var buffer = '';
        var marking = false;
        var flush = function () {
            if (!buffer) return;
            if (marking) {
                var mark = document.createElement('mark');
                mark.textContent = buffer;
                frag.appendChild(mark);
            } else {
                frag.appendChild(document.createTextNode(buffer));
            }
            buffer = '';
        };
        chars.forEach(function (ch, index) {
            if (marks[index] !== marking) {
                flush();
                marking = marks[index];
            }
            buffer += ch;
        });
        flush();
        return frag;
    }

    function idsToItems(ids) {
        return ids.map(function (id) {
            for (var i = 0; i < items.length; i++) {
                if (items[i].id === id) return items[i];
            }
            return null;
        }).filter(Boolean);
    }

    function loadStore() {
        try {
            var raw = localStorage.getItem(storageKey);
            var data = raw ? JSON.parse(raw) : null;
            var known = {};
            items.forEach(function (item) { known[item.id] = true; });
            var history = data && Array.isArray(data.history) ? data.history.filter(function (id) { return known[id]; }).slice(0, 8) : [];
            var counts = {};
            if (data && data.counts && typeof data.counts === 'object') {
                Object.keys(data.counts).forEach(function (id) {
                    if (known[id] && Number(data.counts[id]) > 0) counts[id] = Number(data.counts[id]);
                });
            }
            return { history: history, counts: counts };
        } catch (error) {
            return { history: [], counts: {} };
        }
    }

    function saveStore() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(store));
        } catch (error) {
            // Private mode can reject storage; search still works for this page.
        }
    }

    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[\u200c\u200d]/g, '')
            .replace(/[أإآا]/g, 'ا')
            .replace(/[يىی]/g, 'ی')
            .replace(/[كک]/g, 'ک')
            .replace(/ة/g, 'ه')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function iconEl(className) {
        var icon = document.createElement('i');
        icon.className = className;
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text) node.textContent = text;
        return node;
    }
})();
