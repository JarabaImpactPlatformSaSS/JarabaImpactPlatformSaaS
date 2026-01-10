/**
 * @file
 * AgroConecta - Integración con AutoFirma
 *
 * Este módulo gestiona la comunicación con AutoFirma para firmar
 * documentos electrónicamente usando certificados digitales.
 *
 * Requiere: autofirma.js (librería oficial del Gobierno de España)
 *
 * @see https://github.com/ctt-gob-es/clienteafirma
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Namespace para la funcionalidad de AutoFirma
   */
  Drupal.agroconectaFirma = Drupal.agroconectaFirma || {};

  /**
   * Configuración por defecto
   */
  const CONFIG = {
    // Puertos estándar de AutoFirma para WebSocket
    WEBSOCKET_PORTS: [63117, 63217, 63317],
    // Tiempo de espera para conexión (ms)
    CONNECTION_TIMEOUT: 10000,
    // Endpoint base de la API
    API_BASE: '/api/autofirma',
    // Formato de firma para PDF
    SIGN_FORMAT: 'PAdES',
    // Algoritmo de firma
    SIGN_ALGORITHM: 'SHA256withRSA',
  };

  /**
   * Estado de la conexión con AutoFirma
   */
  let autoFirmaConnected = false;
  let activeWebSocket = null;

  /**
   * Inicializa el módulo de firma
   */
  Drupal.behaviors.agroconectaFirma = {
    attach: function (context, settings) {
      // Inicializar botones de firma
      once('agroconecta-firma', '.btn-firmar-documento', context).forEach(function (button) {
        button.addEventListener('click', function (e) {
          e.preventDefault();
          const documentId = this.dataset.documentId;
          const documentTitle = this.dataset.documentTitle || 'Documento';
          
          Drupal.agroconectaFirma.iniciarFirma(documentId, documentTitle);
        });
      });

      // Verificar disponibilidad de AutoFirma al cargar
      if (context === document) {
        Drupal.agroconectaFirma.checkAutoFirmaAvailability();
      }
    }
  };

  /**
   * Verifica si AutoFirma está disponible
   *
   * @returns {Promise<boolean>}
   */
  Drupal.agroconectaFirma.checkAutoFirmaAvailability = async function () {
    // Verificar si la librería autofirma.js está cargada
    if (typeof AutoFirma === 'undefined' && typeof MiniApplet === 'undefined') {
      console.warn('⚠️ AutoFirma: Librería autofirma.js no cargada');
      return false;
    }

    // Intentar conectar por WebSocket
    for (const port of CONFIG.WEBSOCKET_PORTS) {
      try {
        const connected = await this.tryWebSocketConnection(port);
        if (connected) {
          autoFirmaConnected = true;
          console.log('✅ AutoFirma: Conectado en puerto', port);
          return true;
        }
      } catch (e) {
        console.log('AutoFirma: Puerto', port, 'no disponible');
      }
    }

    console.log('ℹ️ AutoFirma: No detectado. Se usará protocolo afirma://');
    return false;
  };

  /**
   * Intenta conexión WebSocket a un puerto
   *
   * @param {number} port - Puerto a intentar
   * @returns {Promise<boolean>}
   */
  Drupal.agroconectaFirma.tryWebSocketConnection = function (port) {
    return new Promise((resolve, reject) => {
      const ws = new WebSocket(`wss://127.0.0.1:${port}/afirma`);
      const timeout = setTimeout(() => {
        ws.close();
        reject(new Error('Timeout'));
      }, 3000);

      ws.onopen = function () {
        clearTimeout(timeout);
        activeWebSocket = ws;
        resolve(true);
      };

      ws.onerror = function () {
        clearTimeout(timeout);
        reject(new Error('Connection failed'));
      };
    });
  };

  /**
   * Inicia el proceso de firma de un documento
   *
   * @param {string} documentId - ID del documento a firmar
   * @param {string} documentTitle - Título del documento (para mostrar)
   */
  Drupal.agroconectaFirma.iniciarFirma = async function (documentId, documentTitle) {
    // Mostrar modal de progreso
    this.showModal({
      title: 'Firmar Documento',
      documentTitle: documentTitle,
      status: 'connecting',
      message: 'Conectando con AutoFirma...',
    });

    try {
      // 1. Obtener el documento desde el servidor
      this.updateModal({ status: 'loading', message: 'Obteniendo documento...' });
      const docData = await this.fetchDocument(documentId);

      if (!docData.success) {
        throw new Error(docData.error || 'Error al obtener el documento');
      }

      // 2. Verificar AutoFirma
      this.updateModal({ status: 'connecting', message: 'Conectando con AutoFirma...' });
      
      // 3. Realizar la firma
      this.updateModal({ 
        status: 'signing', 
        message: 'Por favor, seleccione su certificado en AutoFirma...',
        showManualLink: true,
      });

      const signedContent = await this.signDocument(docData.data);

      // 4. Enviar documento firmado al servidor
      this.updateModal({ status: 'uploading', message: 'Guardando documento firmado...' });
      const result = await this.uploadSignedDocument(documentId, signedContent);

      if (!result.success) {
        throw new Error(result.error || 'Error al guardar el documento');
      }

      // 5. Éxito
      this.updateModal({
        status: 'success',
        message: '¡Documento firmado correctamente!',
        signedAt: result.data.signed_at,
      });

      // Recargar la página después de 2 segundos
      setTimeout(() => {
        window.location.reload();
      }, 2000);

    } catch (error) {
      console.error('Error en firma:', error);
      this.updateModal({
        status: 'error',
        message: this.getErrorMessage(error),
      });
    }
  };

  /**
   * Obtiene el documento desde el servidor
   *
   * @param {string} documentId
   * @returns {Promise<Object>}
   */
  Drupal.agroconectaFirma.fetchDocument = async function (documentId) {
    const response = await fetch(`${CONFIG.API_BASE}/documento/${documentId}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    return response.json();
  };

  /**
   * Firma el documento usando AutoFirma
   *
   * @param {Object} docData - Datos del documento
   * @returns {Promise<Object>} - Contenido firmado e info del certificado
   */
  Drupal.agroconectaFirma.signDocument = function (docData) {
    return new Promise((resolve, reject) => {
      // Preparar parámetros de firma
      const signParams = {
        format: docData.sign_format || CONFIG.SIGN_FORMAT,
        algorithm: docData.sign_algorithm || CONFIG.SIGN_ALGORITHM,
        // Parámetros adicionales para PAdES
        extraParams: 'signaturePage=1\nsignaturePositionOnPage=10,750,200,820',
      };

      // Callbacks
      const successCallback = function (signatureB64, certificateB64) {
        // Extraer información del certificado
        let certInfo = {};
        try {
          certInfo = Drupal.agroconectaFirma.parseCertificate(certificateB64);
        } catch (e) {
          console.warn('No se pudo parsear certificado:', e);
        }

        resolve({
          signed_content: signatureB64,
          certificate_info: certInfo,
        });
      };

      const errorCallback = function (errorType, errorMessage) {
        console.error('AutoFirma error:', errorType, errorMessage);
        
        // Mapear códigos de error
        const errorMessages = {
          'es.gob.afirma.core.AOCancelledOperationException': 'Operación cancelada por el usuario',
          'es.gob.afirma.keystores.AOCertificatesNotFoundException': 'No se encontraron certificados válidos',
          'java.security.cert.CertificateExpiredException': 'El certificado ha expirado',
        };

        const message = errorMessages[errorType] || errorMessage || 'Error desconocido en la firma';
        reject(new Error(message));
      };

      // Intentar firma con WebSocket o protocolo
      if (typeof AutoFirma !== 'undefined') {
        // Usar la librería AutoFirma
        AutoFirma.sign(
          docData.content,           // Datos en Base64
          signParams.algorithm,       // Algoritmo
          signParams.format,          // Formato
          signParams.extraParams,     // Parámetros extra
          successCallback,
          errorCallback
        );
      } else if (typeof MiniApplet !== 'undefined') {
        // Fallback a MiniApplet (versión anterior)
        MiniApplet.sign(
          docData.content,
          signParams.algorithm,
          signParams.format,
          signParams.extraParams,
          successCallback,
          errorCallback
        );
      } else {
        // Usar protocolo afirma://
        this.signWithProtocol(docData, signParams)
          .then(resolve)
          .catch(reject);
      }
    });
  };

  /**
   * Firma usando el protocolo afirma://
   *
   * @param {Object} docData
   * @param {Object} signParams
   * @returns {Promise<Object>}
   */
  Drupal.agroconectaFirma.signWithProtocol = function (docData, signParams) {
    return new Promise((resolve, reject) => {
      // Generar ID único para esta operación
      const opId = 'sign_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      
      // Crear URL para el protocolo
      const params = new URLSearchParams({
        op: 'sign',
        dat: docData.content,
        algorithm: signParams.algorithm,
        format: signParams.format,
        properties: btoa(signParams.extraParams),
        id: opId,
      });

      const afirmaUrl = `afirma://sign?${params.toString()}`;

      // Crear iframe oculto para lanzar el protocolo
      const iframe = document.createElement('iframe');
      iframe.style.display = 'none';
      iframe.src = afirmaUrl;
      document.body.appendChild(iframe);

      // Timeout para detectar si no hay respuesta
      const timeout = setTimeout(() => {
        document.body.removeChild(iframe);
        reject(new Error('AutoFirma no respondió. ¿Está instalado?'));
      }, CONFIG.CONNECTION_TIMEOUT);

      // Escuchar respuesta (AutoFirma enviará a una URL de callback)
      window.addEventListener('message', function handler(event) {
        if (event.data && event.data.opId === opId) {
          clearTimeout(timeout);
          window.removeEventListener('message', handler);
          document.body.removeChild(iframe);

          if (event.data.error) {
            reject(new Error(event.data.error));
          } else {
            resolve({
              signed_content: event.data.signature,
              certificate_info: event.data.certificate || {},
            });
          }
        }
      });
    });
  };

  /**
   * Sube el documento firmado al servidor
   *
   * @param {string} documentId
   * @param {Object} signedData
   * @returns {Promise<Object>}
   */
  Drupal.agroconectaFirma.uploadSignedDocument = async function (documentId, signedData) {
    // Obtener token CSRF
    const csrfToken = await this.getCsrfToken();

    const response = await fetch(`${CONFIG.API_BASE}/firmar`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken,
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        document_id: documentId,
        signed_content: signedData.signed_content,
        certificate_info: signedData.certificate_info,
      }),
    });

    return response.json();
  };

  /**
   * Obtiene el token CSRF de Drupal
   *
   * @returns {Promise<string>}
   */
  Drupal.agroconectaFirma.getCsrfToken = async function () {
    const response = await fetch('/session/token', {
      method: 'GET',
      credentials: 'same-origin',
    });
    return response.text();
  };

  /**
   * Parsea información básica del certificado
   *
   * @param {string} certB64 - Certificado en Base64
   * @returns {Object}
   */
  Drupal.agroconectaFirma.parseCertificate = function (certB64) {
    // Parsing básico - en producción usar librería de certificados
    const info = {
      cn: 'No disponible',
      issuer: 'No disponible',
      serial: 'No disponible',
    };

    try {
      // Decodificar y buscar campos comunes
      const decoded = atob(certB64);
      
      // Buscar CN (Common Name)
      const cnMatch = decoded.match(/CN=([^,\n]+)/);
      if (cnMatch) {
        info.cn = cnMatch[1];
      }

      // Buscar Issuer
      const issuerMatch = decoded.match(/O=([^,\n]+)/);
      if (issuerMatch) {
        info.issuer = issuerMatch[1];
      }
    } catch (e) {
      console.warn('Error parseando certificado:', e);
    }

    return info;
  };

  /**
   * Obtiene mensaje de error legible
   *
   * @param {Error} error
   * @returns {string}
   */
  Drupal.agroconectaFirma.getErrorMessage = function (error) {
    const errorMap = {
      'Operación cancelada': 'Ha cancelado la operación de firma.',
      'No se encontraron certificados': 'No se encontraron certificados digitales válidos. Asegúrese de tener instalado un certificado FNMT o DNIe.',
      'certificado ha expirado': 'Su certificado digital ha expirado. Por favor, renuévelo.',
      'AutoFirma no respondió': 'AutoFirma no está instalado o no respondió. <a href="https://firmaelectronica.gob.es/Home/Descargas.html" target="_blank">Descargar AutoFirma</a>',
    };

    for (const [key, message] of Object.entries(errorMap)) {
      if (error.message.includes(key)) {
        return message;
      }
    }

    return error.message || 'Error desconocido durante la firma.';
  };

  /**
   * Muestra el modal de firma
   *
   * @param {Object} options
   */
  Drupal.agroconectaFirma.showModal = function (options) {
    // Eliminar modal existente si hay
    const existingModal = document.getElementById('autofirma-modal');
    if (existingModal) {
      existingModal.remove();
    }

    // Crear modal
    const modal = document.createElement('div');
    modal.id = 'autofirma-modal';
    modal.className = 'autofirma-modal-overlay';
    modal.innerHTML = this.getModalContent(options);

    document.body.appendChild(modal);

    // Evento para cerrar
    modal.querySelector('.btn-close-modal')?.addEventListener('click', () => {
      modal.remove();
    });

    // Evento para lanzar manualmente
    modal.querySelector('.btn-launch-manual')?.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = 'afirma://echo';
    });
  };

  /**
   * Actualiza el contenido del modal
   *
   * @param {Object} options
   */
  Drupal.agroconectaFirma.updateModal = function (options) {
    const modal = document.getElementById('autofirma-modal');
    if (modal) {
      modal.innerHTML = this.getModalContent(options);

      // Re-vincular eventos
      modal.querySelector('.btn-close-modal')?.addEventListener('click', () => {
        modal.remove();
      });
    }
  };

  /**
   * Genera el HTML del modal
   *
   * @param {Object} options
   * @returns {string}
   */
  Drupal.agroconectaFirma.getModalContent = function (options) {
    const statusIcons = {
      connecting: '<div class="spinner-border text-primary" role="status"></div>',
      loading: '<div class="spinner-border text-primary" role="status"></div>',
      signing: '<i class="bi bi-pen-fill text-primary" style="font-size: 3rem;"></i>',
      uploading: '<div class="spinner-border text-success" role="status"></div>',
      success: '<i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>',
      error: '<i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>',
    };

    const canClose = ['success', 'error'].includes(options.status);

    return `
      <div class="autofirma-modal-content">
        <div class="autofirma-modal-header">
          <h5><i class="bi bi-pen me-2"></i>${options.title || 'Firmar Documento'}</h5>
          ${canClose ? '<button type="button" class="btn-close btn-close-modal"></button>' : ''}
        </div>
        <div class="autofirma-modal-body text-center">
          ${options.documentTitle ? `<p class="text-muted mb-4">Documento: <strong>${options.documentTitle}</strong></p>` : ''}
          
          <div class="autofirma-status-icon mb-4">
            ${statusIcons[options.status] || ''}
          </div>
          
          <p class="autofirma-message mb-3">${options.message || ''}</p>
          
          ${options.showManualLink ? `
            <p class="small text-muted">
              Si AutoFirma no se abre automáticamente,
              <a href="#" class="btn-launch-manual">haga clic aquí</a>.
            </p>
          ` : ''}
          
          ${options.status === 'success' && options.signedAt ? `
            <p class="small text-success">Firmado el ${new Date(options.signedAt).toLocaleString()}</p>
          ` : ''}
          
          ${options.status === 'error' ? `
            <div class="mt-4">
              <button type="button" class="btn btn-outline-secondary btn-close-modal">Cerrar</button>
            </div>
          ` : ''}
        </div>
        <div class="autofirma-modal-footer">
          <p class="small text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Necesita tener instalado <a href="https://firmaelectronica.gob.es/Home/Descargas.html" target="_blank">AutoFirma</a>
            y un certificado digital (DNIe o FNMT).
          </p>
        </div>
      </div>
    `;
  };

})(Drupal, drupalSettings, once);
