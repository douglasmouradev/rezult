(function () {
    const nav = document.getElementById('lp-nav');
    const menuBtn = document.getElementById('lp-menu-btn');
    const navLinks = document.getElementById('lp-nav-links');

    if (menuBtn && nav && navLinks) {
        menuBtn.addEventListener('click', () => {
            nav.classList.toggle('open');
            menuBtn.setAttribute(
                'aria-expanded',
                nav.classList.contains('open') ? 'true' : 'false'
            );
        });

        navLinks.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => nav.classList.remove('open'));
        });
    }

    if (nav) {
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 8);
        }, { passive: true });
    }

    document.querySelectorAll('.lp-faq-item').forEach((item) => {
        item.addEventListener('toggle', () => {
            if (!item.open) return;
            document.querySelectorAll('.lp-faq-item').forEach((other) => {
                if (other !== item) other.open = false;
            });
        });
    });

    const carousel = document.getElementById('lp-carousel');
    const prevBtn = document.getElementById('lp-carousel-prev');
    const nextBtn = document.getElementById('lp-carousel-next');
    const dotsEl = document.getElementById('lp-carousel-dots');

    if (carousel && prevBtn && nextBtn) {
        const cards = carousel.querySelectorAll('.lp-showcase-card');
        let page = 0;

        const cardsPerPage = () => {
            const w = window.innerWidth;
            if (w >= 1200) return 4;
            if (w >= 768) return 2;
            return 1;
        };

        const totalPages = () => Math.max(1, Math.ceil(cards.length / cardsPerPage()));

        const scrollToPage = (p) => {
            page = Math.max(0, Math.min(p, totalPages() - 1));
            const card = cards[page * cardsPerPage()];
            if (card) {
                carousel.scrollTo({ left: card.offsetLeft - carousel.offsetLeft - 8, behavior: 'smooth' });
            }
            updateControls();
        };

        const updateControls = () => {
            prevBtn.disabled = page <= 0;
            nextBtn.disabled = page >= totalPages() - 1;
            if (dotsEl) {
                dotsEl.innerHTML = '';
                for (let i = 0; i < totalPages(); i++) {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'lp-carousel-dot' + (i === page ? ' active' : '');
                    dot.setAttribute('aria-label', 'Página ' + (i + 1));
                    dot.addEventListener('click', () => scrollToPage(i));
                    dotsEl.appendChild(dot);
                }
            }
        };

        prevBtn.addEventListener('click', () => scrollToPage(page - 1));
        nextBtn.addEventListener('click', () => scrollToPage(page + 1));

        let scrollTimer;
        carousel.addEventListener('scroll', () => {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                const scrollLeft = carousel.scrollLeft;
                let nearest = 0;
                let minDist = Infinity;
                cards.forEach((card, i) => {
                    const dist = Math.abs(card.offsetLeft - carousel.offsetLeft - scrollLeft - 8);
                    if (dist < minDist) {
                        minDist = dist;
                        nearest = i;
                    }
                });
                page = Math.floor(nearest / cardsPerPage());
                updateControls();
            }, 80);
        }, { passive: true });

        window.addEventListener('resize', () => {
            page = Math.min(page, totalPages() - 1);
            updateControls();
        });

        updateControls();
    }
})();
