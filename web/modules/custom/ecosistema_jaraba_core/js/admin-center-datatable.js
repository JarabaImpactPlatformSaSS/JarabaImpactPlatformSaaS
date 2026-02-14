/**
 * @file
 * Admin Center DataTable — Reusable vanilla JS table component.
 *
 * Declarative usage via data attributes:
 *   <div data-datatable
 *        data-datatable-url="/api/v1/admin/tenants"
 *        data-datatable-columns='[{"key":"label","label":"Nombre","sortable":true},...]'
 *        data-datatable-page-size="20">
 *   </div>
 *
 * Features:
 *   - Server-side pagination (offset/limit).
 *   - Column sorting (single column click).
 *   - Debounced search input.
 *   - Status filter chips.
 *   - Configurable row actions (via data-datatable-actions).
 *   - Loading skeleton state.
 *   - Empty state.
 *   - Accessible: ARIA roles, keyboard navigation.
 *
 * F6 — Doc 181 / Spec f104 §FASE 2.
 */
(function (Drupal, once) {
  'use strict';

  /**
   * DataTable class — manages a single table instance.
   */
  class DataTable {

    /**
     * @param {HTMLElement} el - The container element with data-datatable.
     */
    constructor(el) {
      this.el = el;
      this.url = el.dataset.datatableUrl || '';
      this.columns = JSON.parse(el.dataset.datatableColumns || '[]');
      this.actions = JSON.parse(el.dataset.datatableActions || '[]');
      this.pageSize = parseInt(el.dataset.datatablePageSize, 10) || 20;
      this.exportUrl = el.dataset.datatableExport || '';

      // State.
      this.data = [];
      this.total = 0;
      this.offset = 0;
      this.sort = el.dataset.datatableDefaultSort || '';
      this.dir = el.dataset.datatableDefaultDir || 'ASC';
      this.query = '';
      this.filters = {};
      this.loading = false;

      this.init();
    }

    init() {
      this.render();
      this.fetch();
    }

    /**
     * Build the DOM structure.
     */
    render() {
      this.el.classList.add('ac-datatable');
      this.el.innerHTML = '';

      // Toolbar.
      const toolbar = document.createElement('div');
      toolbar.className = 'ac-datatable__toolbar';

      // Search.
      const searchWrap = document.createElement('div');
      searchWrap.className = 'ac-datatable__search';
      const searchInput = document.createElement('input');
      searchInput.type = 'search';
      searchInput.className = 'ac-datatable__search-input';
      searchInput.placeholder = Drupal.t('Buscar...');
      searchInput.setAttribute('aria-label', Drupal.t('Buscar en la tabla'));
      searchInput.addEventListener('input', this.debounce(() => {
        this.query = searchInput.value.trim();
        this.offset = 0;
        this.fetch();
      }, 350));
      searchWrap.appendChild(searchInput);
      toolbar.appendChild(searchWrap);

      // Filter chips container (populated by setFilters).
      this.filtersEl = document.createElement('div');
      this.filtersEl.className = 'ac-datatable__filters';
      toolbar.appendChild(this.filtersEl);

      // Export button.
      if (this.exportUrl) {
        const exportBtn = document.createElement('button');
        exportBtn.type = 'button';
        exportBtn.className = 'ac-datatable__export-btn';
        exportBtn.textContent = Drupal.t('Exportar');
        exportBtn.setAttribute('aria-label', Drupal.t('Exportar datos'));
        exportBtn.addEventListener('click', () => this.handleExport());
        toolbar.appendChild(exportBtn);
      }

      this.el.appendChild(toolbar);

      // Table wrapper (scrollable).
      const tableWrap = document.createElement('div');
      tableWrap.className = 'ac-datatable__table-wrap';
      this.tableEl = document.createElement('table');
      this.tableEl.className = 'ac-datatable__table';
      this.tableEl.setAttribute('role', 'grid');
      tableWrap.appendChild(this.tableEl);
      this.el.appendChild(tableWrap);

      // Pagination bar.
      this.paginationEl = document.createElement('div');
      this.paginationEl.className = 'ac-datatable__pagination';
      this.el.appendChild(this.paginationEl);
    }

    /**
     * Set filter chip options.
     *
     * @param {string} key - Filter key (e.g. 'status').
     * @param {Array} options - [{value: 'active', label: 'Activos'}, ...].
     */
    setFilters(key, options) {
      const group = document.createElement('div');
      group.className = 'ac-datatable__filter-group';
      group.dataset.filterKey = key;

      // "All" chip.
      const allChip = document.createElement('button');
      allChip.type = 'button';
      allChip.className = 'ac-datatable__chip ac-datatable__chip--active';
      allChip.textContent = Drupal.t('Todos');
      allChip.addEventListener('click', () => {
        delete this.filters[key];
        this.offset = 0;
        this.updateChipState(group, allChip);
        this.fetch();
      });
      group.appendChild(allChip);

      options.forEach(opt => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'ac-datatable__chip';
        chip.textContent = opt.label;
        chip.dataset.value = opt.value;
        chip.addEventListener('click', () => {
          this.filters[key] = opt.value;
          this.offset = 0;
          this.updateChipState(group, chip);
          this.fetch();
        });
        group.appendChild(chip);
      });

      this.filtersEl.appendChild(group);
    }

    updateChipState(group, activeChip) {
      group.querySelectorAll('.ac-datatable__chip').forEach(c => {
        c.classList.remove('ac-datatable__chip--active');
      });
      activeChip.classList.add('ac-datatable__chip--active');
    }

    /**
     * Fetch data from the server.
     */
    async fetch() {
      if (!this.url || this.loading) return;
      this.loading = true;
      this.showSkeleton();

      const params = new URLSearchParams();
      params.set('limit', this.pageSize);
      params.set('offset', this.offset);
      if (this.query) params.set('q', this.query);
      if (this.sort) params.set('sort', this.sort);
      if (this.dir) params.set('dir', this.dir);

      Object.entries(this.filters).forEach(([k, v]) => {
        params.set(k, v);
      });

      try {
        const res = await fetch(`${this.url}?${params.toString()}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const json = await res.json();

        if (json.success) {
          this.data = json.data || [];
          this.total = json.meta?.pagination?.total ?? this.data.length;
        }
        else {
          this.data = [];
          this.total = 0;
        }
      }
      catch (err) {
        this.data = [];
        this.total = 0;
      }

      this.loading = false;
      this.renderTable();
      this.renderPagination();
    }

    /**
     * Show skeleton loading state.
     */
    showSkeleton() {
      const rows = Math.min(this.pageSize, 5);
      let html = '<thead><tr>';
      this.columns.forEach(col => {
        html += `<th class="ac-datatable__th">${this.escapeHtml(col.label)}</th>`;
      });
      if (this.actions.length) {
        html += `<th class="ac-datatable__th ac-datatable__th--actions">${Drupal.t('Acciones')}</th>`;
      }
      html += '</tr></thead><tbody>';
      for (let i = 0; i < rows; i++) {
        html += '<tr class="ac-datatable__row ac-datatable__row--skeleton">';
        const colCount = this.columns.length + (this.actions.length ? 1 : 0);
        for (let j = 0; j < colCount; j++) {
          html += '<td class="ac-datatable__td"><span class="ac-datatable__skeleton-bar"></span></td>';
        }
        html += '</tr>';
      }
      html += '</tbody>';
      this.tableEl.innerHTML = html;
    }

    /**
     * Render the table header + body.
     */
    renderTable() {
      let html = '<thead><tr>';

      // Header cells with sort.
      this.columns.forEach(col => {
        const isSorted = this.sort === col.key;
        const sortClass = isSorted ? ` ac-datatable__th--sorted-${this.dir.toLowerCase()}` : '';
        const sortable = col.sortable !== false;

        html += `<th class="ac-datatable__th${sortClass}${sortable ? ' ac-datatable__th--sortable' : ''}"`;
        if (sortable) {
          html += ` data-sort-key="${col.key}" role="columnheader" aria-sort="${isSorted ? (this.dir === 'ASC' ? 'ascending' : 'descending') : 'none'}" tabindex="0"`;
        }
        html += `>${this.escapeHtml(col.label)}`;
        if (sortable) {
          html += '<span class="ac-datatable__sort-icon"></span>';
        }
        html += '</th>';
      });

      if (this.actions.length) {
        html += `<th class="ac-datatable__th ac-datatable__th--actions">${Drupal.t('Acciones')}</th>`;
      }
      html += '</tr></thead>';

      // Body.
      html += '<tbody>';
      if (this.data.length === 0) {
        const colSpan = this.columns.length + (this.actions.length ? 1 : 0);
        html += `<tr><td colspan="${colSpan}" class="ac-datatable__empty">`;
        html += `<p>${Drupal.t('No se encontraron resultados.')}</p>`;
        html += '</td></tr>';
      }
      else {
        this.data.forEach(row => {
          html += '<tr class="ac-datatable__row">';
          this.columns.forEach(col => {
            const val = row[col.key] ?? '';
            const rendered = col.render ? col.render(val, row) : this.renderCell(col, val, row);
            html += `<td class="ac-datatable__td ac-datatable__td--${col.key}">${rendered}</td>`;
          });
          if (this.actions.length) {
            html += '<td class="ac-datatable__td ac-datatable__td--actions">';
            html += this.renderActions(row);
            html += '</td>';
          }
          html += '</tr>';
        });
      }
      html += '</tbody>';

      this.tableEl.innerHTML = html;

      // Bind sort clicks.
      this.tableEl.querySelectorAll('[data-sort-key]').forEach(th => {
        const handler = () => {
          const key = th.dataset.sortKey;
          if (this.sort === key) {
            this.dir = this.dir === 'ASC' ? 'DESC' : 'ASC';
          }
          else {
            this.sort = key;
            this.dir = 'ASC';
          }
          this.offset = 0;
          this.fetch();
        };
        th.addEventListener('click', handler);
        th.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            handler();
          }
        });
      });

      // Bind row action clicks.
      this.tableEl.querySelectorAll('[data-action]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const action = btn.dataset.action;
          const rowId = btn.dataset.rowId;
          const row = this.data.find(r => String(r.id) === String(rowId));
          this.el.dispatchEvent(new CustomEvent('datatable:action', {
            detail: { action, row, rowId },
            bubbles: true,
          }));
        });
      });
    }

    /**
     * Default cell renderer with status badges.
     */
    renderCell(col, val, row) {
      if (col.type === 'status') {
        const statusClass = `ac-datatable__badge ac-datatable__badge--${this.escapeHtml(String(val))}`;
        return `<span class="${statusClass}">${this.escapeHtml(String(val))}</span>`;
      }
      if (col.type === 'link') {
        const url = row[col.urlKey || 'url'] || '#';
        return `<a href="${this.escapeHtml(url)}" class="ac-datatable__link">${this.escapeHtml(String(val))}</a>`;
      }
      return this.escapeHtml(String(val));
    }

    /**
     * Render action buttons for a row.
     */
    renderActions(row) {
      return this.actions.map(act => {
        const attrs = [];
        attrs.push(`data-action="${this.escapeHtml(act.action)}"`);
        attrs.push(`data-row-id="${this.escapeHtml(String(row.id))}"`);

        if (act.slidePanel) {
          attrs.push(`data-slide-panel="${this.escapeHtml(act.slidePanel)}"`);
          const url = act.slidePanelUrl
            ? act.slidePanelUrl.replace('{id}', row.id)
            : '';
          if (url) attrs.push(`data-slide-panel-url="${this.escapeHtml(url)}"`);
          if (act.slidePanelTitle) {
            const title = act.slidePanelTitle.replace('{label}', row.label || '');
            attrs.push(`data-slide-panel-title="${this.escapeHtml(title)}"`);
          }
        }

        const cls = `ac-datatable__action-btn ac-datatable__action-btn--${this.escapeHtml(act.action)}`;
        return `<button type="button" class="${cls}" ${attrs.join(' ')} title="${this.escapeHtml(act.label)}">${this.escapeHtml(act.label)}</button>`;
      }).join('');
    }

    /**
     * Render pagination controls.
     */
    renderPagination() {
      const totalPages = Math.ceil(this.total / this.pageSize);
      const currentPage = Math.floor(this.offset / this.pageSize) + 1;

      if (totalPages <= 1) {
        this.paginationEl.innerHTML = `<span class="ac-datatable__pagination-info">${this.total} ${Drupal.t('resultados')}</span>`;
        return;
      }

      let html = '<div class="ac-datatable__pagination-info">';
      const from = this.offset + 1;
      const to = Math.min(this.offset + this.pageSize, this.total);
      html += `${from}–${to} ${Drupal.t('de')} ${this.total}`;
      html += '</div>';

      html += '<div class="ac-datatable__pagination-controls">';

      // Prev.
      html += `<button type="button" class="ac-datatable__page-btn" data-page="prev"${currentPage <= 1 ? ' disabled' : ''} aria-label="${Drupal.t('Anterior')}">`;
      html += '&laquo;';
      html += '</button>';

      // Page numbers (show max 5).
      const startPage = Math.max(1, currentPage - 2);
      const endPage = Math.min(totalPages, startPage + 4);
      for (let p = startPage; p <= endPage; p++) {
        const active = p === currentPage ? ' ac-datatable__page-btn--active' : '';
        html += `<button type="button" class="ac-datatable__page-btn${active}" data-page="${p}">${p}</button>`;
      }

      // Next.
      html += `<button type="button" class="ac-datatable__page-btn" data-page="next"${currentPage >= totalPages ? ' disabled' : ''} aria-label="${Drupal.t('Siguiente')}">`;
      html += '&raquo;';
      html += '</button>';

      html += '</div>';

      this.paginationEl.innerHTML = html;

      // Bind pagination clicks.
      this.paginationEl.querySelectorAll('[data-page]').forEach(btn => {
        btn.addEventListener('click', () => {
          const page = btn.dataset.page;
          if (page === 'prev') {
            this.offset = Math.max(0, this.offset - this.pageSize);
          }
          else if (page === 'next') {
            this.offset = Math.min((totalPages - 1) * this.pageSize, this.offset + this.pageSize);
          }
          else {
            this.offset = (parseInt(page, 10) - 1) * this.pageSize;
          }
          this.fetch();
        });
      });
    }

    /**
     * Handle export click.
     */
    handleExport() {
      const params = new URLSearchParams();
      Object.entries(this.filters).forEach(([k, v]) => {
        params.set(k, v);
      });
      if (this.query) params.set('q', this.query);

      window.open(`${this.exportUrl}?${params.toString()}`, '_blank');
    }

    /**
     * Escape HTML entities.
     */
    escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    /**
     * Debounce helper.
     */
    debounce(fn, delay) {
      let timer;
      return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
      };
    }

    /**
     * Refresh data (external trigger).
     */
    refresh() {
      this.fetch();
    }
  }

  // Expose class globally for programmatic use.
  Drupal.AdminCenterDataTable = DataTable;

  /**
   * Drupal behavior: auto-init DataTable on data-datatable elements.
   */
  Drupal.behaviors.adminCenterDataTable = {
    attach(context) {
      once('ac-datatable', '[data-datatable]', context).forEach(el => {
        el._datatable = new DataTable(el);
      });
    },
  };

})(Drupal, once);
