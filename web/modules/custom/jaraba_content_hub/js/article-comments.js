/**
 * @file
 * Article comments widget.
 *
 * Loads approved comments from the REST API and renders an inline
 * threaded comment list with a new-comment form for authenticated users.
 *
 * Reads article ID from [data-article-id] on the article element.
 * ROUTE-LANGPREFIX-001: Uses Drupal.url() for all fetch calls.
 */

(function (Drupal, once) {
  'use strict';

  var csrfToken = null;

  /**
   * Get CSRF token (cached).
   */
  async function getCsrfToken() {
    if (csrfToken) {
      return csrfToken;
    }
    var response = await fetch(Drupal.url('session/token'));
    csrfToken = await response.text();
    return csrfToken;
  }

  /**
   * Escape HTML to prevent XSS.
   */
  function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
  }

  /**
   * Format ISO date to human-readable.
   */
  function formatDate(iso) {
    try {
      var d = new Date(iso);
      return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
    }
    catch (e) {
      return '';
    }
  }

  /**
   * Render a single comment as HTML.
   */
  function renderComment(comment, depth) {
    var maxDepth = 3;
    var depthClass = depth > 0 ? ' article-comments__item--reply' : '';
    var html = '<div class="article-comments__item' + depthClass + '" data-comment-id="' + comment.id + '">';
    html += '<div class="article-comments__avatar">';
    html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
    html += '</div>';
    html += '<div class="article-comments__content">';
    html += '<div class="article-comments__header">';
    html += '<strong class="article-comments__author">' + escapeHtml(comment.author_name) + '</strong>';
    html += '<time class="article-comments__date">' + formatDate(comment.created) + '</time>';
    html += '</div>';
    html += '<p class="article-comments__body">' + escapeHtml(comment.body) + '</p>';
    html += '<div class="article-comments__actions">';
    html += '<button type="button" class="article-comments__helpful-btn" data-helpful-id="' + comment.id + '">';
    html += '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>';
    html += ' <span class="article-comments__helpful-count">' + (comment.helpful_count || 0) + '</span>';
    html += '</button>';
    if (depth < maxDepth) {
      html += '<button type="button" class="article-comments__reply-btn" data-reply-to="' + comment.id + '">';
      html += Drupal.t('Responder');
      html += '</button>';
    }
    html += '</div>';
    html += '</div>';
    html += '</div>';

    // Render children (threading).
    if (comment.children && comment.children.length > 0) {
      for (var i = 0; i < comment.children.length; i++) {
        html += renderComment(comment.children[i], depth + 1);
      }
    }

    return html;
  }

  /**
   * Article comments behavior.
   */
  Drupal.behaviors.articleComments = {
    attach: function (context) {
      once('article-comments', '[data-article-comments]', context).forEach(function (container) {
        var articleId = container.dataset.articleId;
        if (!articleId) {
          return;
        }

        var listEl = container.querySelector('[data-comments-list]');
        var countEl = container.querySelector('[data-comments-count]');
        var formEl = container.querySelector('[data-comment-form]');
        var messagesEl = container.querySelector('[data-comment-messages]');

        // Load comments.
        loadComments(articleId, listEl, countEl);

        // Form submit handler.
        if (formEl) {
          formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            submitComment(articleId, formEl, listEl, countEl, messagesEl);
          });
        }

        // Delegated event handlers for helpful and reply buttons.
        container.addEventListener('click', function (e) {
          var helpfulBtn = e.target.closest('[data-helpful-id]');
          if (helpfulBtn) {
            handleHelpful(helpfulBtn);
            return;
          }

          var replyBtn = e.target.closest('[data-reply-to]');
          if (replyBtn) {
            handleReply(replyBtn, formEl);
          }
        });
      });
    }
  };

  /**
   * Fetch and render approved comments.
   */
  async function loadComments(articleId, listEl, countEl) {
    if (!listEl) {
      return;
    }

    try {
      var response = await fetch(Drupal.url('api/v1/content/articles/' + articleId + '/comments'));
      if (!response.ok) {
        return;
      }
      var result = await response.json();
      var comments = result.data || [];
      var total = result.meta ? result.meta.total : comments.length;

      if (countEl) {
        countEl.textContent = total;
      }

      if (comments.length === 0) {
        listEl.innerHTML = '<p class="article-comments__empty">' +
          Drupal.t('Se el primero en comentar.') + '</p>';
        return;
      }

      var html = '';
      for (var i = 0; i < comments.length; i++) {
        html += renderComment(comments[i], 0);
      }
      listEl.innerHTML = html;
    }
    catch (err) {
      listEl.innerHTML = '<p class="article-comments__error">' +
        Drupal.t('No se pudieron cargar los comentarios.') + '</p>';
    }
  }

  /**
   * Submit new comment via API.
   */
  async function submitComment(articleId, formEl, listEl, countEl, messagesEl) {
    if (messagesEl) {
      messagesEl.innerHTML = '';
    }

    var bodyInput = formEl.querySelector('[name="comment_body"]');
    var body = (bodyInput ? bodyInput.value : '').trim();
    var parentInput = formEl.querySelector('[name="parent_id"]');
    var parentId = parentInput ? parentInput.value : '';

    if (body.length < 3) {
      showMessage(messagesEl, Drupal.t('El comentario debe tener al menos 3 caracteres.'), 'error');
      return;
    }

    var submitBtn = formEl.querySelector('[data-comment-submit-btn]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = Drupal.t('Enviando...');
    }

    try {
      var token = await getCsrfToken();
      var payload = { body: body };
      if (parentId) {
        payload.parent_id = parseInt(parentId, 10);
      }

      var response = await fetch(Drupal.url('api/v1/content/articles/' + articleId + '/comments'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': token
        },
        body: JSON.stringify(payload)
      });

      var data = await response.json();

      if (response.ok) {
        showMessage(messagesEl, Drupal.t('Comentario enviado. Sera visible tras la moderacion.'), 'success');
        bodyInput.value = '';
        // Clear reply target.
        if (parentInput) {
          parentInput.value = '';
        }
        var replyIndicator = formEl.querySelector('[data-reply-indicator]');
        if (replyIndicator) {
          replyIndicator.style.display = 'none';
        }
      }
      else {
        showMessage(messagesEl, data.error || Drupal.t('No se pudo enviar el comentario.'), 'error');
      }
    }
    catch (err) {
      showMessage(messagesEl, Drupal.t('Error de conexion. Intentalo de nuevo.'), 'error');
    }

    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = Drupal.t('Comentar');
    }
  }

  /**
   * Handle helpful vote.
   */
  async function handleHelpful(btn) {
    var commentId = btn.dataset.helpfulId;
    btn.disabled = true;

    try {
      var token = await getCsrfToken();
      var response = await fetch(Drupal.url('api/v1/content/comments/' + commentId + '/helpful'), {
        method: 'POST',
        headers: { 'X-CSRF-Token': token }
      });

      if (response.ok) {
        var data = await response.json();
        var countSpan = btn.querySelector('.article-comments__helpful-count');
        if (countSpan) {
          countSpan.textContent = data.helpful_count;
        }
        btn.classList.add('article-comments__helpful-btn--voted');
      }
    }
    catch (err) {
      // Silent fail for helpful votes.
    }

    btn.disabled = false;
  }

  /**
   * Handle reply button — sets parent_id and scrolls to form.
   */
  function handleReply(btn, formEl) {
    if (!formEl) {
      return;
    }

    var parentId = btn.dataset.replyTo;
    var parentInput = formEl.querySelector('[name="parent_id"]');
    if (parentInput) {
      parentInput.value = parentId;
    }

    // Show reply indicator.
    var replyIndicator = formEl.querySelector('[data-reply-indicator]');
    if (replyIndicator) {
      replyIndicator.style.display = 'flex';
      var authorEl = btn.closest('.article-comments__item').querySelector('.article-comments__author');
      var replyName = replyIndicator.querySelector('[data-reply-name]');
      if (replyName && authorEl) {
        replyName.textContent = authorEl.textContent;
      }
    }

    // Cancel reply button.
    var cancelBtn = formEl.querySelector('[data-cancel-reply]');
    if (cancelBtn) {
      cancelBtn.onclick = function () {
        if (parentInput) {
          parentInput.value = '';
        }
        if (replyIndicator) {
          replyIndicator.style.display = 'none';
        }
      };
    }

    formEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    var bodyInput = formEl.querySelector('[name="comment_body"]');
    if (bodyInput) {
      bodyInput.focus();
    }
  }

  /**
   * Show feedback message.
   */
  function showMessage(container, text, type) {
    if (!container) {
      return;
    }
    var div = document.createElement('div');
    div.className = 'article-comments__message article-comments__message--' + type;
    div.setAttribute('role', type === 'error' ? 'alert' : 'status');
    div.textContent = text;
    container.appendChild(div);
  }

})(Drupal, once);
