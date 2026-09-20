/* =========================================================
   Student Management System — shared front-end behaviour
   ========================================================= */

/** Show a toast notification. type: 'success' | 'danger' | 'info' */
function showToast(message, type = 'info') {
    const stack = document.getElementById('toast-stack');
    if (!stack) return;

    const icons = { success: 'fa-circle-check', danger: 'fa-circle-exclamation', info: 'fa-circle-info' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i><span>${message}</span>`;
    stack.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 220ms ease';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 220);
    }, 4200);
}

document.addEventListener('DOMContentLoaded', function () {
    /* ---- Dark / light mode toggle. The saved preference is already applied
       before paint (see the inline script in includes/header.php) — this
       just wires up the button and keeps its icon/label in sync, on every
       page that includes the toggle (admin/teacher/student topbar + public
       site nav). ---- */
    (function () {
        const toggle = document.getElementById('themeToggle');
        if (!toggle) return;
        const icon = document.getElementById('themeToggleIcon');
        const label = document.getElementById('themeToggleLabel');

        function sync() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (icon) icon.className = isDark ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
            if (label) label.textContent = isDark ? 'Dark' : 'Light';
        }
        sync();

        toggle.addEventListener('click', () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                document.documentElement.removeAttribute('data-theme');
                try { localStorage.setItem('theme', 'light'); } catch (e) { /* private mode — theme just won't persist */ }
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                try { localStorage.setItem('theme', 'dark'); } catch (e) { /* private mode — theme just won't persist */ }
            }
            sync();
        });
    })();

    /* ---- Graceful image fallback: broken external images hide instead of showing
       a broken-image icon, and get a soft placeholder background so layout holds. ---- */
    document.addEventListener('error', function (e) {
        const img = e.target;
        if (!img || img.tagName !== 'IMG' || img.dataset.fallbackApplied) return;
        img.dataset.fallbackApplied = '1';
        img.style.background = '#e4e1d8';
        img.removeAttribute('src');
        img.alt = img.alt || 'Image unavailable';
    }, true);

    /* ---- Live grade preview: mirrors scoreToGrade() in functions.php.
       This is a UX convenience only — the server always recalculates and
       is the authoritative source, per "never rely only on JS validation." ---- */
    function scoreToGradeJs(score) {
        const bands = [
            [90, 'A', 4.00], [85, 'A-', 3.75], [80, 'B+', 3.50], [75, 'B', 3.00],
            [70, 'B-', 2.75], [65, 'C+', 2.50], [60, 'C', 2.00], [55, 'C-', 1.75],
            [50, 'D+', 1.50], [45, 'D', 1.00], [0, 'F', 0.00]
        ];
        for (const [min, letter, point] of bands) {
            if (score >= min) return { letter, point };
        }
        return { letter: 'F', point: 0 };
    }
    document.querySelectorAll('[data-grade-preview]').forEach((input) => {
        const preview = document.getElementById(input.getAttribute('data-grade-preview'));
        if (!preview) return;
        const update = () => {
            const score = parseFloat(input.value);
            if (isNaN(score) || score < 0 || score > 100) {
                preview.textContent = '— enter a score —';
                preview.className = 'grade-preview';
                return;
            }
            const { letter, point } = scoreToGradeJs(score);
            preview.textContent = letter + '  ·  ' + point.toFixed(2) + ' points';
            preview.className = 'grade-preview is-filled';
        };
        input.addEventListener('input', update);
        update();
    });

    /* ---- AJAX live tables: any [data-live-table] wrapper gets its search,
       filters, sort headers, and pagination upgraded to fetch-and-swap
       instead of a full page reload. The URL is kept in sync via
       pushState so refresh/back/forward/bookmarking still work.
       This progressively enhances a page that already works without JS —
       every link/form inside still has a real, valid href/action. ---- */
    document.querySelectorAll('[data-live-table]').forEach((container) => {
        const path = window.location.pathname;

        async function loadUrl(url, pushHistory) {
            container.classList.add('is-loading');
            try {
                const fetchUrl = url + (url.includes('?') ? '&' : '?') + 'ajax=1';
                const res = await fetch(fetchUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Request failed');
                const html = await res.text();
                container.innerHTML = html;
                if (pushHistory) window.history.pushState({ liveTable: true }, '', url);
            } catch (err) {
                // Fall back to a real navigation — the page works fine without JS.
                window.location.href = url;
            } finally {
                container.classList.remove('is-loading');
            }
        }

        function buildUrl(overrides) {
            const params = new URLSearchParams(window.location.search);
            params.delete('ajax');
            Object.entries(overrides).forEach(([k, v]) => {
                if (v === null) params.delete(k);
                else params.set(k, v);
            });
            return path + '?' + params.toString();
        }

        let debounceTimer = null;
        container.addEventListener('input', (e) => {
            if (e.target.matches('input[type="text"][name="q"]')) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    loadUrl(buildUrl({ q: e.target.value, page: null }), true);
                }, 350);
            }
        });

        container.addEventListener('change', (e) => {
            if (e.target.matches('select')) {
                loadUrl(buildUrl({ [e.target.name]: e.target.value, page: null }), true);
            }
        });

        container.addEventListener('click', (e) => {
            // Sort headers and pagination links both live inside this container
            // and point at the same page — intercept those, let everything
            // else (view/edit/delete row actions) behave normally.
            const link = e.target.closest('a.sortable-th, .pagination a');
            if (!link || link.classList.contains('disabled')) return;
            e.preventDefault();
            loadUrl(link.getAttribute('href'), true);
        });

        container.addEventListener('submit', (e) => {
            if (e.target.matches('form[method="get"]')) {
                e.preventDefault();
                loadUrl(buildUrl({ q: e.target.querySelector('[name="q"]')?.value ?? '', page: null }), true);
            }
        });

        window.addEventListener('popstate', () => {
            loadUrl(window.location.pathname + window.location.search, false);
        });
    });

    /* ---- Admin: sidebar drawer on mobile ---- */
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
            overlay && overlay.classList.toggle('is-open');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-open');
        });
    }

    /* ---- Public site: mobile nav toggle ---- */
    const navToggle = document.getElementById('navToggle');
    const siteNav = document.getElementById('siteNav');
    if (navToggle && siteNav) {
        navToggle.addEventListener('click', () => siteNav.classList.toggle('is-open'));
    }

    /* ---- Image carousel ---- */
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('.carousel-track');
        const slides = Array.from(carousel.querySelectorAll('.carousel-slide'));
        const dotsWrap = carousel.querySelector('.carousel-dots');
        const prevBtn = carousel.querySelector('.carousel-btn.prev');
        const nextBtn = carousel.querySelector('.carousel-btn.next');
        if (!track || slides.length === 0) return;

        let index = 0;
        let timer = null;
        const delay = parseInt(carousel.getAttribute('data-autoplay') || '0', 10);
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
            if (i === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goTo(i));
            dotsWrap && dotsWrap.appendChild(dot);
        });

        function render() {
            track.style.transform = `translateX(-${index * 100}%)`;
            dotsWrap && Array.from(dotsWrap.children).forEach((d, i) => d.classList.toggle('active', i === index));
        }
        function goTo(i) {
            index = (i + slides.length) % slides.length;
            render();
        }
        function next() { goTo(index + 1); }
        function prev() { goTo(index - 1); }

        nextBtn && nextBtn.addEventListener('click', () => { next(); restart(); });
        prevBtn && prevBtn.addEventListener('click', () => { prev(); restart(); });

        function start() {
            if (!delay || reduceMotion) return;
            timer = setInterval(next, delay);
        }
        function stop() { if (timer) clearInterval(timer); }
        function restart() { stop(); start(); }

        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);

        // Basic touch swipe support
        let touchStartX = null;
        track.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; stop(); }, { passive: true });
        track.addEventListener('touchend', (e) => {
            if (touchStartX === null) return;
            const delta = e.changedTouches[0].clientX - touchStartX;
            if (delta > 40) prev();
            else if (delta < -40) next();
            touchStartX = null;
            start();
        }, { passive: true });

        render();
        start();
    });

    /* ---- Generic modal open/close + delete-confirmation, all via event
       delegation on document — this means it keeps working for elements
       added later by an AJAX table refresh, not just ones present at
       page load. ---- */
    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteForm = document.getElementById('deleteConfirmForm');
    const deleteNameEl = document.getElementById('deleteConfirmName');
    let lastModalTrigger = null;

    document.addEventListener('click', (e) => {
        const openTrigger = e.target.closest('[data-modal-target]');
        if (openTrigger) {
            const modal = document.querySelector(openTrigger.getAttribute('data-modal-target'));
            if (modal) modal.classList.add('is-open');
            lastModalTrigger = openTrigger;
            return;
        }

        const deleteTrigger = e.target.closest('[data-delete-url]');
        if (deleteTrigger) {
            if (deleteModal && deleteForm) {
                deleteForm.action = deleteTrigger.getAttribute('data-delete-url');
                if (deleteNameEl) deleteNameEl.textContent = deleteTrigger.getAttribute('data-delete-name') || 'this record';
                deleteModal.classList.add('is-open');
            }
            lastModalTrigger = deleteTrigger;
            return;
        }

        const closeTrigger = e.target.closest('[data-modal-close]');
        if (closeTrigger) {
            closeTrigger.closest('.modal-backdrop')?.classList.remove('is-open');
            return;
        }

        // Clicking the dimmed backdrop itself (not its content) closes it.
        if (e.target.classList.contains('modal-backdrop')) {
            e.target.classList.remove('is-open');
        }
    });

    /* ---- Keyboard support: Escape closes the topmost open modal, and focus
       moves into the modal on open / back to the trigger button on close. ---- */
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        const openModal = document.querySelector('.modal-backdrop.is-open');
        if (!openModal) return;
        openModal.classList.remove('is-open');
        lastModalTrigger && lastModalTrigger.focus();
    });
    // Move focus to the first focusable element whenever a modal opens.
    new MutationObserver((mutations) => {
        mutations.forEach((m) => {
            const el = m.target;
            if (el.classList.contains('is-open')) {
                const focusable = el.querySelector('input, select, textarea, button');
                focusable && focusable.focus();
            }
        });
    }).observe(document.body, { attributes: true, attributeFilter: ['class'], subtree: true });

    /** Disable a form's submit button and show a spinner, so slow requests give feedback and can't double-submit. */
    function setSubmitLoading(form) {
        const btn = form.querySelector('button[type="submit"]');
        if (!btn || btn.dataset.loading) return;
        btn.dataset.loading = '1';
        btn.dataset.originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + (btn.dataset.loadingText || 'Please wait…');
    }

    /* ---- Simple required-field client-side validation ---- */
    document.querySelectorAll('form[data-validate]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            let valid = true;
            form.querySelectorAll('[required]').forEach((field) => {
                const value = field.value.trim();
                field.classList.remove('is-invalid');
                if (!value) {
                    valid = false;
                    field.classList.add('is-invalid');
                }
                if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    valid = false;
                    field.classList.add('is-invalid');
                }
            });
            if (!valid) {
                e.preventDefault();
                showToast('Please fill in all required fields correctly.', 'danger');
            } else {
                setSubmitLoading(form);
            }
        });
    });

    /* ---- Forms with no client-side validation (delete confirms, quick actions)
       still get a loading state so a slow request can't be double-submitted. ---- */
    document.querySelectorAll('form:not([data-validate])').forEach((form) => {
        form.addEventListener('submit', () => setSubmitLoading(form));
    });
});
