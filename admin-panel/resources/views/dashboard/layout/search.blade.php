@php($searchItems = \App\Support\AdminSearchCatalog::itemsFor($admin, (bool) $panelLocked))
<div class="admin-search" id="admin-search" hidden role="dialog" aria-modal="true" aria-labelledby="admin-search-title" data-admin-id="{{ $admin->id }}">
    <div class="admin-search__backdrop" data-admin-search-close></div>
    <div class="admin-search__dialog">
        <h2 class="admin-search__title-sr" id="admin-search-title">جستجو در پنل</h2>
        <div class="admin-search__field">
            <i class="fa-regular fa-magnifying-glass" aria-hidden="true"></i>
            <input type="text"
                   data-admin-search-input
                   placeholder="نام بخش، صفحه یا میانبر را بنویسید"
                   autocomplete="off"
                   spellcheck="false"
                   aria-label="جستجو در پنل"
                   aria-controls="admin-search-list">
            <button type="button" class="admin-search__clear" data-admin-search-clear hidden aria-label="پاک کردن عبارت">
                <i class="fa-regular fa-xmark" aria-hidden="true"></i>
            </button>
            <kbd data-admin-search-shortcut>Ctrl K</kbd>
        </div>
        <div class="admin-search__list" id="admin-search-list" data-admin-search-list role="listbox"></div>
        <div class="admin-search__foot">
            <span class="admin-search__hint"><i class="fa-regular fa-chevron-up" aria-hidden="true"></i><i class="fa-regular fa-chevron-down" aria-hidden="true"></i> جابه‌جایی</span>
            <span class="admin-search__hint"><kbd>Enter</kbd> باز کردن</span>
            <span class="admin-search__hint"><kbd>Esc</kbd> بستن</span>
            <span class="admin-search__count" data-admin-search-count></span>
        </div>
    </div>
</div>
<script type="application/json" id="admin-search-catalog">@json($searchItems)</script>
<script src="{{ asset('js/admin-search.js') }}?v={{ filemtime(public_path('js/admin-search.js')) }}"></script>
