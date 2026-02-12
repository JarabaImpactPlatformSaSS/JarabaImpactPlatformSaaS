/**
 * @file
 * Funding Calendar — Simple month calendar with deadline markers.
 *
 * Renders a calendar grid with month navigation, deadline markers loaded
 * via API, today highlight, and upcoming deadlines list.
 *
 * Funding Intelligence module.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.fundingCalendar = {
    attach: function (context) {
      once('funding-calendar', '#funding-calendar', context).forEach(function (calendarEl) {
        var grid = calendarEl.querySelector('#funding-calendar-grid');
        var monthTitle = calendarEl.querySelector('#funding-calendar-month-title');
        var prevBtn = calendarEl.querySelector('#funding-calendar-prev');
        var nextBtn = calendarEl.querySelector('#funding-calendar-next');
        var upcomingList = calendarEl.querySelector('#funding-calendar-upcoming-list');

        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth();
        var deadlines = {};

        var monthNames = [
          Drupal.t('Enero'), Drupal.t('Febrero'), Drupal.t('Marzo'),
          Drupal.t('Abril'), Drupal.t('Mayo'), Drupal.t('Junio'),
          Drupal.t('Julio'), Drupal.t('Agosto'), Drupal.t('Septiembre'),
          Drupal.t('Octubre'), Drupal.t('Noviembre'), Drupal.t('Diciembre')
        ];

        // ========================================
        // Month navigation.
        // ========================================
        if (prevBtn) {
          prevBtn.addEventListener('click', function () {
            currentMonth--;
            if (currentMonth < 0) {
              currentMonth = 11;
              currentYear--;
            }
            renderCalendar();
            loadDeadlines();
          });
        }

        if (nextBtn) {
          nextBtn.addEventListener('click', function () {
            currentMonth++;
            if (currentMonth > 11) {
              currentMonth = 0;
              currentYear++;
            }
            renderCalendar();
            loadDeadlines();
          });
        }

        // ========================================
        // Calendar rendering.
        // ========================================

        /**
         * Render the calendar grid for the current month.
         */
        function renderCalendar() {
          if (!grid || !monthTitle) {
            return;
          }

          monthTitle.textContent = monthNames[currentMonth] + ' ' + currentYear;

          var firstDay = new Date(currentYear, currentMonth, 1);
          var lastDay = new Date(currentYear, currentMonth + 1, 0);
          var startDay = firstDay.getDay();
          // Adjust for Monday start (0 = Monday, 6 = Sunday).
          startDay = startDay === 0 ? 6 : startDay - 1;

          var totalDays = lastDay.getDate();
          var today = new Date();
          var todayDate = today.getDate();
          var todayMonth = today.getMonth();
          var todayYear = today.getFullYear();

          var html = '';

          // Empty cells before first day.
          for (var i = 0; i < startDay; i++) {
            html += '<div class="funding-calendar__day funding-calendar__day--empty"></div>';
          }

          // Day cells.
          for (var day = 1; day <= totalDays; day++) {
            var dateStr = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            var isToday = (day === todayDate && currentMonth === todayMonth && currentYear === todayYear);
            var hasDeadline = deadlines[dateStr] && deadlines[dateStr].length > 0;

            var classes = 'funding-calendar__day';
            if (isToday) {
              classes += ' funding-calendar__day--today';
            }
            if (hasDeadline) {
              classes += ' funding-calendar__day--has-deadline';
            }

            html += '<div class="' + classes + '" data-date="' + dateStr + '">';
            html += '<span class="funding-calendar__day-number">' + day + '</span>';

            if (hasDeadline) {
              html += '<div class="funding-calendar__deadline-markers">';
              deadlines[dateStr].forEach(function (dl) {
                html += '<span class="funding-calendar__deadline" title="' + Drupal.checkPlain(dl.title || '') + '"></span>';
              });
              html += '</div>';
            }

            html += '</div>';
          }

          grid.innerHTML = html;

          // Click on deadline day to show detail.
          grid.querySelectorAll('.funding-calendar__day--has-deadline').forEach(function (dayEl) {
            dayEl.addEventListener('click', function () {
              var date = dayEl.getAttribute('data-date');
              showDayDeadlines(date);
            });
          });
        }

        /**
         * Show deadlines for a specific day.
         *
         * @param {string} dateStr
         *   Date string YYYY-MM-DD.
         */
        function showDayDeadlines(dateStr) {
          if (!deadlines[dateStr] || !upcomingList) {
            return;
          }

          var html = '';
          deadlines[dateStr].forEach(function (dl) {
            html += '<li class="funding-calendar__upcoming-item">';
            html += '<span class="funding-calendar__upcoming-date">' + Drupal.checkPlain(dateStr) + '</span>';
            html += '<span class="funding-calendar__upcoming-title">' + Drupal.checkPlain(dl.title || '') + '</span>';
            if (dl.region) {
              html += '<span class="funding-calendar__upcoming-region">' + Drupal.checkPlain(dl.region) + '</span>';
            }
            html += '</li>';
          });

          upcomingList.innerHTML = html;
        }

        // ========================================
        // Load deadlines from API.
        // ========================================

        /**
         * Load deadlines for the current month from the API.
         */
        function loadDeadlines() {
          var fromDate = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-01';
          var lastDay = new Date(currentYear, currentMonth + 1, 0).getDate();
          var toDate = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-' + String(lastDay).padStart(2, '0');

          var baseUrl = (drupalSettings.fundingIntelligence && drupalSettings.fundingIntelligence.apiSearchUrl)
            || '/api/v1/funding/calls';

          fetch(baseUrl + '?deadline_from=' + fromDate + '&deadline_to=' + toDate + '&estado=abierta', {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status);
              }
              return response.json();
            })
            .then(function (data) {
              deadlines = {};
              if (data.success && data.data) {
                data.data.forEach(function (call) {
                  if (call.deadline) {
                    var dateKey = call.deadline.substring(0, 10);
                    if (!deadlines[dateKey]) {
                      deadlines[dateKey] = [];
                    }
                    deadlines[dateKey].push({
                      id: call.id,
                      title: call.title,
                      region: call.region
                    });
                  }
                });
              }
              renderCalendar();
              renderUpcomingDeadlines();
            })
            .catch(function (error) {
              console.warn('Funding Calendar: Failed to load deadlines', error);
              renderCalendar();
            });
        }

        /**
         * Render the upcoming deadlines list.
         */
        function renderUpcomingDeadlines() {
          if (!upcomingList) {
            return;
          }

          var allDeadlines = [];
          Object.keys(deadlines).sort().forEach(function (dateStr) {
            deadlines[dateStr].forEach(function (dl) {
              allDeadlines.push({
                date: dateStr,
                title: dl.title,
                region: dl.region
              });
            });
          });

          if (allDeadlines.length === 0) {
            upcomingList.innerHTML = '<li class="funding-calendar__upcoming-empty">' + Drupal.t('No hay plazos proximos.') + '</li>';
            return;
          }

          var html = '';
          allDeadlines.slice(0, 10).forEach(function (dl) {
            html += '<li class="funding-calendar__upcoming-item">';
            html += '<span class="funding-calendar__upcoming-date">' + Drupal.checkPlain(dl.date) + '</span>';
            html += '<span class="funding-calendar__upcoming-title">' + Drupal.checkPlain(dl.title) + '</span>';
            if (dl.region) {
              html += '<span class="funding-calendar__upcoming-region">' + Drupal.checkPlain(dl.region) + '</span>';
            }
            html += '</li>';
          });

          upcomingList.innerHTML = html;
        }

        // Initial load.
        loadDeadlines();
      });
    }
  };

})(Drupal, drupalSettings, once);
