/* Swasti Homoeo Clinic — public behaviour.
   Scroll reveals, FAQ filtering and form enhancements. Everything degrades
   gracefully without JavaScript. */

(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* The public site is static on Vercel, so maintenance status is checked
       through the PHP API before the page is revealed. API failures fail open. */
    if (document.documentElement.classList.contains('maintenance-checking')
        && !document.querySelector('[data-admin]')
        && typeof window.fetch === 'function'
    ) {
        var root = document.documentElement;
        var pendingTimeout = window.setTimeout(function () {
            root.classList.remove('maintenance-checking');
        }, 4000);

        window.fetch('/api/maintenance-status.php', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Maintenance status is unavailable.');
                }
                return response.json();
            })
            .then(function (status) {
                window.clearTimeout(pendingTimeout);
                root.classList.remove('maintenance-checking');

                if (!status || status.enabled !== true || !document.body) {
                    return;
                }

                root.classList.add('maintenance-active');
                var screen = document.createElement('main');
                screen.className = 'maintenance-screen';
                screen.setAttribute('role', 'main');
                screen.setAttribute('aria-labelledby', 'maintenance-title');
                screen.innerHTML = '<section class="maintenance-screen__card">'
                    + '<span class="maintenance-screen__mark" aria-hidden="true">S</span>'
                    + '<p class="maintenance-screen__eyebrow">Swasti Homoeo Clinic</p>'
                    + '<h1 id="maintenance-title">We’ll be back soon</h1>'
                    + '<p class="maintenance-screen__message" data-maintenance-message></p>'
                    + '<a class="maintenance-screen__call" href="tel:+917980644867">Call the clinic</a>'
                    + '</section>';

                var message = screen.querySelector('[data-maintenance-message]');
                message.textContent = typeof status.message === 'string' && status.message.trim()
                    ? status.message
                    : 'We are making a few updates to our website. Please check back soon.';
                document.body.appendChild(screen);
            })
            .catch(function () {
                window.clearTimeout(pendingTimeout);
                root.classList.remove('maintenance-checking');
            });
    } else {
        document.documentElement.classList.remove('maintenance-checking');
    }

    /* --------------------------------------------------------- scroll reveal */

    var revealables = Array.prototype.slice.call(document.querySelectorAll('[data-reveal]'));

    if (revealables.length) {
        if (!('IntersectionObserver' in window) || reduceMotion) {
            revealables.forEach(function (el) { el.classList.add('is-visible'); });
        } else {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

            revealables.forEach(function (el, index) {
                el.style.setProperty('transition-delay', Math.min(index % 4, 3) * 70 + 'ms');
                observer.observe(el);
            });
        }
    }

    /* -------------------------------------------- today's clinic hours */

    var hoursTable = document.querySelector('[data-hours-table]');

    if (hoursTable && window.Intl && Intl.DateTimeFormat) {
        var clinicTimeFormatter = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Asia/Kolkata',
            weekday: 'long',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23'
        });

        var formatClinicTime = function (minutes) {
            var hour = Math.floor(minutes / 60);
            var minute = minutes % 60;
            var suffix = hour >= 12 ? 'PM' : 'AM';
            var displayHour = hour % 12 || 12;
            return displayHour + ':' + (minute < 10 ? '0' : '') + minute + ' ' + suffix;
        };

        var updateClinicHours = function () {
            var parts = {};
            clinicTimeFormatter.formatToParts(new Date()).forEach(function (part) {
                if (part.type !== 'literal') {
                    parts[part.type] = part.value;
                }
            });

            var clinicToday = (parts.weekday || '').toLowerCase();
            var nowMinutes = (parseInt(parts.hour || '0', 10) * 60) + parseInt(parts.minute || '0', 10);

            Array.prototype.forEach.call(hoursTable.querySelectorAll('[data-hours-day]'), function (row) {
                var isToday = row.getAttribute('data-hours-day') === clinicToday;
                row.classList.toggle('hours__row--today', isToday);

                var label = row.querySelector('.hours__today-label');
                if (isToday && !label) {
                    label = document.createElement('span');
                    label.className = 'hours__today-label';
                    label.textContent = 'Today';
                    row.querySelector('th').appendChild(label);
                } else if (!isToday && label) {
                    label.remove();
                }

                var state = row.querySelector('[data-hours-state]');
                if (!state) {
                    return;
                }

                state.replaceChildren();
                if (!isToday) {
                    return;
                }

                var periods = [];
                try {
                    periods = JSON.parse(row.getAttribute('data-hours-periods') || '[]');
                } catch (error) {
                    periods = [];
                }

                var isClosed = row.getAttribute('data-hours-closed') === 'true';
                var isOpen = false;
                var nextOpening = null;

                periods.forEach(function (period) {
                    var open = (parseInt(period.open.slice(0, 2), 10) * 60)
                        + parseInt(period.open.slice(3, 5), 10);
                    var close = (parseInt(period.close.slice(0, 2), 10) * 60)
                        + parseInt(period.close.slice(3, 5), 10);

                    if (nowMinutes >= open && nowMinutes < close) {
                        isOpen = true;
                    } else if (open > nowMinutes && (nextOpening === null || open < nextOpening)) {
                        nextOpening = open;
                    }
                });

                var badge = document.createElement('span');
                badge.className = isOpen ? 'badge badge--success' : 'badge badge--muted';
                if (isOpen) {
                    badge.textContent = 'Open now';
                } else if (isClosed) {
                    badge.textContent = 'Closed';
                } else if (nextOpening !== null) {
                    badge.textContent = 'Opens at ' + formatClinicTime(nextOpening);
                } else {
                    badge.textContent = 'Closed for today';
                }
                state.appendChild(badge);
            });
        };

        updateClinicHours();
        window.setInterval(updateClinicHours, 60000);
        window.addEventListener('focus', updateClinicHours);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                updateClinicHours();
            }
        });
    }


    /* ------------------------------------------------------------ FAQ search */

    Array.prototype.forEach.call(document.querySelectorAll('[data-faq]'), function (faq) {
        var input = faq.querySelector('[data-faq-search]');
        var groups = Array.prototype.slice.call(faq.querySelectorAll('[data-faq-group]'));
        var empty = faq.querySelector('[data-faq-empty]');
        var countEl = faq.querySelector('[data-faq-count]');

        if (!input) {
            return;
        }

        input.addEventListener('input', function () {
            var term = input.value.trim().toLowerCase();
            var visible = 0;

            groups.forEach(function (group) {
                var items = Array.prototype.slice.call(group.querySelectorAll('[data-faq-item]'));
                var shown = 0;

                items.forEach(function (item) {
                    var haystack = item.getAttribute('data-search') || item.textContent.toLowerCase();
                    var match = term === '' || haystack.indexOf(term) !== -1;
                    item.hidden = !match;
                    if (match) {
                        shown++;
                    }
                });

                group.hidden = shown === 0;
                visible += shown;
            });

            if (empty) {
                empty.hidden = visible > 0;
            }

            if (countEl) {
                countEl.textContent = term === ''
                    ? ''
                    : visible + (visible === 1 ? ' question matches' : ' questions match') + ' “' + input.value.trim() + '”';
            }
        });
    });

    /* ---------------------------------------------------- testimonial rotator */

    /* Patient reviews advance one card at a time on their own. The track is a
       native scroll-snap container, so it stays swipeable when the timer is
       suppressed, and the markup is fully readable with JavaScript off. */

    var ROTATE_DELAY = 6000;

    Array.prototype.forEach.call(document.querySelectorAll('[data-testimonials]'), function (track) {
        var cards = Array.prototype.slice.call(track.children);

        if (cards.length < 2) {
            return;
        }

        var index = 0;
        var timer = null;
        var onScreen = false;
        var scrolling = false;
        var expandedQuote = null;

        /* Distance from the track's left edge to a card, independent of which
           element happens to be the offsetParent. */
        var offsetOf = function (card) {
            return card.getBoundingClientRect().left - track.getBoundingClientRect().left + track.scrollLeft;
        };

        var scrollToIndex = function (target, smooth) {
            index = ((target % cards.length) + cards.length) % cards.length;

            var left = offsetOf(cards[index]);

            if (typeof track.scrollTo === 'function') {
                track.scrollTo({ left: left, behavior: smooth && !reduceMotion ? 'smooth' : 'auto' });
            } else {
                track.scrollLeft = left;
            }
        };

        /* Swiping or dragging the track moves it under the visitor's control,
           so adopt that position as the new index instead of fighting it. */
        var syncFromScroll = function () {
            if (scrolling) {
                return;
            }

            scrolling = true;
            window.requestAnimationFrame(function () {
                scrolling = false;

                var closest = 0;
                var smallest = Infinity;

                cards.forEach(function (card, i) {
                    var distance = Math.abs(offsetOf(card) - track.scrollLeft);

                    if (distance < smallest) {
                        smallest = distance;
                        closest = i;
                    }
                });

                index = closest;
            });
        };

        var tick = function () {
            scrollToIndex(index + 1, true);
        };

        var stop = function () {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
        };

        var start = function () {
            stop();

            /* The track is not scrollable and has no controls, so there is
               nothing for a visitor to interrupt: keep advancing while the
               section is on screen. Only a background tab or a reduced-motion
               preference stops it. */
            if (reduceMotion || !onScreen || document.hidden || expandedQuote) {
                return;
            }

            timer = window.setTimeout(function () {
                tick();
                start();
            }, ROTATE_DELAY);
        };

        /* Long reviews begin at the same height as the other cards. Let a
           reader expand one, and pause rotation until they collapse it again. */
        var addReadMoreControls = function () {
            cards.forEach(function (card, cardIndex) {
                var quote = card.querySelector('.testimonial__quote');

                if (!quote || quote.getAttribute('data-read-more-ready') === 'true') {
                    return;
                }

                if (quote.scrollHeight <= quote.clientHeight + 2) {
                    return;
                }

                quote.setAttribute('data-read-more-ready', 'true');
                quote.id = quote.id || 'testimonial-quote-' + trackIndex + '-' + cardIndex;

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'testimonial__read-more';
                button.textContent = 'Read more';
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-controls', quote.id);
                quote.parentNode.insertBefore(button, quote.nextSibling);

                button.addEventListener('click', function () {
                    var isExpanded = button.getAttribute('aria-expanded') === 'true';

                    if (isExpanded) {
                        quote.classList.remove('testimonial__quote--expanded');
                        button.setAttribute('aria-expanded', 'false');
                        button.textContent = 'Read more';
                        expandedQuote = null;
                        start();
                        return;
                    }

                    if (expandedQuote && expandedQuote !== quote) {
                        expandedQuote.classList.remove('testimonial__quote--expanded');
                        var previousButton = expandedQuote.parentNode.querySelector('.testimonial__read-more');
                        if (previousButton) {
                            previousButton.setAttribute('aria-expanded', 'false');
                            previousButton.textContent = 'Read more';
                        }
                    }

                    expandedQuote = quote;
                    quote.classList.add('testimonial__quote--expanded');
                    button.setAttribute('aria-expanded', 'true');
                    button.textContent = 'Read less';
                    stop();
                });
            });
        };

        var trackIndex = Array.prototype.indexOf.call(document.querySelectorAll('[data-testimonials]'), track);
        window.requestAnimationFrame(addReadMoreControls);
        window.addEventListener('resize', addReadMoreControls);

        track.addEventListener('scroll', syncFromScroll, { passive: true });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stop();
            } else {
                start();
            }
        });

        /* Never rotate a section nobody is looking at. */
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                onScreen = entries[entries.length - 1].isIntersecting;
                start();
            }, { threshold: 0.2 }).observe(track);
        } else {
            onScreen = true;
        }

        index = 0;
        start();
    });

    /* ------------------------------------------------------- hero rotator */

    Array.prototype.forEach.call(document.querySelectorAll('[data-hero-slides]'), function (rotator) {
        var slides = Array.prototype.slice.call(rotator.children);

        if (slides.length < 2) {
            return;
        }

        var index = 0;
        var timer = null;
        var onScreen = false;

        var stop = function () {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
        };

        var advance = function () {
            slides[index].classList.remove('is-active');
            slides[index].setAttribute('aria-hidden', 'true');
            index = (index + 1) % slides.length;
            slides[index].classList.add('is-active');
            slides[index].setAttribute('aria-hidden', 'false');
        };

        var start = function () {
            stop();

            if (reduceMotion || !onScreen || document.hidden) {
                return;
            }

            timer = window.setTimeout(function () {
                advance();
                start();
            }, ROTATE_DELAY);
        };

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stop();
            } else {
                start();
            }
        });

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                onScreen = entries[entries.length - 1].isIntersecting;
                start();
            }, { threshold: 0.2 }).observe(rotator);
        } else {
            onScreen = true;
        }

        start();
    });

    /* ---------------------------------------------------------------- forms */

    Array.prototype.forEach.call(document.querySelectorAll('[data-no-sundays]'), function (input) {
        var validateDay = function () {
            if (!input.value) {
                input.setCustomValidity('');
                return;
            }

            var selectedDate = new Date(input.value + 'T12:00:00');
            var isSunday = !Number.isNaN(selectedDate.getTime()) && selectedDate.getDay() === 0;
            input.setCustomValidity(isSunday ? input.getAttribute('data-sunday-message') : '');
        };

        input.addEventListener('input', validateDay);
        input.addEventListener('change', validateDay);
        validateDay();
    });

    var normaliseDigits = function (value) {
        var digits = value.replace(/\D+/g, '');
        if (digits.length === 10) {
            return digits;
        }
        if (digits.length === 12 && digits.indexOf('91') === 0) {
            return digits.slice(2);
        }
        return value;
    };

    Array.prototype.forEach.call(document.querySelectorAll('input[type="tel"], input[inputmode="tel"]'), function (input) {
        input.addEventListener('blur', function () {
            var cleaned = normaliseDigits(input.value.trim());
            if (cleaned !== input.value) {
                input.value = cleaned;
            }
        });
    });

    /* Live character counters. */
    Array.prototype.forEach.call(document.querySelectorAll('[data-counter]'), function (field) {
        var target = document.getElementById(field.getAttribute('data-counter'));
        if (!target) {
            return;
        }

        var max = parseInt(field.getAttribute('maxlength') || '0', 10);

        var update = function () {
            var length = field.value.length;
            target.textContent = length + (max ? ' / ' + max : '');
            target.classList.toggle('is-over', max > 0 && length > max);
        };

        field.addEventListener('input', update);
        update();
    });

    /* Suggestion chips that fill a textarea. */
    Array.prototype.forEach.call(document.querySelectorAll('[data-chip-fill]'), function (chip) {
        chip.addEventListener('click', function () {
            var target = document.querySelector('[name="' + chip.getAttribute('data-chip-fill') + '"]');
            if (!target) {
                return;
            }

            var value = chip.getAttribute('data-chip-value') || chip.textContent.trim();
            var lead = target.value && !/\s$/.test(target.value) ? target.value + ' ' : target.value;

            target.value = lead + value;
            target.focus();

            if (target.hasAttribute('data-counter')) {
                target.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });

    /* A free-text reason appears only when the patient chooses that option. */
    Array.prototype.forEach.call(document.querySelectorAll('[data-own-words-select]'), function (select) {
        var hiddenReason = document.getElementById(select.getAttribute('data-reason-hidden'));
        var details = document.getElementById(select.getAttribute('data-reason-details'));
        var wrapper = details ? details.parentElement : null;
        if (!hiddenReason || !details || !wrapper) {
            return;
        }

        var sync = function (focusDetails) {
            var custom = select.value === '__custom__';
            wrapper.hidden = !custom;
            hiddenReason.disabled = custom;
            details.disabled = !custom;
            details.required = custom;
            if (custom) {
                hiddenReason.value = '';
                details.dispatchEvent(new Event('input', { bubbles: true }));
                if (focusDetails) {
                    details.focus();
                }
            } else {
                hiddenReason.value = select.value;
            }
        };

        sync(false);
        select.addEventListener('change', function () { sync(true); });
    });

    /* Single-select radio chip groups. */
    Array.prototype.forEach.call(document.querySelectorAll('[data-chip-group]'), function (group) {
        var inputs = Array.prototype.slice.call(group.querySelectorAll('[data-chip-input]'));

        inputs.forEach(function (input) {
            input.addEventListener('change', function () {
                inputs.forEach(function (other) {
                    var label = other.closest('.chip');
                    if (label) {
                        label.classList.toggle('is-active', other.checked);
                    }
                });
            });
        });
    });

    /* Guard the booking date fields on the client as well as the server. */
    Array.prototype.forEach.call(document.querySelectorAll('input[type="date"]'), function (input) {
        var sync = function () {
            var min = input.min ? new Date(input.min + 'T00:00:00') : null;
            var max = input.max ? new Date(input.max + 'T00:00:00') : null;
            var value = input.value ? new Date(input.value + 'T00:00:00') : null;

            if (value && min && value < min) {
                input.value = '';
                input.setCustomValidity('Choose a date from today onwards.');
            } else if (value && max && value > max) {
                input.value = '';
                input.setCustomValidity('That date is too far ahead.');
            } else {
                input.setCustomValidity('');
            }
        };

        input.addEventListener('change', sync);
    });

    /* ------------------------------------------------- AJAX form submission */

    function setCsrfToken(form, token) {
        var field = form.querySelector('input[name="_token"]');
        if (field) {
            field.value = token;
        }
    }

    function fetchCsrf(form) {
        var url = form.getAttribute('data-csrf-url') || '/api/csrf.php';

        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('The booking service could not start (HTTP ' + response.status + '). Please try again later or call the clinic.');
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || data.ok !== true || !data.token) {
                    throw new Error('The booking security check could not be completed. Please reload the page and try again.');
                }
                setCsrfToken(form, data.token);
                return data.token;
            })
    }

    function fieldWrap(input) {
        return input.closest('.field') || input.parentElement;
    }

    function clearFieldErrors(form) {
        Array.prototype.forEach.call(form.querySelectorAll('.field__error[data-js]'), function (node) {
            node.remove();
        });

        Array.prototype.forEach.call(form.querySelectorAll('[aria-invalid="true"]'), function (input) {
            input.removeAttribute('aria-invalid');
            var wrap = fieldWrap(input);
            if (wrap) {
                wrap.classList.remove('field--error');
            }
        });
    }

    function showFieldErrors(form, errors) {
        var first = null;

        Object.keys(errors || {}).forEach(function (name) {
            var input = form.querySelector('[name="' + name + '"]');
            if (!input) {
                return;
            }

            input.setAttribute('aria-invalid', 'true');

            var wrap = fieldWrap(input);
            if (wrap) {
                wrap.classList.add('field--error');
            }

            var note = document.createElement('p');
            note.className = 'field__error';
            note.setAttribute('data-js', '');
            note.textContent = errors[name];
            (wrap || input.parentElement).appendChild(note);

            if (!first) {
                first = input;
            }
        });

        if (first) {
            first.focus({ preventScroll: true });
            first.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        }
    }

    function setStatus(form, message, kind) {
        var status = form.querySelector('[data-form-status]');
        if (!status) {
            return;
        }

        status.textContent = message;
        status.className = 'form__status' + (kind ? ' form__status--' + kind : '');
    }

    function setBusy(form, busy) {
        var button = form.querySelector('[data-submit-button]') || form.querySelector('button[type="submit"]');
        var spinner = form.querySelector('[data-submit-spinner]');

        if (button) {
            button.classList.toggle('is-busy', busy);
            button.toggleAttribute('disabled', busy);
            button.setAttribute('aria-busy', busy ? 'true' : 'false');
        }

        if (spinner) {
            spinner.toggleAttribute('hidden', !busy);
        }
    }

    Array.prototype.forEach.call(document.querySelectorAll('form[data-ajax-form]'), function (form) {
        var status = form.querySelector('[data-form-status]');

        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            event.preventDefault();
            clearFieldErrors(form);

            var payload = new FormData(form);
            setBusy(form, true);
            setStatus(form, 'Sending your request — please wait a moment. Do not close this tab.', 'pending');

            track('form_submit', form.getAttribute('data-form-name') || 'form');

            fetchCsrf(form)
                .then(function () {
                    payload = new FormData(form);
                    var phone = form.querySelector('input[name="phone"]');
                    var countryCode = form.querySelector('select[name="phone_country_code"]');
                    if (phone && countryCode) {
                        payload.set('phone', countryCode.value + ' ' + phone.value.trim());
                    }

                    return fetch(form.getAttribute('action') || window.location.pathname, {
                        method: 'POST',
                        body: payload,
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                })
                .then(function (response) {
                    return response.text().then(function (body) {
                        var data;
                        try {
                            data = JSON.parse(body);
                        } catch (error) {
                            throw new Error('The booking service returned an invalid response (HTTP ' + response.status + '). Please call the clinic to arrange your appointment.');
                        }
                        return { status: response.status, data: data };
                    });
                })
                .then(function (result) {
                    setBusy(form, false);

                    if (result.data && result.data.ok) {
                        form.reset();
                        setStatus(form, result.data.message || 'Thank you. We will be in touch shortly.', 'success');
                        track('form_success', form.getAttribute('data-form-name') || 'form');
                        if (status) {
                            status.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
                        }
                        return;
                    }

                    var message = (result.data && result.data.message) || 'Something went wrong. Please try again.';
                    setStatus(form, message, 'error');
                    track('form_error', form.getAttribute('data-form-name') || 'form');

                    if (result.data && result.data.errors) {
                        showFieldErrors(form, result.data.errors);
                    }
                })
                .catch(function (error) {
                    setBusy(form, false);
                    setStatus(form, error && error.message
                        ? error.message
                        : 'We could not reach the booking service. Check your connection and try again, or call the clinic.', 'error');
                });
        });

        /* Keep the honeypot empty for honest visitors. */
        var honeypot = form.querySelector('#website');
        if (honeypot) {
            honeypot.addEventListener('input', function () {
                honeypot.value = '';
            });
        }
    });

    /* No-JavaScript fallback: the endpoint redirects back with a status flag. */
    (function statusFromQuery() {
        if (!window.location.search || !document.querySelector('form[data-ajax-form]')) {
            return;
        }

        var params = new URLSearchParams(window.location.search);
        var flags = {
            sent: 'Thank you. Your request has been received and we will be in touch shortly.',
            limited: 'Too many submissions from this device. Please wait a few minutes, or call the clinic.',
            invalid: 'Please check the highlighted fields and submit again.',
            error: 'Something went wrong on our side. Please try again or call the clinic.'
        };

        Object.keys(flags).forEach(function (key) {
            if (params.get(key) !== '1') {
                return;
            }

            var form = document.querySelector('form[data-ajax-form]');
            if (form) {
                setStatus(form, flags[key], key === 'sent' ? 'success' : 'error');
            }

            if (history.replaceState) {
                params.delete(key);
                var query = params.toString();
                history.replaceState(null, '', window.location.pathname + (query ? '?' + query : ''));
            }
        });
    })();

    /* ------------------------------------------------------------ flash bars */

    Array.prototype.forEach.call(document.querySelectorAll('[data-flash]'), function (flash) {
        var close = flash.querySelector('[data-flash-close]');
        var dismiss = function () {
            flash.classList.add('is-hiding');
            window.setTimeout(function () { flash.remove(); }, 320);
        };

        if (close) {
            close.addEventListener('click', dismiss);
        }

        if (flash.classList.contains('flash--success')) {
            window.setTimeout(dismiss, 9000);
        }
    });

    /* ------------------------------------------------------------- analytics */

    function track(event, detail) {
        if (Array.isArray(window.dataLayer)) {
            window.dataLayer.push({ event: event, detail: detail || null });
        }
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-track], [data-call-track]'), function (el) {
        el.addEventListener('click', function () {
            track(el.getAttribute('data-track') || (el.hasAttribute('data-call-track') ? 'click_call' : 'click'), el.getAttribute('href'));
        });
    });

    /* ------------------------------------------------- anchor links + copy */

    Array.prototype.forEach.call(document.querySelectorAll('a[href^="#"]'), function (link) {
        link.addEventListener('click', function (event) {
            var id = link.getAttribute('href').slice(1);
            if (!id) {
                return;
            }

            var target = document.getElementById(id);
            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });

            if (history.replaceState) {
                history.replaceState(null, '', '#' + id);
            }

            if (!target.hasAttribute('tabindex')) {
                target.setAttribute('tabindex', '-1');
            }
            target.focus({ preventScroll: true });
        });
    });

    /* Scroll to the FAQ entry the visitor was linked to. */
    if (window.location.hash) {
        var deep = document.querySelector('[data-faq-item][id="' + window.location.hash.slice(1) + '"]');
        if (deep && deep.tagName === 'DETAILS') {
            deep.open = true;
        }
    }

    /* The static 404 page is one shared file, so it cannot know which address
       the visitor typed. Show it here instead of echoing a build-time path. */
    Array.prototype.forEach.call(document.querySelectorAll('[data-404-path]'), function (node) {
        var requested = window.location.pathname + window.location.search;

        if (requested && requested !== '/' && requested.length > 1) {
            node.textContent = requested;
        }
    });

}());
