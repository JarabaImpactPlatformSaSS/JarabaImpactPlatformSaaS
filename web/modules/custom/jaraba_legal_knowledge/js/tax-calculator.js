/**
 * @file
 * Tax Calculator — IRPF and IVA calculator forms with AJAX submission.
 *
 * Handles form submission for the IRPF and IVA tax calculators,
 * sending data to the API and displaying results.
 *
 * Legal Knowledge module.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.taxCalculator = {
    attach: function (context) {
      once('tax-calculator', '.tax-calculator', context).forEach(function (container) {
        var irpfForm = container.querySelector('#irpf-form');
        var ivaForm = container.querySelector('#iva-form');

        // ========================================
        // IRPF Calculator.
        // ========================================
        if (irpfForm) {
          irpfForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitIrpf();
          });
        }

        /**
         * Submit IRPF calculation via the API.
         */
        function submitIrpf() {
          var grossIncome = parseFloat(container.querySelector('#irpf-gross-income').value) || 0;
          var deductions = parseFloat(container.querySelector('#irpf-deductions').value) || 0;
          var community = container.querySelector('#irpf-autonomous-community').value;
          var year = parseInt(container.querySelector('#irpf-year').value, 10) || 2025;

          if (grossIncome <= 0) {
            return;
          }

          var payload = {
            gross_income: grossIncome,
            deductions: deductions,
            autonomous_community: community,
            year: year
          };

          var url = (drupalSettings.legalKnowledge && drupalSettings.legalKnowledge.apiIrpfUrl)
            || '/api/v1/legal/calculators/irpf';

          fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (data) {
              if (data.success) {
                displayIrpfResult(data.data);
              } else {
                console.warn('IRPF calculation error:', data.message);
              }
            })
            .catch(function (error) {
              console.warn('Legal Knowledge: Failed to calculate IRPF', error);
            });
        }

        /**
         * Display IRPF calculation results.
         *
         * @param {Object} data
         *   Calculation result data.
         */
        function displayIrpfResult(data) {
          var resultDiv = container.querySelector('#irpf-result');
          if (!resultDiv) {
            return;
          }

          resultDiv.style.display = 'block';

          setResultValue('#irpf-taxable-base', formatCurrency(data.taxable_base));
          setResultValue('#irpf-gross-tax', formatCurrency(data.gross_tax));
          setResultValue('#irpf-effective-rate', (data.effective_rate || 0).toFixed(2) + '%');
          setResultValue('#irpf-net-tax', formatCurrency(data.net_tax));

          // Bracket breakdown.
          var bracketsDiv = container.querySelector('#irpf-brackets');
          if (bracketsDiv && data.brackets && data.brackets.length > 0) {
            var html = '<h4 class="tax-calculator__brackets-title">' + Drupal.t('Desglose por tramos') + '</h4>';
            html += '<table class="tax-calculator__brackets-table">';
            html += '<thead><tr>';
            html += '<th>' + Drupal.t('Tramo') + '</th>';
            html += '<th>' + Drupal.t('Tipo') + '</th>';
            html += '<th>' + Drupal.t('Base') + '</th>';
            html += '<th>' + Drupal.t('Cuota') + '</th>';
            html += '</tr></thead><tbody>';
            data.brackets.forEach(function (bracket) {
              html += '<tr>';
              html += '<td>' + formatCurrency(bracket.from) + ' - ' + formatCurrency(bracket.to) + '</td>';
              html += '<td>' + bracket.rate + '%</td>';
              html += '<td>' + formatCurrency(bracket.taxable_amount) + '</td>';
              html += '<td>' + formatCurrency(bracket.tax) + '</td>';
              html += '</tr>';
            });
            html += '</tbody></table>';
            bracketsDiv.innerHTML = html;
          }
        }

        // ========================================
        // IVA Calculator.
        // ========================================
        if (ivaForm) {
          ivaForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitIva();
          });
        }

        /**
         * Submit IVA calculation via the API.
         */
        function submitIva() {
          var baseAmount = parseFloat(container.querySelector('#iva-base-amount').value) || 0;
          var rateType = container.querySelector('#iva-rate-type').value;
          var recargo = parseInt(container.querySelector('#iva-recargo').value, 10) === 1;

          if (baseAmount <= 0) {
            return;
          }

          var payload = {
            base_amount: baseAmount,
            rate_type: rateType,
            recargo_equivalencia: recargo
          };

          var url = (drupalSettings.legalKnowledge && drupalSettings.legalKnowledge.apiIvaUrl)
            || '/api/v1/legal/calculators/iva';

          fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (data) {
              if (data.success) {
                displayIvaResult(data.data);
              } else {
                console.warn('IVA calculation error:', data.message);
              }
            })
            .catch(function (error) {
              console.warn('Legal Knowledge: Failed to calculate IVA', error);
            });
        }

        /**
         * Display IVA calculation results.
         *
         * @param {Object} data
         *   Calculation result data.
         */
        function displayIvaResult(data) {
          var resultDiv = container.querySelector('#iva-result');
          if (!resultDiv) {
            return;
          }

          resultDiv.style.display = 'block';

          setResultValue('#iva-base', formatCurrency(data.base_amount));
          setResultValue('#iva-rate', (data.rate || 0) + '%');
          setResultValue('#iva-amount', formatCurrency(data.iva_amount));
          setResultValue('#iva-recargo-amount', formatCurrency(data.recargo_amount || 0));
          setResultValue('#iva-total', formatCurrency(data.total));
        }

        // ========================================
        // Helper functions.
        // ========================================

        /**
         * Set a result value by selector within the container.
         *
         * @param {string} selector
         *   CSS selector.
         * @param {string} value
         *   Value to display.
         */
        function setResultValue(selector, value) {
          var el = container.querySelector(selector);
          if (el) {
            el.textContent = value;
          }
        }

        /**
         * Format a number as currency (EUR).
         *
         * @param {number} amount
         *   The amount.
         *
         * @return {string}
         *   Formatted string.
         */
        function formatCurrency(amount) {
          if (typeof amount !== 'number' || isNaN(amount)) {
            return '0,00 \u20ac';
          }
          return amount.toLocaleString('es-ES', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          }) + ' \u20ac';
        }
      });
    }
  };

})(Drupal, drupalSettings, once);
