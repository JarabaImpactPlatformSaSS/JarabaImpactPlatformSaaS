/**
 * @file
 * Firma electrónica — SignaturePad + integración AutoFirma.
 *
 * Sprint 2 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * Componente Vanilla JS con Drupal.behaviors:
 * - Canvas HTML5 con touch/mouse events para firma manuscrita.
 * - Interpolación Bézier para trazo suave.
 * - Integración con endpoints de firma via drupalSettings.
 * - CSRF token cacheado (CSRF-JS-CACHE-001).
 * - URLs via drupalSettings, NUNCA hardcoded (ROUTE-LANGPREFIX-001).
 * - XSS: Drupal.checkPlain() para datos de API (INNERHTML-XSS-001).
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /** @type {string|null} Cached CSRF token. */
  var csrfToken = null;

  /**
   * Obtiene token CSRF cacheado.
   *
   * CSRF-JS-CACHE-001.
   *
   * @return {Promise<string>}
   */
  function getCsrfToken() {
    if (csrfToken) {
      return Promise.resolve(csrfToken);
    }
    return fetch(Drupal.url('session/token'))
      .then(function (response) { return response.text(); })
      .then(function (token) {
        csrfToken = token;
        return token;
      });
  }

  /**
   * Signature Pad — Canvas de firma manuscrita táctil.
   */
  Drupal.behaviors.jarabaFirmaElectronica = {
    attach: function (context) {
      var pads = once('jaraba-firma-pad', '.firma-pad', context);

      pads.forEach(function (container) {
        var canvas = container.querySelector('.firma-pad__canvas');
        if (!canvas) {
          return;
        }

        var ctx = canvas.getContext('2d');
        var isDrawing = false;
        var lastPoint = null;
        var hasSignature = false;
        var points = [];

        // Configuración del trazo.
        var strokeWidth = 2.5;
        var strokeColor = getComputedStyle(document.documentElement)
          .getPropertyValue('--ej-color-corporate').trim() || '#233D63';

        // Resolver tamaño del canvas al contenedor.
        function resizeCanvas() {
          var rect = container.getBoundingClientRect();
          var dpr = window.devicePixelRatio || 1;
          canvas.width = rect.width * dpr;
          canvas.height = Math.max(200, rect.height) * dpr;
          canvas.style.width = rect.width + 'px';
          canvas.style.height = Math.max(200, rect.height) + 'px';
          ctx.scale(dpr, dpr);
          ctx.strokeStyle = strokeColor;
          ctx.lineWidth = strokeWidth;
          ctx.lineCap = 'round';
          ctx.lineJoin = 'round';
        }

        resizeCanvas();

        // Obtener coordenadas relativas al canvas.
        function getPoint(e) {
          var rect = canvas.getBoundingClientRect();
          var touch = e.touches ? e.touches[0] : e;
          return {
            x: touch.clientX - rect.left,
            y: touch.clientY - rect.top,
            time: Date.now()
          };
        }

        // Dibujar segmento con interpolación Bézier cuadrática.
        function drawSegment(p0, p1) {
          if (!p0) {
            ctx.beginPath();
            ctx.moveTo(p1.x, p1.y);
            return;
          }
          var midX = (p0.x + p1.x) / 2;
          var midY = (p0.y + p1.y) / 2;
          ctx.quadraticCurveTo(p0.x, p0.y, midX, midY);
          ctx.stroke();
        }

        // === Event handlers ===

        function onStart(e) {
          e.preventDefault();
          isDrawing = true;
          lastPoint = getPoint(e);
          points = [lastPoint];
          ctx.beginPath();
          ctx.moveTo(lastPoint.x, lastPoint.y);
        }

        function onMove(e) {
          if (!isDrawing) return;
          e.preventDefault();
          var point = getPoint(e);
          points.push(point);
          drawSegment(lastPoint, point);
          lastPoint = point;
          hasSignature = true;
        }

        function onEnd(e) {
          if (!isDrawing) return;
          e.preventDefault();
          isDrawing = false;
          lastPoint = null;
          updateUI();
        }

        // Touch events.
        canvas.addEventListener('touchstart', onStart, { passive: false });
        canvas.addEventListener('touchmove', onMove, { passive: false });
        canvas.addEventListener('touchend', onEnd);
        canvas.addEventListener('touchcancel', onEnd);

        // Mouse events.
        canvas.addEventListener('mousedown', onStart);
        canvas.addEventListener('mousemove', onMove);
        canvas.addEventListener('mouseup', onEnd);
        canvas.addEventListener('mouseleave', onEnd);

        // === Botones ===

        var btnLimpiar = container.querySelector('[data-firma-action="limpiar"]');
        var btnFirmar = container.querySelector('[data-firma-action="firmar"]');
        var statusEl = container.querySelector('.firma-pad__status');
        var feedbackEl = container.querySelector('.firma-pad__feedback');

        function updateUI() {
          if (btnFirmar) {
            btnFirmar.disabled = !hasSignature;
            btnFirmar.setAttribute('aria-disabled', hasSignature ? 'false' : 'true');
          }
        }

        function clearCanvas() {
          var dpr = window.devicePixelRatio || 1;
          ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
          hasSignature = false;
          points = [];
          updateUI();
          if (feedbackEl) {
            feedbackEl.textContent = '';
            feedbackEl.className = 'firma-pad__feedback';
          }
        }

        function showFeedback(message, type) {
          if (feedbackEl) {
            feedbackEl.textContent = message;
            feedbackEl.className = 'firma-pad__feedback firma-pad__feedback--' + type;
          }
        }

        if (btnLimpiar) {
          btnLimpiar.addEventListener('click', function (e) {
            e.preventDefault();
            clearCanvas();
          });
        }

        if (btnFirmar) {
          btnFirmar.disabled = true;
          btnFirmar.addEventListener('click', function (e) {
            e.preventDefault();
            if (!hasSignature) return;
            enviarFirma();
          });
        }

        // === Envío de firma al servidor ===

        function enviarFirma() {
          if (!hasSignature) return;

          var settings = drupalSettings.jarabaFirma || {};
          var url = settings.firmarTactilUrl;
          var documentoId = settings.documentoId;

          if (!url || !documentoId) {
            showFeedback(Drupal.t('Error de configuración. Recarga la página.'), 'error');
            return;
          }

          // Capturar firma como PNG base64.
          var firmaBase64 = canvas.toDataURL('image/png').split(',')[1];

          // Deshabilitar botones durante envío.
          if (btnFirmar) btnFirmar.disabled = true;
          if (btnLimpiar) btnLimpiar.disabled = true;
          showFeedback(Drupal.t('Enviando firma...'), 'loading');

          getCsrfToken().then(function (token) {
            return fetch(url, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
              },
              body: JSON.stringify({
                documento_id: parseInt(documentoId, 10),
                firma_base64: firmaBase64
              })
            });
          })
          .then(function (response) { return response.json(); })
          .then(function (data) {
            if (data.success) {
              showFeedback(Drupal.checkPlain(data.message || Drupal.t('Documento firmado correctamente.')), 'success');
              // Disparar evento custom para que el portal se actualice.
              container.dispatchEvent(new CustomEvent('jaraba:firma-completada', {
                bubbles: true,
                detail: { documentoId: documentoId, estado: data.estado }
              }));
              // Deshabilitar canvas tras firma exitosa.
              canvas.style.pointerEvents = 'none';
              canvas.style.opacity = '0.6';
            }
            else {
              showFeedback(Drupal.checkPlain(data.message || Drupal.t('Error al firmar.')), 'error');
              if (btnFirmar) btnFirmar.disabled = false;
              if (btnLimpiar) btnLimpiar.disabled = false;
            }
          })
          .catch(function () {
            showFeedback(Drupal.t('Error de conexión. Inténtalo de nuevo.'), 'error');
            if (btnFirmar) btnFirmar.disabled = false;
            if (btnLimpiar) btnLimpiar.disabled = false;
          });
        }

        // Resize handler.
        var resizeTimer;
        window.addEventListener('resize', function () {
          clearTimeout(resizeTimer);
          resizeTimer = setTimeout(function () {
            // Save current signature state.
            var imageData = hasSignature ? canvas.toDataURL() : null;
            resizeCanvas();
            // Restore signature if it existed.
            if (imageData) {
              var img = new Image();
              img.onload = function () {
                ctx.drawImage(img, 0, 0, canvas.width / (window.devicePixelRatio || 1), canvas.height / (window.devicePixelRatio || 1));
              };
              img.src = imageData;
            }
          }, 250);
        });

        // Initial UI state.
        updateUI();
      });
    }
  };

})(Drupal, drupalSettings, once);
