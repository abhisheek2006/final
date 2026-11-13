/* Swasti Homoeo Clinic — navigation.
   Header behaviour, mobile drawer and section scroll-spy.
   Plain ES2017, no dependencies, no inline handlers. */

(function () {
    'use strict';

    var header = document.querySelector('[data-site-header]');

    if (header) {
        var ticking = false;
        var onScroll = function () {
            var currentY = Math.max(0, window.pageYOffset);
            header.classList.toggle('is-stuck', currentY > 8);
            ticking = false;
        };

        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(onScroll);
                ticking = true;
            }
        }, { passive: true });

        window.addEventListener('resize', onScroll, { passive: true });
        onScroll();
    }

    /* ------------------------------------------------------------ mobile nav */

    var toggle = document.querySelector('[data-nav-toggle]');
    var drawer = document.querySelector('[data-mobile-nav]');

    if (toggle && drawer) {
        var setDrawer = function (open) {
            toggle.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');

            if (open) {
                drawer.removeAttribute('hidden');
            } else {
                drawer.setAttribute('hidden', '');
            }
        };

        toggle.addEventListener('click', function () {
            setDrawer(toggle.getAttribute('aria-expanded') !== 'true');
        });

        drawer.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                setDrawer(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setDrawer(false);
                toggle.focus();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 1024) {
                setDrawer(false);
            }
        });
    }

    /* ----------------------------------------------------------- scroll spy */

    var anchorNav = document.querySelector('.anchor-nav');

    if (anchorNav) {
        var links = Array.prototype.slice.call(anchorNav.querySelectorAll('.anchor-nav__link'));
        var targets = links
            .map(function (link) {
                var href = link.getAttribute('href') || '';
                return href.charAt(0) === '#' ? document.getElementById(href.slice(1)) : null;
            })
            .filter(Boolean);

        if (targets.length) {
            var spy = function () {
                var line = window.pageYOffset + (window.innerHeight * 0.32);
                var activeIndex = 0;

                targets.forEach(function (target, index) {
                    if (target.offsetTop <= line) {
                        activeIndex = index;
                    }
                });

                links.forEach(function (link, index) {
                    link.classList.toggle('is-active', index === activeIndex);
                });
            };

            window.addEventListener('scroll', function () {
                window.requestAnimationFrame(spy);
            }, { passive: true });

            spy();
        }
    }
}());
