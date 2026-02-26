/**
 * @file
 * Related Articles - Dynamic loading via Recommendations API.
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Behavior for loading related articles dynamically.
     */
    Drupal.behaviors.relatedArticles = {
        attach: function (context) {
            once('related-articles', '.related-articles[data-article-id]', context).forEach(function (container) {
                const articleId = container.dataset.articleId;
                const count = parseInt(container.dataset.count || '4', 10);
                const gridContainer = container.querySelector('.related-articles__grid');

                if (!articleId || !gridContainer) {
                    return;
                }

                // Only fetch if we have a loading placeholder
                const loadingElement = gridContainer.querySelector('.related-articles__loading');
                if (!loadingElement) {
                    return; // Already pre-rendered from backend
                }

                // Fetch related articles
                Drupal.relatedArticles.fetchRelated(articleId, count)
                    .then(function (articles) {
                        Drupal.relatedArticles.renderArticles(gridContainer, articles);
                    })
                    .catch(function (error) {
                        Drupal.relatedArticles.renderError(gridContainer);
                    });
            });
        }
    };

    /**
     * Related articles utilities.
     */
    Drupal.relatedArticles = {
        /**
         * Fetch related articles from API.
         */
        fetchRelated: function (articleId, limit) {
            const url = `/api/v1/content/articles/${articleId}/related?limit=${limit}`;

            return fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (data.success && data.data) {
                        return data.data;
                    }
                    return [];
                });
        },

        /**
         * Render articles into the container.
         */
        renderArticles: function (container, articles) {
            if (!articles || articles.length === 0) {
                container.innerHTML = `
          <div class="related-articles__empty">
            <p>${Drupal.t('No related articles found.')}</p>
          </div>
        `;
                return;
            }

            const html = articles.map(function (article) {
                return Drupal.relatedArticles.renderArticleCard(article);
            }).join('');

            container.innerHTML = html;
        },

        /**
         * Render a single article card.
         */
        renderArticleCard: function (article) {
            const imageHtml = article.featured_image
                ? `<a href="${article.url}" class="article-card__image-link" aria-label="${Drupal.relatedArticles.escapeHtml(article.title)}">
             <img src="${article.featured_image}" alt="${Drupal.relatedArticles.escapeHtml(article.title)}" loading="lazy" class="article-card__image">
           </a>`
                : '';

            const categoryHtml = article.category
                ? `<span class="article-card__category">${Drupal.relatedArticles.escapeHtml(article.category)}</span>`
                : '';

            const readingTimeHtml = article.reading_time
                ? `<span class="article-card__reading-time">
             <svg class="article-card__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <circle cx="12" cy="12" r="10"></circle>
               <polyline points="12 6 12 12 16 14"></polyline>
             </svg>
             ${article.reading_time} ${Drupal.t('min')}
           </span>`
                : '';

            // Usar URL canónica del API (respeta prefijo de idioma).
            // Fallback a article.slug solo si la API no devuelve url.
            const url = article.url || (article.slug ? `/blog/${article.slug}` : `/blog/article/${article.id}`);

            return `
        <article class="article-card article-card--standard" data-article-id="${article.id}">
          ${imageHtml}
          <div class="article-card__content">
            ${categoryHtml}
            <h3 class="article-card__title">
              <a href="${url}">${Drupal.relatedArticles.escapeHtml(article.title)}</a>
            </h3>
            ${article.excerpt ? `<p class="article-card__excerpt">${Drupal.relatedArticles.escapeHtml(article.excerpt)}</p>` : ''}
            <div class="article-card__meta">
              ${readingTimeHtml}
            </div>
          </div>
        </article>
      `;
        },

        /**
         * Render error state.
         */
        renderError: function (container) {
            container.innerHTML = `
        <div class="related-articles__error">
          <p>${Drupal.t('Unable to load recommendations.')}</p>
        </div>
      `;
        },

        /**
         * Escape HTML entities.
         */
        escapeHtml: function (text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

})(Drupal, drupalSettings, once);
