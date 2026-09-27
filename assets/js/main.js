document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = document.body.dataset.baseUrl || '';

    // Mobile navigation
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            const open = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', String(!open));
            navLinks.classList.toggle('is-open');
        });
        navLinks.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // Dropdown hover & click behavior
    document.querySelectorAll('.nav-dropdown').forEach((dropdown) => {
        const toggle = dropdown.querySelector('.nav-dropdown-toggle');
        let hoverTimeout = null;

        const openDropdown = () => {
            clearTimeout(hoverTimeout);
            dropdown.classList.add('is-open');
            toggle?.setAttribute('aria-expanded', 'true');
        };

        const closeDropdown = () => {
            hoverTimeout = setTimeout(() => {
                dropdown.classList.remove('is-open');
                toggle?.setAttribute('aria-expanded', 'false');
            }, 250);
        };

        dropdown.addEventListener('mouseenter', openDropdown);
        dropdown.addEventListener('mouseleave', closeDropdown);

        toggle?.addEventListener('click', (e) => {
            const isOpen = dropdown.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });

        dropdown.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                dropdown.classList.remove('is-open');
                toggle?.setAttribute('aria-expanded', 'false');
            });
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.nav-dropdown.is-open').forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('is-open');
                dropdown.querySelector('.nav-dropdown-toggle')?.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Unified Auth Modal (with Join Us and Log In tabs)
    const authModal = document.querySelector('#auth-modal');
    const openAuthButtons = document.querySelectorAll('[data-open-auth], [data-open-join]');
    const closeAuthButtons = document.querySelectorAll('[data-close-auth]');
    const tabButtons = document.querySelectorAll('[data-auth-tab]');
    const switchButtons = document.querySelectorAll('[data-switch-auth]');
    let lastFocusedElement = null;

    function switchAuthTab(targetTab) {
        const tabName = targetTab === 'login' ? 'login' : 'join';

        tabButtons.forEach((btn) => {
            const isActive = btn.dataset.authTab === tabName;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', String(isActive));
        });

        const panelJoin = document.querySelector('#panel-join');
        const panelLogin = document.querySelector('#panel-login');
        if (panelJoin && panelLogin) {
            if (tabName === 'login') {
                panelJoin.hidden = true;
                panelLogin.hidden = false;
                panelLogin.querySelector('input[type="email"]')?.focus();
            } else {
                panelJoin.hidden = false;
                panelLogin.hidden = true;
                panelJoin.querySelector('input:not([type="hidden"])')?.focus();
            }
        }
    }

    function openAuthModal(targetTab = 'join', triggerEl = null) {
        if (!authModal) return;
        lastFocusedElement = triggerEl || document.activeElement;
        switchAuthTab(targetTab);
        authModal.hidden = false;
        document.body.classList.add('modal-open');
    }

    function closeAuthModal() {
        if (!authModal || authModal.hidden) return;
        authModal.hidden = true;
        document.body.classList.remove('modal-open');
        lastFocusedElement?.focus();
    }

    openAuthButtons.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tab = btn.dataset.openAuth || (btn.hasAttribute('data-open-join') ? 'join' : 'login');
            openAuthModal(tab, btn);
        });
    });

    closeAuthButtons.forEach((btn) => btn.addEventListener('click', closeAuthModal));

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => switchAuthTab(btn.dataset.authTab));
    });

    switchButtons.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            switchAuthTab(btn.dataset.switchAuth);
        });
    });

    authModal?.addEventListener('click', (e) => {
        if (e.target === authModal) closeAuthModal();
    });

    // 5-second delay popup for visitors; defaults to Join Us tab
    let authPromptSeen = false;
    try {
        authPromptSeen = sessionStorage.getItem('foodfusion_auth_prompt_seen') === '1' || sessionStorage.getItem('foodfusion_login_prompt_seen') === '1';
    } catch (_) { /* Browser storage fallback */ }

    if (authModal && !authPromptSeen && window.location.hash !== '#join') {
        setTimeout(() => {
            if (authModal && !authPromptSeen && !document.body.classList.contains('modal-open') && window.location.hash !== '#join') {
                openAuthModal('join');
                try {
                    sessionStorage.setItem('foodfusion_auth_prompt_seen', '1');
                    sessionStorage.setItem('foodfusion_login_prompt_seen', '1');
                } catch (_) {}
            }
        }, 5000);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && authModal && !authModal.hidden) {
            closeAuthModal();
        }
        if (event.key === 'Tab' && authModal && !authModal.hidden) {
            const focusable = Array.from(authModal.querySelectorAll('a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled])'));
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        }
        if (event.key === 'Escape') {
            document.querySelectorAll('.nav-dropdown.is-open').forEach((dropdown) => {
                dropdown.classList.remove('is-open');
                const toggle = dropdown.querySelector('.nav-dropdown-toggle');
                toggle?.setAttribute('aria-expanded', 'false');
                toggle?.focus();
            });
        }
    });

    if (window.location.hash === '#join' && authModal) {
        openAuthModal('join');
    }

    // Cookie acceptance
    const cookieBanner = document.querySelector('#cookie-banner');
    const acceptCookies = document.querySelector('#accept-cookies');
    if (cookieBanner && !document.cookie.includes('foodfusion_cookie_choice=accepted')) {
        cookieBanner.hidden = false;
    }
    acceptCookies?.addEventListener('click', () => {
        document.cookie = 'foodfusion_cookie_choice=accepted; max-age=2592000; path=/; SameSite=Lax';
        cookieBanner.hidden = true;
    });

    // Homepage event carousel
    const slides = Array.from(document.querySelectorAll('[data-slide]'));
    let currentSlide = 0;
    function showSlide(index) {
        if (!slides.length) return;
        currentSlide = (index + slides.length) % slides.length;
        slides.forEach((slide, itemIndex) => {
            const isActive = itemIndex === currentSlide;
            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', String(!isActive));
        });
    }
    document.querySelector('[data-carousel-prev]')?.addEventListener('click', () => showSlide(currentSlide - 1));
    document.querySelector('[data-carousel-next]')?.addEventListener('click', () => showSlide(currentSlide + 1));
    showSlide(0);
    if (slides.length > 1) {
        window.setInterval(() => showSlide(currentSlide + 1), 7000);
    }

    // Like and save recipe interactions through the PHP web service.
    document.querySelectorAll('.interaction-button').forEach((button) => {
        button.addEventListener('click', async () => {
            const message = document.querySelector('.interaction-message');
            const data = new FormData();
            data.append('recipe_id', button.dataset.recipeId);
            data.append('interaction_type', button.dataset.interaction);
            data.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

            button.disabled = true;
            try {
                const response = await fetch(`${baseUrl}/api/interaction.php`, { method: 'POST', body: data });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                button.classList.toggle('is-active', result.active);
                button.querySelector('[data-count]').textContent = result.count;
                if (message) message.textContent = result.message;
            } catch (error) {
                if (message) message.textContent = error.message || 'Please try again.';
            } finally {
                button.disabled = false;
            }
        });
    });

    // Recipe submission through the PHP JSON web service.
    document.querySelectorAll('[data-api-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const message = form.querySelector('.api-message');
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            message.textContent = 'Saving your recipe...';

            try {
                const response = await fetch(form.action, { method: 'POST', body: new FormData(form) });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                message.textContent = result.message;
                window.location.href = result.redirect;
            } catch (error) {
                message.textContent = error.message || 'The recipe could not be saved.';
                submitButton.disabled = false;
            }
        });
    });

    // Ask before permanently deleting a post from My Wall.
    document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Delete this post? This cannot be undone.')) {
                event.preventDefault();
            }
        });
    });

    // Ask before permanently deleting a post from My Wall.
    document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Delete this community post permanently?')) {
                event.preventDefault();
            }
        });
    });
});
