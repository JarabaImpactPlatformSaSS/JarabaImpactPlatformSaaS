/**
 * @file
 * Notification Center - AgroConecta
 *
 * Componente frontend para notificaciones in-app:
 * - Centro de notificaciones (campana + dropdown)
 * - Preferencias de notificación
 * - Polling para nuevas notificaciones
 */
(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Formatea tiempo relativo.
     */
    function timeAgo(dateStr) {
        var now = new Date();
        var date = new Date(dateStr);
        var diff = Math.floor((now - date) / 1000);

        if (diff < 60) return Drupal.t('Ahora');
        if (diff < 3600) return Math.floor(diff / 60) + ' min';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd';
        return date.toLocaleDateString('es-ES');
    }

    /**
     * Icono por tipo de notificación.
     * Genera un span con clase CSS para renderizar el icono vía SCSS.
     */
    function typeIcon(type) {
        return '<span class="agro-bell__type-icon agro-bell__type-icon--' + type + '" aria-hidden="true"></span>';
    }

    Drupal.behaviors.agroconectaNotifications = {
        attach: function (context) {

            // === Notification Bell ===
            var bells = once('notification-bell', '[data-agro-notifications]', context);
            bells.forEach(function (bell) {
                var badge = bell.querySelector('.agro-bell__badge');
                var dropdown = bell.querySelector('.agro-bell__dropdown');
                var list = bell.querySelector('.agro-bell__list');
                var toggle = bell.querySelector('.agro-bell__toggle');

                if (!toggle || !dropdown) return;

                // Toggle dropdown.
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdown.classList.toggle('agro-bell__dropdown--open');

                    if (dropdown.classList.contains('agro-bell__dropdown--open') && list) {
                        loadNotifications(list, badge);
                    }
                });

                // Click outside to close.
                document.addEventListener('click', function (e) {
                    if (!bell.contains(e.target)) {
                        dropdown.classList.remove('agro-bell__dropdown--open');
                    }
                });
            });

            /**
             * Carga las notificaciones del usuario.
             */
            function loadNotifications(listEl, badgeEl) {
                listEl.innerHTML = '<div class="agro-bell__loading"><span class="agro-spinner"></span></div>';

                fetch('/api/v1/agro/notifications/logs?limit=20&status=sent')
                    .then(function (r) { return r.json(); })
                    .then(function (resp) {
                        if (!resp.data || resp.data.length === 0) {
                            listEl.innerHTML = '<p class="agro-bell__empty">' + Drupal.t('No tienes notificaciones.') + '</p>';
                            if (badgeEl) badgeEl.style.display = 'none';
                            return;
                        }

                        var html = '';
                        var unread = 0;
                        resp.data.forEach(function (notif) {
                            var isRead = notif.opened;
                            if (!isRead) unread++;

                            html += '<div class="agro-bell__item' + (isRead ? '' : ' agro-bell__item--unread') + '">';
                            html += '<span class="agro-bell__icon">' + typeIcon(notif.type) + '</span>';
                            html += '<div class="agro-bell__content">';
                            html += '<p class="agro-bell__subject">' + Drupal.checkPlain(notif.subject) + '</p>';
                            html += '<time class="agro-bell__time">' + timeAgo(notif.created) + '</time>';
                            html += '</div>';
                            html += '</div>';
                        });

                        listEl.innerHTML = html;
                        if (badgeEl) {
                            if (unread > 0) {
                                badgeEl.textContent = unread > 9 ? '9+' : unread;
                                badgeEl.style.display = 'flex';
                            } else {
                                badgeEl.style.display = 'none';
                            }
                        }
                    })
                    .catch(function () {
                        listEl.innerHTML = '<p class="agro-bell__error">' + Drupal.t('Error al cargar notificaciones.') + '</p>';
                    });
            }

            // === Notification Preferences ===
            var prefsForms = once('notif-prefs', '[data-agro-notif-prefs]', context);
            prefsForms.forEach(function (container) {
                var types = (container.dataset.agroNotifTypes || '').split(',').filter(Boolean);
                if (types.length === 0) return;

                var html = '<h3 class="agro-prefs__title">' + Drupal.t('Preferencias de Notificación') + '</h3>';
                html += '<div class="agro-prefs__grid">';

                types.forEach(function (type) {
                    html += '<div class="agro-prefs__row" data-type="' + type + '">';
                    html += '<span class="agro-prefs__type-label">' + typeIcon(type) + ' ' + type.replace(/_/g, ' ') + '</span>';
                    html += '<div class="agro-prefs__channels">';

                    ['email', 'push', 'sms', 'in_app'].forEach(function (ch) {
                        var label = { email: Drupal.t('Email'), push: Drupal.t('Push'), sms: Drupal.t('SMS'), in_app: Drupal.t('In-App') };
                        html += '<label class="agro-prefs__channel">';
                        html += '<input type="checkbox" name="' + type + '_' + ch + '" data-channel="' + ch + '" />';
                        html += '<span>' + (label[ch] || ch) + '</span>';
                        html += '</label>';
                    });

                    html += '</div></div>';
                });

                html += '</div>';
                html += '<button class="agro-prefs__save btn btn-primary" type="button">' + Drupal.t('Guardar preferencias') + '</button>';
                html += '<div class="agro-prefs__feedback"></div>';
                container.innerHTML = html;

                // Load existing prefs.
                types.forEach(function (type) {
                    fetch('/api/v1/agro/notifications/preferences?type=' + type)
                        .then(function (r) { return r.json(); })
                        .then(function (resp) {
                            if (resp.data && resp.data.all_channels) {
                                var row = container.querySelector('[data-type="' + type + '"]');
                                if (!row) return;
                                Object.keys(resp.data.all_channels).forEach(function (ch) {
                                    var input = row.querySelector('[data-channel="' + ch + '"]');
                                    if (input) input.checked = resp.data.all_channels[ch];
                                });
                            }
                        });
                });

                // Save handler.
                var saveBtn = container.querySelector('.agro-prefs__save');
                var feedback = container.querySelector('.agro-prefs__feedback');
                saveBtn.addEventListener('click', function () {
                    saveBtn.disabled = true;
                    saveBtn.textContent = Drupal.t('Guardando...');
                    var promises = [];

                    types.forEach(function (type) {
                        var row = container.querySelector('[data-type="' + type + '"]');
                        if (!row) return;
                        var channels = {};
                        row.querySelectorAll('[data-channel]').forEach(function (input) {
                            channels[input.dataset.channel] = input.checked;
                        });

                        promises.push(
                            fetch('/api/v1/agro/notifications/preferences', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({ type: type, channels: channels }),
                            })
                        );
                    });

                    Promise.all(promises)
                        .then(function () {
                            feedback.className = 'agro-prefs__feedback agro-prefs__feedback--success';
                            feedback.textContent = Drupal.t('Preferencias guardadas correctamente.');
                            saveBtn.disabled = false;
                            saveBtn.textContent = Drupal.t('Guardar preferencias');
                        })
                        .catch(function () {
                            feedback.className = 'agro-prefs__feedback agro-prefs__feedback--error';
                            feedback.textContent = Drupal.t('Error al guardar preferencias.');
                            saveBtn.disabled = false;
                            saveBtn.textContent = Drupal.t('Guardar preferencias');
                        });
                });
            });

            // === Animaciones ===
            var items = once('notif-animate', '.agro-bell__item', context);
            items.forEach(function (item, i) {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-10px)';
                setTimeout(function () {
                    item.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, i * 40);
            });
        }
    };

})(Drupal, drupalSettings, once);
