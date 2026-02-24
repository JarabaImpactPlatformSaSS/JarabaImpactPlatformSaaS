/**
 * @file
 * Business Model Canvas Editor interactivity.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  // Block type labels for modal title
  const blockLabels = {
    'key_partners': Drupal.t('Socios Clave'),
    'key_activities': Drupal.t('Actividades Clave'),
    'key_resources': Drupal.t('Recursos Clave'),
    'value_propositions': Drupal.t('Propuesta de Valor'),
    'customer_relationships': Drupal.t('Relaciones con Clientes'),
    'channels': Drupal.t('Canales'),
    'customer_segments': Drupal.t('Segmentos de Clientes'),
    'cost_structure': Drupal.t('Estructura de Costes'),
    'revenue_streams': Drupal.t('Fuentes de Ingresos')
  };

  Drupal.behaviors.canvasEditor = {
    attach: function (context, settings) {
      const editor = once('canvas-editor', '.canvas-editor', context);
      if (!editor.length) return;

      const editorEl = editor[0];
      const canvasId = editorEl.dataset.canvasId;
      const modal = document.getElementById('add-item-modal');
      const modalTitle = modal ? modal.querySelector('.modal__title') : null;
      let currentBlockType = null;

      // Initialize modal functionality
      if (modal) {
        // Close modal on cancel
        const cancelBtn = modal.querySelector('[value="cancel"]');
        if (cancelBtn) {
          cancelBtn.addEventListener('click', function () {
            modal.close();
          });
        }

        // Close modal on backdrop click
        modal.addEventListener('click', function (e) {
          if (e.target === modal) {
            modal.close();
          }
        });

        // Handle form submission
        const form = modal.querySelector('form');
        if (form) {
          form.addEventListener('submit', function (e) {
            e.preventDefault();
            const text = form.querySelector('#item-text').value.trim();
            // Get color from radio buttons or fallback to select
            const selectedColor = form.querySelector('input[name="color"]:checked');
            const color = selectedColor ? selectedColor.value : '#FFE082';

            if (text && currentBlockType && canvasId) {
              // Check if we're in edit mode
              if (modal.dataset.editMode === 'true' && modal.dataset.editItemId) {
                editItemInBlock(canvasId, currentBlockType, modal.dataset.editItemId, text, color);
              } else {
                addItemToBlock(canvasId, currentBlockType, text, color);
              }
            }
          });
        }

        // Close modal and reset edit mode
        modal.addEventListener('close', function () {
          delete modal.dataset.editMode;
          delete modal.dataset.editItemId;
        });
      }

      // Handle add item buttons
      editorEl.querySelectorAll('[data-action="add-item"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          const block = btn.closest('.canvas-block');
          if (block && modal) {
            currentBlockType = block.dataset.blockType;
            // Update modal title with block name
            if (modalTitle) {
              modalTitle.textContent = Drupal.t('Añadir a @block', { '@block': blockLabels[currentBlockType] || currentBlockType });
            }
            modal.showModal();
            // Focus the textarea
            const textarea = modal.querySelector('#item-text');
            if (textarea) {
              textarea.value = '';
              textarea.focus();
            }
          }
        });
      });

      // Handle delete item buttons - use event delegation for dynamically added elements
      editorEl.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('[data-action="delete-item"]');
        if (!deleteBtn) return;

        const postIt = deleteBtn.closest('.post-it');
        const block = deleteBtn.closest('.canvas-block');
        if (postIt && block) {
          const itemId = postIt.dataset.itemId;
          const blockType = block.dataset.blockType;

          console.log('Delete clicked:', { canvasId: canvasId, blockType: blockType, itemId: itemId });

          if (!itemId) {
            console.error('No item ID found on post-it');
            showMessage(Drupal.t('Error: elemento sin ID'), 'error');
            return;
          }

          if (confirm(Drupal.t('¿Eliminar este elemento?'))) {
            deleteItem(canvasId, blockType, itemId, postIt);
          }
        }
      });

      // Handle edit item - click on post-it text (not delete button)
      editorEl.addEventListener('click', function (e) {
        // Ignore if clicking delete button or other controls
        if (e.target.closest('[data-action="delete-item"]')) return;
        if (e.target.closest('[data-action="add-item"]')) return;

        const postIt = e.target.closest('.post-it');
        if (!postIt) return;

        const block = postIt.closest('.canvas-block');
        if (!block) return;

        const itemId = postIt.dataset.itemId;
        const blockType = block.dataset.blockType;

        if (!itemId) return;

        // Get current text (exclude the delete button text)
        const currentText = postIt.childNodes[0]?.textContent?.trim() || '';

        // Open modal in edit mode
        if (modal) {
          currentBlockType = blockType;
          modal.dataset.editItemId = itemId;
          modal.dataset.editMode = 'true';

          if (modalTitle) {
            const label = blockLabels[blockType] || blockType;
            modalTitle.textContent = Drupal.t('Editar en @block', { '@block': label });
          }

          const textarea = modal.querySelector('#item-text');
          if (textarea) {
            textarea.value = currentText;
          }

          modal.showModal();
          if (textarea) textarea.focus();
        }
      });

      // Handle save version button
      const saveBtn = editorEl.querySelector('[data-action="save-version"]');
      if (saveBtn) {
        saveBtn.addEventListener('click', function () {
          saveVersion(canvasId, saveBtn);
        });
      }

      // Handle export PDF button
      const exportBtn = editorEl.querySelector('[data-action="export-pdf"]');
      if (exportBtn) {
        exportBtn.addEventListener('click', function () {
          exportCanvasPdf(editorEl, exportBtn);
        });
      }

      // Handle analyze with AI button
      const analyzeBtn = editorEl.querySelector('[data-action="analyze-ai"]');
      if (analyzeBtn) {
        analyzeBtn.addEventListener('click', function () {
          analyzeWithAi(canvasId, analyzeBtn);
        });
      }

      // Handle status select change
      const statusSelect = editorEl.querySelector('.status-select');
      if (statusSelect) {
        statusSelect.addEventListener('change', function () {
          const newStatus = this.value;
          const oldStatus = this.dataset.currentStatus;
          const selectEl = this;

          getCsrfToken()
            .then(function (token) {
              return fetch('/api/v1/canvas/' + canvasId + '/status', {
                method: 'PATCH',
                headers: {
                  'Content-Type': 'application/json',
                  Accept: 'application/json',
                  'X-CSRF-Token': token,
                },
                body: JSON.stringify({ status: newStatus }),
              });
            })
            .then(function (response) {
              if (!response.ok) {
                throw new Error(Drupal.t('Error al cambiar estado'));
              }
              return response.json();
            })
            .then(function () {
              selectEl.classList.remove('status-select--' + oldStatus);
              selectEl.classList.add('status-select--' + newStatus);
              selectEl.dataset.currentStatus = newStatus;
              showMessage(Drupal.t('Estado actualizado'), 'success');
            })
            .catch(function (error) {
              showMessage(error.message, 'error');
              selectEl.value = oldStatus;
            });
        });
      }

      // Initialize SortableJS for drag & drop
      initSortable(editorEl, canvasId);

      /**
       * Initialize SortableJS on all canvas block items containers.
       */
      function initSortable(container, canvasId) {
        if (typeof Sortable === 'undefined') {
          console.warn('SortableJS not loaded');
          return;
        }

        const sortableContainers = container.querySelectorAll('[data-sortable="true"]');
        sortableContainers.forEach(function (itemsContainer) {
          const block = itemsContainer.closest('.canvas-block');
          const blockType = block ? block.dataset.blockType : null;

          new Sortable(itemsContainer, {
            animation: 150,
            ghostClass: 'post-it--ghost',
            chosenClass: 'post-it--chosen',
            dragClass: 'post-it--dragging',
            handle: '.post-it',
            filter: '[data-action="delete-item"], [data-action="add-item"]',
            preventOnFilter: true,
            onEnd: function (evt) {
              if (evt.oldIndex !== evt.newIndex && blockType) {
                reorderItems(canvasId, blockType, itemsContainer);
              }
            }
          });
        });
      }

      /**
       * Persist the new order of items via API.
       */
      function reorderItems(canvasId, blockType, container) {
        const items = container.querySelectorAll('.post-it');
        const order = [];
        items.forEach(function (item, index) {
          order.push({
            id: item.dataset.itemId,
            order: index
          });
        });

        getCsrfToken()
          .then(function (token) {
            return fetch('/api/v1/canvas/' + canvasId + '/blocks/' + blockType, {
              method: 'PATCH',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': token
              },
              body: JSON.stringify({ reorder: order })
            });
          })
          .then(function (response) {
            if (!response.ok) {
              throw new Error(Drupal.t('Error al reordenar'));
            }
            return response.json();
          })
          .then(function () {
            showMessage(Drupal.t('Orden actualizado'), 'success');
          })
          .catch(function (error) {
            showMessage(error.message, 'error');
          });
      }

      /**
       * Show a toast message.
       */
      function showMessage(message, type) {
        const toast = document.createElement('div');
        toast.className = 'canvas-toast canvas-toast--' + type;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function () {
          toast.classList.add('canvas-toast--visible');
        }, 10);
        setTimeout(function () {
          toast.classList.remove('canvas-toast--visible');
          setTimeout(function () {
            toast.remove();
          }, 300);
        }, 3000);
      }

      /**
       * Update item count badge for a block.
       */
      function updateItemCount(blockType) {
        const block = editorEl.querySelector('.canvas-block[data-block-type="' + blockType + '"]');
        if (!block) return;

        const items = block.querySelectorAll('.post-it');
        let countBadge = block.querySelector('.item-count');

        if (countBadge) {
          countBadge.textContent = items.length;
          if (items.length === 0) {
            countBadge.style.display = 'none';
          } else {
            countBadge.style.display = 'inline-block';
          }
        }

        // Also update completeness score
        updateCompleteness();
      }

      /**
       * Update the completeness score visually.
       */
      function updateCompleteness() {
        // Count blocks with at least one item
        const allBlocks = editorEl.querySelectorAll('.canvas-block');
        let filledBlocks = 0;

        allBlocks.forEach(function (block) {
          const items = block.querySelectorAll('.post-it');
          if (items.length > 0) {
            filledBlocks++;
          }
        });

        // Calculate percentage (9 total blocks)
        const totalBlocks = 9;
        const percentage = Math.round((filledBlocks / totalBlocks) * 100);

        // Update progress bar
        const progressBar = editorEl.querySelector('.completeness-bar__progress');
        if (progressBar) {
          progressBar.style.width = percentage + '%';
        }

        // Update label
        const label = editorEl.querySelector('.completeness-label');
        if (label) {
          label.textContent = Drupal.t('Completitud del canvas:') + ' ' + percentage + '%';
        }
      }

      /**
       * Get CSRF token for API calls.
       */
      function getCsrfToken() {
        return fetch('/session/token')
          .then(function (response) {
            return response.text();
          });
      }

      /**
       * Add item to a block via API.
       */
      function addItemToBlock(canvasId, blockType, text, color) {
        const submitBtn = modal.querySelector('[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = Drupal.t('Añadiendo...');
        }

        getCsrfToken().then(function (token) {
          return fetch('/api/v1/canvas/' + canvasId + '/blocks/' + blockType + '/items', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-Token': token
            },
            body: JSON.stringify({ text: text, color: color })
          });
        })
          .then(function (response) {
            if (response.ok) {
              // Close modal first
              if (modal && modal.open) {
                modal.close();
              }
              // Show success message
              showMessage(Drupal.t('Elemento añadido'), 'success');
              // Reload to show new item (simplest approach)
              setTimeout(function () {
                location.reload();
              }, 300);
            } else {
              return response.json().then(function (data) {
                throw new Error(data.error || data.message || 'Error al añadir elemento');
              });
            }
          })
          .catch(function (error) {
            console.error('Add item error:', error);
            showMessage(error.message || Drupal.t('Error al añadir elemento'), 'error');
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.textContent = Drupal.t('Añadir nota');
            }
          });
      }

      /**
       * Delete item from a block via API.
       */
      function deleteItem(canvasId, blockType, itemId, element) {
        // Visual feedback - fade out
        element.style.opacity = '0.5';
        element.style.pointerEvents = 'none';

        getCsrfToken().then(function (token) {
          return fetch('/api/v1/canvas/' + canvasId + '/blocks/' + blockType + '/items/' + itemId, {
            method: 'DELETE',
            headers: {
              'X-CSRF-Token': token,
              'Accept': 'application/json'
            }
          });
        })
          .then(function (response) {
            if (response.ok) {
              // Remove element from DOM
              element.remove();
              // Update counts and completeness
              updateItemCount(blockType);
              showMessage(Drupal.t('Elemento eliminado'), 'success');
            } else {
              // Restore element
              element.style.opacity = '1';
              element.style.pointerEvents = 'auto';
              throw new Error('Error al eliminar');
            }
          })
          .catch(function (error) {
            console.error('Delete error:', error);
            element.style.opacity = '1';
            element.style.pointerEvents = 'auto';
            showMessage(Drupal.t('Error al eliminar elemento'), 'error');
          });
      }

      /**
       * Edit existing item in a block via API.
       */
      function editItemInBlock(canvasId, blockType, itemId, text, color) {
        const submitBtn = modal.querySelector('[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = Drupal.t('Guardando...');
        }

        getCsrfToken().then(function (token) {
          return fetch('/api/v1/canvas/' + canvasId + '/blocks/' + blockType + '/items/' + itemId, {
            method: 'PATCH',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': token,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ text: text, color: color })
          });
        })
          .then(function (response) {
            if (!response.ok) {
              return response.json().then(function (data) {
                throw new Error(data.error || 'Error al actualizar');
              });
            }
            return response.json();
          })
          .then(function (data) {
            // Update the post-it in the DOM
            const postIt = editorEl.querySelector('.post-it[data-item-id="' + itemId + '"]');
            if (postIt) {
              // Update text (first text node, preserve delete button)
              postIt.childNodes[0].textContent = text + ' ';
              postIt.style.backgroundColor = color;
            }

            // Close and reset modal
            modal.close();
            delete modal.dataset.editMode;
            delete modal.dataset.editItemId;

            // Reset button
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.textContent = Drupal.t('Guardar');
            }

            // Reset form
            const form = modal.querySelector('form');
            if (form) form.reset();

            showMessage(Drupal.t('Elemento actualizado'), 'success');
          })
          .catch(function (error) {
            console.error('Edit item error:', error);
            showMessage(error.message || Drupal.t('Error al actualizar elemento'), 'error');
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.textContent = Drupal.t('Guardar');
            }
          });
      }

      // Note: updateItemCount is defined above (line 145-166) and includes updateCompleteness()

      /**
       * Save a new version of the canvas.
       */
      function saveVersion(canvasId, btn) {
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = Drupal.t('Guardando...');

        getCsrfToken().then(function (token) {
          return fetch('/api/v1/canvas/' + canvasId + '/version', {
            method: 'POST',
            headers: {
              'X-CSRF-Token': token,
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
          });
        })
          .then(function (response) {
            if (response.ok) {
              showMessage(Drupal.t('Versión guardada correctamente'), 'success');
              location.reload();
            } else {
              throw new Error('Error al guardar versión');
            }
          })
          .catch(function () {
            showMessage(Drupal.t('Error al guardar versión'), 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
          });
      }

      /**
       * Export canvas to PDF using html2pdf.js.
       */
      function exportCanvasPdf(editorEl, btn) {
        if (typeof html2pdf === 'undefined') {
          showMessage(Drupal.t('La librería de exportación no está disponible'), 'error');
          return;
        }

        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = Drupal.t('Generando PDF...');

        // Get canvas info from settings
        const settings = drupalSettings.canvasEditor || drupalSettings.canvas_editor || {};
        const title = settings.canvasTitle || editorEl.querySelector('.canvas-editor__title h1')?.textContent?.trim() || 'Business Model Canvas';
        const ownerName = settings.ownerName || drupalSettings.user?.name || 'Usuario';
        const sector = settings.sector || 'General';
        const businessStage = settings.businessStage || 'idea';
        const completeness = settings.completenessScore || 0;
        const version = settings.version || 1;
        const currentYear = new Date().getFullYear();
        const filename = title.replace(/[^a-zA-Z0-9\s]/g, '').replace(/\s+/g, '_') + '_v' + version + '_' + new Date().toISOString().split('T')[0] + '.pdf';

        // Business stage labels
        const stageLabels = {
          'idea': Drupal.t('Idea'),
          'validation': Drupal.t('Validación'),
          'launch': Drupal.t('Lanzamiento'),
          'growth': Drupal.t('Crecimiento'),
          'scale': Drupal.t('Escala')
        };
        const stageLabel = stageLabels[businessStage] || businessStage;

        // Get the grid element to export
        const gridEl = editorEl.querySelector('.canvas-editor__grid');
        if (!gridEl) {
          showMessage(Drupal.t('No se encontró el contenido del canvas'), 'error');
          btn.disabled = false;
          btn.innerHTML = originalText;
          return;
        }

        // Clone the grid for PDF (to avoid modifying the original)
        const clone = gridEl.cloneNode(true);

        // Remove action buttons and SVG icons (they don't render in html2canvas)
        clone.querySelectorAll('[data-action], .canvas-block__add-btn').forEach(function (el) {
          el.remove();
        });
        clone.querySelectorAll('svg, .jaraba-icon').forEach(function (el) {
          el.remove();
        });

        // Create wrapper with header and footer
        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'padding: 24px; background: white; font-family: Arial, sans-serif; min-height: 100%; display: flex; flex-direction: column;';

        // Professional header
        const header = document.createElement('div');
        header.style.cssText = 'margin-bottom: 24px; padding-bottom: 16px; border-bottom: 3px solid #1565C0;';
        header.innerHTML =
          '<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">' +
          '<div>' +
          '<span style="display: inline-block; background: #1565C0; color: white; font-size: 10px; font-weight: 600; padding: 4px 10px; border-radius: 4px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Business Model Canvas</span>' +
          '<h1 style="margin: 0; color: #1e293b; font-size: 26px; font-weight: 700;">' + title + '</h1>' +
          '</div>' +
          '<div style="text-align: right; color: #64748b; font-size: 11px;">' +
          '<div style="font-weight: 600; color: #1e293b; font-size: 13px;">' + Drupal.t('Versión') + ' ' + version + '</div>' +
          '<div>' + Drupal.t('Completitud:') + ' ' + Math.round(completeness) + '%</div>' +
          '</div>' +
          '</div>' +
          '<div style="display: flex; gap: 24px; color: #475569; font-size: 12px;">' +
          '<div><strong>' + Drupal.t('Titular:') + '</strong> ' + ownerName + '</div>' +
          '<div><strong>' + Drupal.t('Sector:') + '</strong> ' + sector.charAt(0).toUpperCase() + sector.slice(1) + '</div>' +
          '<div><strong>' + Drupal.t('Etapa:') + '</strong> ' + stageLabel + '</div>' +
          '<div><strong>' + Drupal.t('Fecha:') + '</strong> ' + new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' }) + '</div>' +
          '</div>';

        // Content container
        const content = document.createElement('div');
        content.style.cssText = 'flex: 1;';
        content.appendChild(clone);

        // Professional footer with branding
        const footer = document.createElement('div');
        footer.style.cssText = 'text-align: center; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 10px;';
        footer.innerHTML =
          '<div style="display: flex; justify-content: space-between; align-items: center;">' +
          '<div style="text-align: left;">' +
          '<div style="font-weight: 600; color: #1565C0;">Jaraba Impact Platform</div>' +
          '<div>https://jarabaimpact.com</div>' +
          '</div>' +
          '<div style="text-align: center;">' +
          '<div>' + Drupal.t('Documento generado automáticamente') + '</div>' +
          '<div>© ' + currentYear + ' Plataforma de Ecosistemas Digitales</div>' +
          '</div>' +
          '<div style="text-align: right;">' +
          '<div>' + Drupal.t('Página') + ' 1</div>' +
          '<div style="color: #94a3b8;">' + Drupal.t('Todos los derechos reservados') + '</div>' +
          '</div>' +
          '</div>';

        wrapper.appendChild(header);
        wrapper.appendChild(content);
        wrapper.appendChild(footer);

        // PDF options - landscape for canvas layout
        const opt = {
          margin: [12, 12, 18, 12],
          filename: filename,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: { scale: 2, useCORS: true, logging: false, allowTaint: true },
          jsPDF: { unit: 'mm', format: 'a3', orientation: 'landscape' }
        };

        html2pdf().set(opt).from(wrapper).save()
          .then(function () {
            showMessage(Drupal.t('PDF exportado correctamente'), 'success');
          })
          .catch(function (error) {
            console.error('PDF export error:', error);
            showMessage(Drupal.t('Error al exportar PDF'), 'error');
          })
          .finally(function () {
            btn.disabled = false;
            btn.innerHTML = originalText;
          });
      }

      /**
       * Trigger AI analysis via FAB Copilot.
       * Instead of doing the analysis here and reloading, we delegate to the FAB.
       */
      function analyzeWithAi(canvasId, btn) {
        // Find the FAB container and trigger the analysis there
        const fabContainer = document.querySelector('.agent-fab-container');
        if (fabContainer && fabContainer.openPanel) {
          fabContainer.openPanel();
          // Dispatch a custom event that the FAB listens for
          document.dispatchEvent(new CustomEvent('canvas-analyze-request', {
            detail: { canvasId: canvasId }
          }));
        } else {
          // Fallback: show a message if FAB is not available
          showMessage(Drupal.t('Abre el Copiloto Canvas para analizar'), 'info');
        }
      }
    }
  };

})(Drupal, drupalSettings, once);
