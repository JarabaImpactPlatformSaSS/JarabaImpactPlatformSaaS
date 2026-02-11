/**
 * @file
 * Life Wheel Chart Premium - Radar chart animado e interactivo.
 *
 * PROPÓSITO:
 * Renderiza un radar chart premium con animaciones, hover effects, 
 * y feedback visual mejorado para las 8 áreas de la vida.
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    let hoveredArea = null;
    let chartData = [];
    let chartLabels = [];

    Drupal.behaviors.lifeWheelChart = {
        attach: function (context) {
            const chartCanvas = context.querySelector('#life-wheel-chart');
            if (!chartCanvas) return;

            once('life-wheel-chart', chartCanvas, context).forEach(function (canvas) {
                const settings = drupalSettings.jarabaSelfDiscovery || {};
                const scores = settings.scores || {
                    career: 5, finance: 5, health: 5, family: 5,
                    social: 5, growth: 5, leisure: 5, environment: 5
                };

                chartLabels = [
                    Drupal.t('Trabajo'),
                    Drupal.t('Finanzas'),
                    Drupal.t('Salud'),
                    Drupal.t('Familia'),
                    Drupal.t('Social'),
                    Drupal.t('Desarrollo'),
                    Drupal.t('Ocio'),
                    Drupal.t('Entorno')
                ];

                chartData = Object.values(scores);

                // Usar Canvas 2D premium (independiente de Chart.js).
                drawPremiumRadar(canvas, chartLabels, chartData, true);

                // Listeners para sliders.
                const sliders = context.querySelectorAll('.lw-slider');
                sliders.forEach(function (slider) {
                    slider.addEventListener('input', function () {
                        const area = this.dataset.area;
                        const value = parseInt(this.value, 10);

                        const valueLabel = context.querySelector('#value-' + area);
                        if (valueLabel) valueLabel.textContent = value;

                        const areaIndex = {
                            career: 0, finance: 1, health: 2, family: 3,
                            social: 4, growth: 5, leisure: 6, environment: 7
                        };

                        chartData[areaIndex[area]] = value;
                        drawPremiumRadar(canvas, chartLabels, chartData, false);
                    });
                });

                // Botón reset.
                const resetBtn = context.querySelector('#reset-sliders');
                if (resetBtn) {
                    resetBtn.addEventListener('click', function () {
                        sliders.forEach(function (slider) {
                            slider.value = 5;
                            const area = slider.dataset.area;
                            const valueLabel = context.querySelector('#value-' + area);
                            if (valueLabel) valueLabel.textContent = '5';
                        });
                        chartData = [5, 5, 5, 5, 5, 5, 5, 5];
                        drawPremiumRadar(canvas, chartLabels, chartData, true);
                    });
                }

                // Botón guardar.
                const saveBtn = context.querySelector('#save-assessment');
                if (saveBtn) {
                    saveBtn.addEventListener('click', function () {
                        saveAssessment(context, settings.saveUrl);
                    });
                }

                // Mouse events para hover.
                canvas.onmousemove = function (e) {
                    const rect = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;
                    const x = (e.clientX - rect.left) * scaleX;
                    const y = (e.clientY - rect.top) * scaleY;

                    const centerX = canvas.width / 2;
                    const centerY = canvas.height / 2;
                    const dx = x - centerX;
                    const dy = y - centerY;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    const radius = Math.min(centerX, centerY) - 60;

                    if (dist <= radius + 30) {
                        let angle = Math.atan2(dy, dx) + Math.PI / 2;
                        if (angle < 0) angle += Math.PI * 2;
                        const segmentAngle = (Math.PI * 2) / chartData.length;
                        const newHovered = Math.floor(angle / segmentAngle) % chartData.length;

                        if (newHovered !== hoveredArea) {
                            hoveredArea = newHovered;
                            canvas.style.cursor = 'pointer';
                            drawPremiumRadar(canvas, chartLabels, chartData, false);
                        }
                    } else if (hoveredArea !== null) {
                        hoveredArea = null;
                        canvas.style.cursor = 'default';
                        drawPremiumRadar(canvas, chartLabels, chartData, false);
                    }
                };

                canvas.onmouseleave = function () {
                    if (hoveredArea !== null) {
                        hoveredArea = null;
                        drawPremiumRadar(canvas, chartLabels, chartData, false);
                    }
                };

                canvas.onclick = function () {
                    if (hoveredArea !== null) {
                        const slider = context.querySelector('[data-area="' + Object.keys({
                            career: 0, finance: 1, health: 2, family: 3,
                            social: 4, growth: 5, leisure: 6, environment: 7
                        }).find(key => ({
                            career: 0, finance: 1, health: 2, family: 3,
                            social: 4, growth: 5, leisure: 6, environment: 7
                        })[key] === hoveredArea) + '"]');
                        if (slider) {
                            slider.focus();
                            slider.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                };
            });
        }
    };

    /**
     * Dibuja radar premium con animaciones y efectos.
     */
    function drawPremiumRadar(canvas, labels, data, animate) {
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 60;
        const numPoints = data.length;

        // Colores por área (gradiente de colores Jaraba).
        const areaColors = [
            '#FF8C42', // Trabajo - Naranja Impulso
            '#10B981', // Finanzas - Verde
            '#EF4444', // Salud - Rojo
            '#EC4899', // Familia - Rosa
            '#8B5CF6', // Social - Púrpura
            '#00A9A5', // Desarrollo - Verde Innovación
            '#F59E0B', // Ocio - Ámbar
            '#233D63'  // Entorno - Azul Corporativo
        ];

        function draw(progress) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Fondo gradiente radial.
            const bgGrad = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius + 50);
            bgGrad.addColorStop(0, '#FFFFFF');
            bgGrad.addColorStop(1, '#F8FAFC');
            ctx.fillStyle = bgGrad;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Dibujar grid con gradiente.
            for (let level = 2; level <= 10; level += 2) {
                ctx.strokeStyle = level === 10 ? '#CBD5E1' : '#E5E7EB';
                ctx.lineWidth = level === 10 ? 2 : 1;
                ctx.beginPath();
                for (let j = 0; j <= numPoints; j++) {
                    const angle = (Math.PI * 2 / numPoints) * j - Math.PI / 2;
                    const r = (level / 10) * radius;
                    const x = centerX + r * Math.cos(angle);
                    const y = centerY + r * Math.sin(angle);
                    if (j === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.stroke();

                // Número de nivel.
                if (level <= 8) {
                    ctx.fillStyle = '#94A3B8';
                    ctx.font = '10px Inter, sans-serif';
                    ctx.textAlign = 'left';
                    ctx.fillText(level.toString(), centerX + 5, centerY - (level / 10) * radius + 4);
                }
            }

            // Dibujar líneas radiales.
            ctx.strokeStyle = '#E5E7EB';
            ctx.lineWidth = 1;
            for (let i = 0; i < numPoints; i++) {
                const angle = (Math.PI * 2 / numPoints) * i - Math.PI / 2;
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.lineTo(centerX + radius * Math.cos(angle), centerY + radius * Math.sin(angle));
                ctx.stroke();
            }

            // Dibujar área de datos con gradiente.
            const areaGrad = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius);
            areaGrad.addColorStop(0, 'rgba(0, 169, 165, 0.4)');
            areaGrad.addColorStop(1, 'rgba(0, 169, 165, 0.15)');

            ctx.fillStyle = areaGrad;
            ctx.strokeStyle = 'rgba(0, 169, 165, 1)';
            ctx.lineWidth = 3;
            ctx.beginPath();

            for (let i = 0; i <= numPoints; i++) {
                const idx = i % numPoints;
                const angle = (Math.PI * 2 / numPoints) * i - Math.PI / 2;
                const value = data[idx] * progress;
                const r = (value / 10) * radius;
                const x = centerX + r * Math.cos(angle);
                const y = centerY + r * Math.sin(angle);
                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            }
            ctx.fill();
            ctx.stroke();

            // Dibujar puntos en cada vértice.
            for (let i = 0; i < numPoints; i++) {
                const angle = (Math.PI * 2 / numPoints) * i - Math.PI / 2;
                const value = data[i] * progress;
                const r = (value / 10) * radius;
                const x = centerX + r * Math.cos(angle);
                const y = centerY + r * Math.sin(angle);
                const isHovered = hoveredArea === i;
                const pointRadius = isHovered ? 12 : 8;

                // Sombra.
                ctx.shadowColor = areaColors[i];
                ctx.shadowBlur = isHovered ? 15 : 8;

                // Punto.
                ctx.beginPath();
                ctx.arc(x, y, pointRadius, 0, Math.PI * 2);
                ctx.fillStyle = areaColors[i];
                ctx.fill();
                ctx.shadowBlur = 0;
                ctx.strokeStyle = '#FFFFFF';
                ctx.lineWidth = 3;
                ctx.stroke();

                // Valor dentro del punto.
                if (progress >= 1) {
                    ctx.fillStyle = '#FFFFFF';
                    ctx.font = 'bold 10px Inter, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(Math.round(data[i]).toString(), x, y);
                }
            }

            // Dibujar labels.
            for (let i = 0; i < numPoints; i++) {
                const angle = (Math.PI * 2 / numPoints) * i - Math.PI / 2;
                const labelRadius = radius + 35;
                const x = centerX + labelRadius * Math.cos(angle);
                const y = centerY + labelRadius * Math.sin(angle);
                const isHovered = hoveredArea === i;

                ctx.fillStyle = isHovered ? areaColors[i] : '#334155';
                ctx.font = isHovered ? 'bold 13px Inter, sans-serif' : '12px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                // Ajustar posición para labels laterales.
                let adjustedX = x;
                let adjustedY = y;
                if (angle > -0.1 && angle < 0.1) adjustedY -= 5; // Top
                if (angle > 3.0 && angle < 3.3) adjustedY += 5; // Bottom

                ctx.fillText(labels[i], adjustedX, adjustedY);
            }

            // Tooltip para área hover.
            if (hoveredArea !== null && progress >= 1) {
                const tooltipW = 120;
                const tooltipH = 50;
                const angle = (Math.PI * 2 / numPoints) * hoveredArea - Math.PI / 2;
                const value = data[hoveredArea];
                const r = (value / 10) * radius;
                const pointX = centerX + r * Math.cos(angle);
                const pointY = centerY + r * Math.sin(angle);

                let tooltipX = pointX - tooltipW / 2;
                let tooltipY = pointY - tooltipH - 20;

                if (tooltipX < 10) tooltipX = 10;
                if (tooltipX + tooltipW > canvas.width - 10) tooltipX = canvas.width - tooltipW - 10;
                if (tooltipY < 10) tooltipY = pointY + 25;

                ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
                ctx.shadowBlur = 10;
                ctx.fillStyle = '#FFFFFF';
                ctx.beginPath();
                ctx.roundRect(tooltipX, tooltipY, tooltipW, tooltipH, 8);
                ctx.fill();
                ctx.shadowBlur = 0;

                ctx.strokeStyle = areaColors[hoveredArea];
                ctx.lineWidth = 2;
                ctx.stroke();

                ctx.fillStyle = '#1E293B';
                ctx.font = 'bold 12px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.fillText(labels[hoveredArea], tooltipX + tooltipW / 2, tooltipY + 8);

                ctx.fillStyle = areaColors[hoveredArea];
                ctx.font = 'bold 16px Inter, sans-serif';
                ctx.fillText(data[hoveredArea] + '/10', tooltipX + tooltipW / 2, tooltipY + 26);
            }
        }

        // Animación.
        if (animate) {
            let start = null;
            const duration = 800;

            function animateFrame(timestamp) {
                if (!start) start = timestamp;
                const elapsed = timestamp - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                draw(eased);

                if (progress < 1) {
                    requestAnimationFrame(animateFrame);
                }
            }

            requestAnimationFrame(animateFrame);
        } else {
            draw(1);
        }
    }

    /**
     * Guarda la evaluación via AJAX.
     */
    function saveAssessment(context, url) {
        const sliders = context.querySelectorAll('.lw-slider');
        const scores = {};

        sliders.forEach(function (slider) {
            scores[slider.dataset.area] = parseInt(slider.value, 10);
        });

        // Guardar también en localStorage para persistencia.
        localStorage.setItem('lifeWheelScores', JSON.stringify(scores));

        if (!url) {
            alert(Drupal.t('Evaluación guardada localmente.'));
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ scores: scores }),
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    alert(Drupal.t('Evaluación guardada correctamente.'));
                } else {
                    alert(data.message || Drupal.t('Error al guardar.'));
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                // Fallback a localStorage.
                alert(Drupal.t('Guardado localmente (sin conexión al servidor).'));
            });
    }

})(Drupal, drupalSettings, once);
