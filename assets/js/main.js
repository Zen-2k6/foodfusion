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

    // Community Cookbook dropdown
    document.querySelectorAll('.nav-dropdown').forEach((dropdown) => {
        const toggle = dropdown.querySelector('.nav-dropdown-toggle');
        toggle?.addEventListener('click', () => {
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

    // Join Us modal
    const modal = document.querySelector('#join-modal');
    const openButtons = document.querySelectorAll('[data-open-join]');
    const closeButton = document.querySelector('[data-close-join]');
    let lastFocusedElement = null;

    function openModal(event) {
        if (!modal) return;
        event.preventDefault();
        lastFocusedElement = event.currentTarget;
        modal.hidden = false;
        document.body.classList.add('modal-open');
        modal.querySelector('input:not([type="hidden"])')?.focus();
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        lastFocusedElement?.focus();
    }

    openButtons.forEach((button) => button.addEventListener('click', openModal));
    closeButton?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
        if (event.key === 'Escape') {
            document.querySelectorAll('.nav-dropdown.is-open').forEach((dropdown) => {
                dropdown.classList.remove('is-open');
                const toggle = dropdown.querySelector('.nav-dropdown-toggle');
                toggle?.setAttribute('aria-expanded', 'false');
                toggle?.focus();
            });
        }
    });
    if (window.location.hash === '#join' && modal) {
        modal.hidden = false;
        document.body.classList.add('modal-open');
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
