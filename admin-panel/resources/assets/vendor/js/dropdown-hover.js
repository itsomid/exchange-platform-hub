(() => {
  const Dropdown = window.bootstrap?.Dropdown;
  if (!Dropdown) return;

  const SELECTOR = '[data-bs-toggle="dropdown"][data-trigger="hover"]';
  const TIMEOUT = 150;

  const hoverState = new WeakMap();
  const closeTimeouts = new WeakMap();

  function getToggleAndMenu(target) {
    const el = target instanceof Element ? target : null;
    if (!el) return null;

    if (el.matches(SELECTOR)) {
      const menu = el.nextElementSibling?.classList.contains('dropdown-menu') ? el.nextElementSibling : null;
      return { toggle: el, menu };
    }

    if (el.classList.contains('dropdown-menu')) {
      const toggle = el.previousElementSibling?.matches(SELECTOR) ? el.previousElementSibling : null;
      return toggle ? { toggle, menu: el } : null;
    }

    return null;
  }

  function isStaticPosition(menuEl) {
    if (!menuEl) return false;
    return window.getComputedStyle(menuEl).getPropertyValue('position') === 'static';
  }

  function clearCloseTimeout(toggleEl) {
    const t = closeTimeouts.get(toggleEl);
    if (t) {
      clearTimeout(t);
      closeTimeouts.delete(toggleEl);
    }
  }

  function openDropdown(toggleEl) {
    clearCloseTimeout(toggleEl);
    const instance = Dropdown.getOrCreateInstance(toggleEl);
    instance.show();
  }

  function closeDropdown(toggleEl) {
    clearCloseTimeout(toggleEl);
    const t = setTimeout(() => {
      const instance = Dropdown.getOrCreateInstance(toggleEl);
      if (toggleEl.getAttribute('aria-expanded') === 'true') instance.hide();
      closeTimeouts.delete(toggleEl);
    }, TIMEOUT);
    closeTimeouts.set(toggleEl, t);
  }

  document.body.addEventListener(
    'mouseenter',
    (e) => {
      const hit = getToggleAndMenu(e.target);
      if (!hit || (hit.menu && isStaticPosition(hit.menu))) return;

      hoverState.set(hit.toggle, true);
      openDropdown(hit.toggle);
    },
    true
  );

  document.body.addEventListener(
    'mouseleave',
    (e) => {
      const hit = getToggleAndMenu(e.target);
      if (!hit || (hit.menu && isStaticPosition(hit.menu))) return;

      hoverState.set(hit.toggle, false);
      closeDropdown(hit.toggle);
    },
    true
  );

  document.body.addEventListener(
    'hide.bs.dropdown',
    (e) => {
      const toggleEl = e.target instanceof Element ? e.target : null;
      if (!toggleEl || !toggleEl.matches(SELECTOR)) return;
      if (hoverState.get(toggleEl)) e.preventDefault();
    },
    true
  );
})();
