document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }

    const dropdowns = document.querySelectorAll('.topbar-dropdown');

    const closeDropdowns = (except = null) => {
        dropdowns.forEach((dropdown) => {
            if (dropdown !== except) {
                dropdown.classList.remove('open');
                const btn = dropdown.querySelector('button');
                if (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        });
    };

    dropdowns.forEach((dropdown) => {
        const btn = dropdown.querySelector('button');

        if (!btn) {
            return;
        }

        btn.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = dropdown.classList.contains('open');
            closeDropdowns();

            if (!isOpen) {
                dropdown.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');

                if (dropdown.id === 'notificationDropdown') {
                    btn.classList.remove('notify-alert');
                }
            }
        });
    });

    document.addEventListener('click', () => {
        closeDropdowns();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDropdowns();
        }
    });

    const getFullscreenElement = () =>
        document.fullscreenElement
        || document.webkitFullscreenElement
        || document.mozFullScreenElement
        || document.msFullscreenElement
        || null;

    const requestFullscreen = (element) => {
        const request = element.requestFullscreen
            || element.webkitRequestFullscreen
            || element.mozRequestFullScreen
            || element.msRequestFullscreen;

        if (!request) {
            return Promise.reject(new Error('Fullscreen API not supported'));
        }

        return request.call(element);
    };

    const exitFullscreen = () => {
        const exit = document.exitFullscreen
            || document.webkitExitFullscreen
            || document.mozCancelFullScreen
            || document.msExitFullscreen;

        if (!exit) {
            return Promise.reject(new Error('Fullscreen API not supported'));
        }

        return exit.call(document);
    };

    const fullscreenToggle = document.getElementById('fullscreenToggle');
    let usingFallbackFullscreen = false;

    const updateFullscreenState = () => {
        if (!fullscreenToggle) {
            return;
        }

        const expandIcon = fullscreenToggle.querySelector('.icon-expand');
        const compressIcon = fullscreenToggle.querySelector('.icon-compress');
        const isNativeFullscreen = !!getFullscreenElement();
        const isFullscreen = isNativeFullscreen || document.body.classList.contains('admin-fullscreen-mode');

        fullscreenToggle.setAttribute('aria-pressed', isFullscreen ? 'true' : 'false');
        fullscreenToggle.title = isFullscreen ? 'Exit fullscreen' : 'Fullscreen';

        if (expandIcon && compressIcon) {
            expandIcon.classList.toggle('icon-hidden', isFullscreen);
            compressIcon.classList.toggle('icon-hidden', !isFullscreen);
        }
    };

    if (fullscreenToggle) {
        fullscreenToggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const isNativeFullscreen = !!getFullscreenElement();
            const isFallbackFullscreen = document.body.classList.contains('admin-fullscreen-mode');

            if (isNativeFullscreen) {
                exitFullscreen().catch(() => {
                    document.body.classList.remove('admin-fullscreen-mode');
                    updateFullscreenState();
                });
                return;
            }

            if (isFallbackFullscreen) {
                document.body.classList.remove('admin-fullscreen-mode');
                usingFallbackFullscreen = false;
                updateFullscreenState();
                return;
            }

            requestFullscreen(document.documentElement)
                .then(() => {
                    usingFallbackFullscreen = false;
                    updateFullscreenState();
                })
                .catch(() => {
                    document.body.classList.toggle('admin-fullscreen-mode');
                    usingFallbackFullscreen = document.body.classList.contains('admin-fullscreen-mode');
                    updateFullscreenState();
                });
        });

        ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach((eventName) => {
            document.addEventListener(eventName, () => {
                if (!getFullscreenElement() && !usingFallbackFullscreen) {
                    document.body.classList.remove('admin-fullscreen-mode');
                }
                updateFullscreenState();
            });
        });

        updateFullscreenState();
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('admin-fullscreen-mode')) {
            document.body.classList.remove('admin-fullscreen-mode');
            if (typeof updateFullscreenState === 'function') {
                updateFullscreenState();
            }
        }
    });

    const notificationBtn = document.getElementById('notificationBtn');
    const notifyBadge = document.getElementById('notifyBadge');
    const notifyHeaderBadge = document.getElementById('notifyHeaderBadge');
    const notificationList = document.getElementById('notificationList');

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    };

    const updateNotifyBadge = (count) => {
        if (!notifyBadge || !notificationBtn) {
            return;
        }

        notificationBtn.dataset.count = String(count);

        if (count > 0) {
            notifyBadge.hidden = false;
            notifyBadge.textContent = count > 9 ? '9+' : String(count);
        } else {
            notifyBadge.hidden = true;
        }

        if (notifyHeaderBadge) {
            if (count > 0) {
                notifyHeaderBadge.hidden = false;
                notifyHeaderBadge.textContent = `${count} new`;
            } else {
                notifyHeaderBadge.hidden = true;
            }
        }
    };

    const renderNotifications = (items, highlightIds = []) => {
        if (!notificationList) {
            return;
        }

        if (!items.length) {
            notificationList.innerHTML = `
                <div class="dropdown-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <p>No notifications yet</p>
                </div>
            `;
            return;
        }

        notificationList.innerHTML = items.map((item) => {
            const isNew = item.is_new;
            const highlightClass = highlightIds.includes(item.id) ? ' notify-highlight' : '';

            return `
                <a href="enquiry.php?id=${item.id}" class="notification-item${isNew ? ' unread is-new' : ''}${highlightClass}" data-id="${item.id}">
                    <div class="notification-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                    </div>
                    <div class="notification-content">
                        ${isNew ? '<span class="notification-new-label">New</span>' : ''}
                        <strong>${escapeHtml(item.name)}</strong>
                        <span>${escapeHtml(item.service)}</span>
                        <time>${escapeHtml(item.time_ago)}</time>
                    </div>
                    ${isNew ? '<span class="notification-dot"></span>' : ''}
                </a>
            `;
        }).join('');
    };

    let lastNotificationCount = notificationBtn ? parseInt(notificationBtn.dataset.count || '0', 10) : 0;
    let knownNotificationIds = new Set(
        notificationList
            ? Array.from(notificationList.querySelectorAll('.notification-item')).map((el) => parseInt(el.dataset.id, 10))
            : []
    );

    const pollNotifications = async () => {
        if (!notificationBtn) {
            return;
        }

        try {
            const response = await fetch('api/notifications.php', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const newCount = parseInt(data.count, 10) || 0;
            const items = Array.isArray(data.notifications) ? data.notifications : [];
            const newIds = items
                .filter((item) => !knownNotificationIds.has(item.id))
                .map((item) => item.id);

            if (newCount > lastNotificationCount || newIds.length > 0) {
                notificationBtn.classList.add('notify-alert');
            }

            updateNotifyBadge(newCount);
            renderNotifications(items, newIds);
            knownNotificationIds = new Set(items.map((item) => item.id));
            lastNotificationCount = newCount;
        } catch (error) {
            console.error('Notification poll failed:', error);
        }
    };

    if (notificationBtn) {
        pollNotifications();
        setInterval(pollNotifications, 15000);
    }

    const deleteForms = document.querySelectorAll('[data-confirm-delete]');
    deleteForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!confirm('Are you sure you want to delete this enquiry?')) {
                event.preventDefault();
            }
        });
    });

    const searchForm = document.getElementById('enquirySearchForm');
    const searchInput = document.getElementById('search');
    const searchResetBtn = document.getElementById('searchResetBtn');

    if (searchForm && searchInput) {
        let searchTimer = null;
        let lastSubmitted = searchInput.value.trim();

        const toggleResetBtn = () => {
            if (searchResetBtn) {
                searchResetBtn.hidden = searchInput.value.trim() === '';
            }
        };

        searchInput.addEventListener('input', () => {
            toggleResetBtn();

            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const current = searchInput.value.trim();
                if (current === lastSubmitted) {
                    return;
                }
                lastSubmitted = current;
                searchForm.requestSubmit();
            }, 400);
        });

        if (searchResetBtn) {
            searchResetBtn.addEventListener('click', () => {
                searchInput.value = '';
                toggleResetBtn();
                lastSubmitted = '';
                searchForm.requestSubmit();
            });
        }
    }

    const htmlEl = document.documentElement;
    const darkToggle = document.getElementById('darkModeToggle');
    const storedTheme = localStorage.getItem('admin-theme');
    const defaultDark = window.ADMIN_DARK_DEFAULT === true;

    const applyTheme = (isDark) => {
        htmlEl.setAttribute('data-theme', isDark ? 'dark' : 'light');
        if (darkToggle) {
            darkToggle.querySelector('.icon-moon')?.classList.toggle('icon-hidden', isDark);
            darkToggle.querySelector('.icon-sun')?.classList.toggle('icon-hidden', !isDark);
        }
    };

    applyTheme(storedTheme ? storedTheme === 'dark' : defaultDark);

    if (darkToggle) {
        darkToggle.addEventListener('click', () => {
            const isDark = htmlEl.getAttribute('data-theme') !== 'dark';
            applyTheme(isDark);
            localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
        });
    }

    const globalSearch = document.getElementById('globalSearch');
    const globalSearchResults = document.getElementById('globalSearchResults');
    let searchDebounce = null;

    if (globalSearch && globalSearchResults) {
        const renderResults = (results) => {
            if (!results.length) {
                globalSearchResults.innerHTML = '<div class="search-empty">No results found</div>';
                globalSearchResults.hidden = false;
                return;
            }

            globalSearchResults.innerHTML = results.map((item) => `
                <a href="${item.url}" class="search-result-item">
                    <strong>${item.title}</strong>
                    <span>${item.subtitle}</span>
                </a>
            `).join('');
            globalSearchResults.hidden = false;
        };

        globalSearch.addEventListener('input', () => {
            clearTimeout(searchDebounce);
            const q = globalSearch.value.trim();

            if (q.length < 2) {
                globalSearchResults.hidden = true;
                return;
            }

            searchDebounce = setTimeout(async () => {
                try {
                    const res = await fetch(`api/search.php?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    renderResults(data.results || []);
                } catch (e) {
                    globalSearchResults.hidden = true;
                }
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (!document.getElementById('globalSearchWrap')?.contains(e.target)) {
                globalSearchResults.hidden = true;
            }
        });
    }

    const selectAll = document.getElementById('selectAllEnquiries');
    const bulkBar = document.getElementById('bulkActionsBar');
    const bulkCount = document.getElementById('bulkSelectedCount');
    const bulkForm = document.getElementById('bulkForm');
    const bulkActionSelect = document.getElementById('bulkActionSelect');

    const getCheckedEnquiries = () => document.querySelectorAll('#bulkForm .enquiry-checkbox:checked');

    const updateSelectAllState = () => {
        if (!selectAll) {
            return;
        }

        const checkboxes = document.querySelectorAll('#bulkForm .enquiry-checkbox');
        const checked = getCheckedEnquiries();

        if (checkboxes.length === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
            return;
        }

        selectAll.checked = checked.length === checkboxes.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
    };

    const updateBulkBar = () => {
        const checked = getCheckedEnquiries();

        if (bulkBar && bulkCount) {
            bulkCount.textContent = String(checked.length);
            bulkBar.classList.toggle('is-active', checked.length > 0);
        }

        updateSelectAllState();
    };

    if (bulkForm) {
        bulkForm.addEventListener('change', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLInputElement)) {
                return;
            }

            if (target.id === 'selectAllEnquiries') {
                document.querySelectorAll('#bulkForm .enquiry-checkbox').forEach((checkbox) => {
                    checkbox.checked = target.checked;
                });
                updateBulkBar();
                return;
            }

            if (target.classList.contains('enquiry-checkbox')) {
                updateBulkBar();
            }
        });

        bulkForm.addEventListener('submit', (event) => {
            const action = bulkActionSelect?.value || '';
            const checked = getCheckedEnquiries();

            if (!action) {
                event.preventDefault();
                alert('Please choose a bulk action.');
                bulkActionSelect?.focus();
                return;
            }

            if (checked.length === 0) {
                event.preventDefault();
                alert('Please select at least one enquiry.');
                return;
            }

            if (action === 'delete' && !confirm(`Delete ${checked.length} selected enquiry/enquiries?`)) {
                event.preventDefault();
            }
        });
    }

    const bulkClearBtn = document.getElementById('bulkClearBtn');

    if (bulkClearBtn) {
        bulkClearBtn.addEventListener('click', () => {
            document.querySelectorAll('#bulkForm .enquiry-checkbox').forEach((checkbox) => {
                checkbox.checked = false;
            });

            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }

            if (bulkActionSelect) {
                bulkActionSelect.value = '';
            }

            updateBulkBar();
        });
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(() => {});
    }

    document.querySelectorAll('.sort-order-control').forEach((control) => {
        const input = control.querySelector('.sort-order-input');
        const decreaseBtn = control.querySelector('.sort-order-decrease');
        const increaseBtn = control.querySelector('.sort-order-increase');

        if (!input || !decreaseBtn || !increaseBtn) {
            return;
        }

        const step = (delta) => {
            const min = parseInt(input.min, 10) || 0;
            const max = parseInt(input.max, 10) || 999;
            const next = Math.min(max, Math.max(min, (parseInt(input.value, 10) || 0) + delta));
            input.value = String(next);
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        decreaseBtn.addEventListener('click', () => step(-1));
        increaseBtn.addEventListener('click', () => step(1));
    });

    const formatDateInputValue = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const updateFollowUpDateUi = (control) => {
        const input = control.querySelector('.follow-up-date-input');
        const clearBtn = control.querySelector('.follow-up-date-clear');

        if (!input || !clearBtn) {
            return;
        }

        clearBtn.hidden = input.value === '';
    };

    const setFollowUpDateValue = (input, value) => {
        input.value = value;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        updateFollowUpDateUi(input.closest('.follow-up-date-control'));
    };

    document.querySelectorAll('.follow-up-date-control').forEach((control) => {
        const input = control.querySelector('.follow-up-date-input');
        const clearBtn = control.querySelector('.follow-up-date-clear');
        const presets = control.parentElement?.querySelectorAll('.follow-up-date-preset') ?? [];

        if (!input) {
            return;
        }

        input.addEventListener('change', () => {
            updateFollowUpDateUi(control);
            presets.forEach((preset) => {
                const days = parseInt(preset.dataset.days, 10);
                const target = new Date();
                target.setDate(target.getDate() + days);
                preset.classList.toggle('is-active', input.value === formatDateInputValue(target));
            });
        });

        clearBtn?.addEventListener('click', () => {
            setFollowUpDateValue(input, '');
            presets.forEach((preset) => preset.classList.remove('is-active'));
        });

        presets.forEach((preset) => {
            preset.addEventListener('click', () => {
                const days = parseInt(preset.dataset.days, 10) || 0;
                const target = new Date();
                target.setDate(target.getDate() + days);
                setFollowUpDateValue(input, formatDateInputValue(target));
                presets.forEach((item) => item.classList.remove('is-active'));
                preset.classList.add('is-active');
            });
        });

        updateFollowUpDateUi(control);
        input.dispatchEvent(new Event('change'));
    });

    const clampSessionTimeout = (value, min, max, step) => {
        const parsed = parseInt(value, 10) || min;
        const clamped = Math.min(max, Math.max(min, parsed));
        const rounded = Math.round(clamped / step) * step;
        return Math.min(max, Math.max(min, rounded));
    };

    const updateSessionTimeoutPresets = (group, value) => {
        group.querySelectorAll('.session-timeout-preset').forEach((preset) => {
            const minutes = parseInt(preset.dataset.minutes, 10);
            preset.classList.toggle('is-active', minutes === value);
        });
    };

    document.querySelectorAll('.session-timeout-control').forEach((control) => {
        const input = control.querySelector('.session-timeout-input');
        const decreaseBtn = control.querySelector('.session-timeout-decrease');
        const increaseBtn = control.querySelector('.session-timeout-increase');
        const group = control.closest('.session-timeout-group');
        const min = parseInt(input?.min, 10) || 5;
        const max = parseInt(input?.max, 10) || 480;
        const step = parseInt(input?.step, 10) || 5;

        if (!input || !group) {
            return;
        }

        const applyValue = (nextValue) => {
            input.value = String(clampSessionTimeout(nextValue, min, max, step));
            updateSessionTimeoutPresets(group, parseInt(input.value, 10));
        };

        decreaseBtn?.addEventListener('click', () => {
            applyValue(parseInt(input.value, 10) - step);
        });

        increaseBtn?.addEventListener('click', () => {
            applyValue(parseInt(input.value, 10) + step);
        });

        input.addEventListener('change', () => {
            applyValue(input.value);
        });

        group.querySelectorAll('.session-timeout-preset').forEach((preset) => {
            preset.addEventListener('click', () => {
                applyValue(parseInt(preset.dataset.minutes, 10));
            });
        });

        applyValue(input.value);
    });

    document.querySelectorAll('.password-toggle').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const wrap = toggle.closest('.password-input-wrap');
            const input = wrap?.querySelector('.password-input');

            if (!input) {
                return;
            }

            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
            toggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        });
    });

    const smtpToggle = document.getElementById('smtp_enabled');
    const smtpFields = document.getElementById('smtp-fields');

    const syncSmtpFields = () => {
        if (!smtpToggle || !smtpFields) {
            return;
        }

        smtpFields.classList.toggle('is-visible', smtpToggle.checked);
    };

    smtpToggle?.addEventListener('change', syncSmtpFields);
    syncSmtpFields();
});
