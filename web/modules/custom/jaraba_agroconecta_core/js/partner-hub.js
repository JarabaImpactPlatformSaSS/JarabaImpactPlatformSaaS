/**
 * @file
 * Comportamientos JS para el Hub Documental B2B de AgroConecta.
 *
 * Módulo: jaraba_agroconecta_core
 * Sprint: AC6-2 (Doc 82)
 *
 * @see Drupal.behaviors.agroPartnerHub
 * @see Drupal.behaviors.agroPartnerPortal
 * @see Drupal.behaviors.agroHubAnimations
 */

(function (Drupal) {
    'use strict';

    // CSRF token cache for POST/DELETE requests.
    var _csrfToken = null;
    function getCsrfToken() {
        if (_csrfToken) return Promise.resolve(_csrfToken);
        return fetch('/session/token')
            .then(function (r) { return r.text(); })
            .then(function (token) { _csrfToken = token; return token; });
    }

    /**
     * Comportamiento principal: Dashboard del Productor.
     *
     * Carga KPIs, lista de partners y documentos vía AJAX,
     * gestiona acciones CRUD y exportación CSV.
     */
    Drupal.behaviors.agroPartnerHub = {
        attach: function (context) {
            var dashboard = context.querySelector
                ? context.querySelector('.agro-hub')
                : null;
            if (!dashboard || dashboard.dataset.hubInit) {
                return;
            }
            dashboard.dataset.hubInit = '1';

            var kpiContainer = dashboard.querySelector('.agro-hub-kpis');
            var partnerBody = dashboard.querySelector('.agro-hub-partners__body');
            var docsGrid = dashboard.querySelector('.agro-hub-docs__grid');

            // Cargar KPIs.
            if (kpiContainer) {
                this.loadKpis(kpiContainer);
            }

            // Cargar partners.
            if (partnerBody) {
                this.loadPartners(partnerBody, 0);
            }

            // Cargar documentos.
            if (docsGrid) {
                this.loadDocuments(docsGrid, 0);
            }

            // Botón invitar partner.
            var inviteBtn = dashboard.querySelector('[data-action="invite-partner"]');
            if (inviteBtn) {
                inviteBtn.addEventListener('click', this.handleInvitePartner.bind(this));
            }

            // Botón exportar CSV.
            var exportBtn = dashboard.querySelector('[data-action="export-csv"]');
            if (exportBtn) {
                exportBtn.addEventListener('click', this.handleExportCsv.bind(this));
            }
        },

        loadKpis: function (container) {
            fetch('/api/v1/hub/analytics')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var cards = container.querySelectorAll('.agro-hub-kpis__card');
                    cards.forEach(function (card) {
                        var key = card.dataset.kpi;
                        var valueEl = card.querySelector('.agro-hub-kpis__value');
                        if (valueEl && data[key] !== undefined) {
                            valueEl.textContent = data[key];
                        }
                    });
                })
                .catch(function () {
                    // Error silencioso — los placeholders se mantienen.
                });
        },

        loadPartners: function (tbody, page) {
            var self = this;
            fetch('/api/v1/hub/partners?page=' + page + '&limit=20')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    tbody.innerHTML = '';
                    if (!data.items || data.items.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="agro-hub-empty__text">' +
                            Drupal.t('No hay partners registrados.') + '</td></tr>';
                        return;
                    }
                    data.items.forEach(function (partner) {
                        var row = document.createElement('tr');
                        row.className = 'agro-hub-partners__row';
                        row.innerHTML =
                            '<td class="agro-hub-partners__td">' + self.escapeHtml(partner.partner_name) + '</td>' +
                            '<td class="agro-hub-partners__td">' + self.escapeHtml(partner.partner_email) + '</td>' +
                            '<td class="agro-hub-partners__td">' + self.escapeHtml(partner.partner_type) + '</td>' +
                            '<td class="agro-hub-partners__td"><span class="agro-hub-badge agro-hub-badge--' +
                            partner.access_level + '">' + self.escapeHtml(partner.access_level) + '</span></td>' +
                            '<td class="agro-hub-partners__td"><span class="agro-hub-badge agro-hub-badge--' +
                            partner.status + '">' + self.escapeHtml(partner.status) + '</span></td>' +
                            '<td class="agro-hub-partners__td">' +
                            '<button class="agro-hub-btn agro-hub-btn--small agro-hub-btn--danger" ' +
                            'data-action="revoke" data-uuid="' + partner.uuid + '">' +
                            Drupal.t('Revocar') + '</button>' +
                            '</td>';
                        tbody.appendChild(row);
                    });

                    // Bind revoke buttons.
                    tbody.querySelectorAll('[data-action="revoke"]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            self.handleRevoke(btn.dataset.uuid, tbody);
                        });
                    });
                });
        },

        loadDocuments: function (grid, page) {
            var self = this;
            fetch('/api/v1/hub/documents?page=' + page + '&limit=20')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    grid.innerHTML = '';
                    if (!data.items || data.items.length === 0) {
                        grid.innerHTML = '<div class="agro-hub-empty"><div class="agro-hub-empty__text">' +
                            Drupal.t('No hay documentos subidos.') + '</div></div>';
                        return;
                    }
                    data.items.forEach(function (doc) {
                        var card = document.createElement('div');
                        card.className = 'agro-hub-doc-card agro-hub-animate';
                        card.innerHTML =
                            '<div class="agro-hub-doc-card__type">' + self.escapeHtml(doc.document_type) + '</div>' +
                            '<div class="agro-hub-doc-card__title">' + self.escapeHtml(doc.title) + '</div>' +
                            '<div class="agro-hub-doc-card__meta">' +
                            '<span>' + Drupal.t('v@version', { '@version': doc.version }) + '</span>' +
                            '<span class="agro-hub-badge agro-hub-badge--' + doc.min_access_level + '">' +
                            self.escapeHtml(doc.min_access_level) + '</span>' +
                            '</div>' +
                            '<div class="agro-hub-doc-card__footer">' +
                            '<span class="agro-hub-doc-card__downloads">' +
                            Drupal.t('@count descargas', { '@count': doc.download_count }) + '</span>' +
                            '<button class="agro-hub-btn agro-hub-btn--small agro-hub-btn--danger" ' +
                            'data-action="delete-doc" data-uuid="' + doc.uuid + '">' +
                            Drupal.t('Desactivar') + '</button>' +
                            '</div>';
                        grid.appendChild(card);
                    });

                    // Bind delete buttons.
                    grid.querySelectorAll('[data-action="delete-doc"]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            self.handleDeleteDoc(btn.dataset.uuid, grid);
                        });
                    });
                });
        },

        handleRevoke: function (uuid, tbody) {
            var self = this;
            if (!confirm(Drupal.t('Revocar el acceso de este partner?'))) {
                return;
            }
            getCsrfToken().then(function (token) {
                return fetch('/api/v1/hub/partners/' + uuid, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-Token': token }
                });
            })
                .then(function (r) { return r.json(); })
                .then(function () {
                    self.loadPartners(tbody, 0);
                });
        },

        handleDeleteDoc: function (uuid, grid) {
            var self = this;
            if (!confirm(Drupal.t('Desactivar este documento?'))) {
                return;
            }
            getCsrfToken().then(function (token) {
                return fetch('/api/v1/hub/documents/' + uuid, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-Token': token }
                });
            })
                .then(function (r) { return r.json(); })
                .then(function () {
                    self.loadDocuments(grid, 0);
                });
        },

        handleInvitePartner: function () {
            // Placeholder para slide-panel de invitación.
            alert(Drupal.t('Funcionalidad de invitación disponible próximamente.'));
        },

        handleExportCsv: function () {
            window.location.href = '/api/v1/hub/analytics?export=csv';
        },

        escapeHtml: function (text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(String(text)));
            return div.innerHTML;
        }
    };

    /**
     * Comportamiento: Portal Partner (público).
     *
     * Carga datos del portal vía token y muestra productos + documentos,
     * gestiona descargas individuales y packs ZIP.
     */
    Drupal.behaviors.agroPartnerPortal = {
        attach: function (context) {
            var portal = context.querySelector
                ? context.querySelector('.agro-portal')
                : null;
            if (!portal || portal.dataset.portalInit) {
                return;
            }
            portal.dataset.portalInit = '1';

            var token = portal.dataset.token;
            if (!token) {
                return;
            }

            this.loadPortalData(portal, token);
        },

        loadPortalData: function (portal, token) {
            var self = this;
            fetch('/api/v1/portal/' + token)
                .then(function (r) {
                    if (!r.ok) {
                        throw new Error(Drupal.t('Acceso no válido'));
                    }
                    return r.json();
                })
                .then(function (data) {
                    // Cabecera del productor.
                    var producerName = portal.querySelector('.agro-portal__producer-name');
                    if (producerName) {
                        producerName.textContent = data.producer.name;
                    }

                    var partnerInfo = portal.querySelector('.agro-portal__partner-info');
                    if (partnerInfo) {
                        partnerInfo.textContent = data.partner.name + ' - ' + data.partner.type;
                    }

                    var levelBadge = portal.querySelector('.agro-portal__level-badge');
                    if (levelBadge) {
                        levelBadge.textContent = Drupal.t('Nivel: @level', { '@level': data.partner.access_level });
                    }

                    // Renderizar productos con documentos.
                    self.renderProducts(portal, data.products, data.documents, token);
                })
                .catch(function () {
                    portal.innerHTML = '<div class="agro-hub-empty"><div class="agro-hub-empty__text">' +
                        Drupal.t('Error al cargar el portal. Verifique su enlace de acceso.') + '</div></div>';
                });
        },

        renderProducts: function (portal, products, documents, token) {
            var self = this;
            var grid = portal.querySelector('.agro-portal-products__grid');
            if (!grid) return;

            grid.innerHTML = '';

            // Documentos generales (sin producto).
            var generalDocs = documents.filter(function (d) { return !d.product_id; });
            if (generalDocs.length > 0) {
                grid.appendChild(self.createProductCard(
                    Drupal.t('Documentos Generales'),
                    generalDocs,
                    token,
                    null
                ));
            }

            // Documentos por producto.
            products.forEach(function (product) {
                var productDocs = documents.filter(function (d) {
                    return d.product_id === product.id;
                });
                if (productDocs.length > 0) {
                    grid.appendChild(self.createProductCard(
                        product.title,
                        productDocs,
                        token,
                        product.id
                    ));
                }
            });

            if (grid.children.length === 0) {
                grid.innerHTML = '<div class="agro-hub-empty"><div class="agro-hub-empty__text">' +
                    Drupal.t('No hay documentos disponibles para su nivel de acceso.') + '</div></div>';
            }
        },

        createProductCard: function (title, docs, token, productId) {
            var self = this;
            var card = document.createElement('div');
            card.className = 'agro-portal-product agro-hub-animate';

            var docsHtml = docs.map(function (doc) {
                return '<li class="agro-portal-product__doc-item">' +
                    '<span class="agro-portal-product__doc-name">' + self.escapeHtml(doc.title) + '</span>' +
                    '<a href="/api/v1/portal/' + token + '/documents/' + doc.uuid + '/download" ' +
                    'class="agro-hub-btn agro-hub-btn--small agro-hub-btn--secondary">' +
                    Drupal.t('Descargar') + '</a>' +
                    '</li>';
            }).join('');

            var packBtn = '';
            if (productId) {
                packBtn = '<div class="agro-portal-product__footer">' +
                    '<button class="agro-hub-btn agro-hub-btn--primary agro-hub-btn--small" ' +
                    'data-action="download-pack" data-product-id="' + productId + '" data-token="' + token + '">' +
                    Drupal.t('Descargar pack ZIP') + '</button></div>';
            }

            card.innerHTML =
                '<div class="agro-portal-product__name">' + self.escapeHtml(title) + '</div>' +
                '<ul class="agro-portal-product__docs">' + docsHtml + '</ul>' +
                packBtn;

            // Bind pack download.
            var packButton = card.querySelector('[data-action="download-pack"]');
            if (packButton) {
                packButton.addEventListener('click', function () {
                    self.handleDownloadPack(packButton);
                });
            }

            return card;
        },

        handleDownloadPack: function (btn) {
            var token = btn.dataset.token;
            var productId = btn.dataset.productId;
            btn.disabled = true;
            btn.textContent = Drupal.t('Generando...');

            getCsrfToken().then(function (csrfToken) {
                return fetch('/api/v1/portal/' + token + '/products/' + productId + '/download-pack', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken }
                });
            })
                .then(function (r) {
                    if (!r.ok) throw new Error('Pack error');
                    return r.blob();
                })
                .then(function (blob) {
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'pack-producto-' + productId + '.zip';
                    a.click();
                    URL.revokeObjectURL(url);
                    btn.disabled = false;
                    btn.textContent = Drupal.t('Descargar pack ZIP');
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.textContent = Drupal.t('Descargar pack ZIP');
                });
        },

        escapeHtml: function (text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(String(text)));
            return div.innerHTML;
        }
    };

    /**
     * Comportamiento: Animaciones de entrada escalonadas.
     */
    Drupal.behaviors.agroHubAnimations = {
        attach: function (context) {
            var elements = context.querySelectorAll
                ? context.querySelectorAll('.agro-hub-animate:not(.agro-hub-animate--visible)')
                : [];

            if (elements.length === 0) return;

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry, index) {
                        if (entry.isIntersecting) {
                            setTimeout(function () {
                                entry.target.classList.add('agro-hub-animate--visible');
                            }, index * 80);
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });

                elements.forEach(function (el) {
                    observer.observe(el);
                });
            }
            else {
                // Fallback: mostrar todo inmediatamente.
                elements.forEach(function (el) {
                    el.classList.add('agro-hub-animate--visible');
                });
            }
        }
    };

})(Drupal);
