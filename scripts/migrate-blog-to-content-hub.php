<?php

/**
 * @file
 * Migration script: jaraba_blog → jaraba_content_hub.
 *
 * Migrates data from deprecated blog entities (BlogPost, BlogCategory,
 * BlogAuthor) to consolidated Content Hub entities (ContentArticle,
 * ContentCategory, ContentAuthor).
 *
 * Execution:
 *   lando drush scr scripts/migrate-blog-to-content-hub.php
 *
 * Features:
 * - Idempotent: uses slug matching to skip already-migrated entities.
 * - Entity reference cascading: categories and authors migrated first,
 *   then articles reference the new IDs.
 * - Field renaming: meta_title→seo_title, meta_description→seo_description,
 *   published_at→publish_date, category_id→category, author_id→content_author.
 * - Safe defaults for new fields (layout_mode=legacy, ai_generated=FALSE).
 * - Dry-run mode: pass --dry-run as argument to preview without saving.
 * - Detailed logging of each entity migrated or skipped.
 *
 * IMPORTANT: Run AFTER jaraba_content_hub update hooks have added the
 * new fields (tags, is_featured, views_count, schema_type, og_image,
 * scheduled_at, content_author, vertical, answer_capsule, etc.).
 *
 * @see docs/implementacion/2026-02-26_Blog_Clase_Mundial_Plan_Implementacion.md
 */

declare(strict_types=1);

// Dry-run detection.
$dryRun = in_array('--dry-run', $extra ?? []);

if ($dryRun) {
  echo "=== DRY-RUN MODE — No entities will be saved ===\n\n";
}

$entityTypeManager = \Drupal::entityTypeManager();

// Verify required entity types exist.
$requiredTypes = [
  'blog_post' => 'jaraba_blog',
  'blog_category' => 'jaraba_blog',
  'blog_author' => 'jaraba_blog',
  'content_article' => 'jaraba_content_hub',
  'content_category' => 'jaraba_content_hub',
  'content_author' => 'jaraba_content_hub',
];

foreach ($requiredTypes as $entityType => $module) {
  if (!$entityTypeManager->hasDefinition($entityType)) {
    echo "ERROR: Entity type '$entityType' not found. Is '$module' installed?\n";
    exit(1);
  }
}

echo "All required entity types found.\n\n";

// =========================================================================
// Phase 1: Migrate BlogCategory → ContentCategory
// =========================================================================

echo "=== Phase 1: Migrating Categories ===\n";

$blogCategoryStorage = $entityTypeManager->getStorage('blog_category');
$contentCategoryStorage = $entityTypeManager->getStorage('content_category');

$blogCategories = $blogCategoryStorage->loadMultiple();
$categoryIdMap = []; // blog_category_id => content_category_id
$categoriesCreated = 0;
$categoriesSkipped = 0;

foreach ($blogCategories as $blogCategory) {
  $slug = $blogCategory->get('slug')->value ?? '';
  $name = $blogCategory->get('name')->value ?? '';

  if (empty($slug)) {
    echo "  SKIP Category ID={$blogCategory->id()}: no slug.\n";
    $categoriesSkipped++;
    continue;
  }

  // Check if already migrated (match by slug).
  $existing = $contentCategoryStorage->loadByProperties(['slug' => $slug]);
  if (!empty($existing)) {
    $existingEntity = reset($existing);
    $categoryIdMap[(int) $blogCategory->id()] = (int) $existingEntity->id();
    echo "  EXISTS Category '$name' (slug=$slug) → ContentCategory ID={$existingEntity->id()}\n";
    $categoriesSkipped++;
    continue;
  }

  $values = [
    'name' => $name,
    'slug' => $slug,
    'description' => $blogCategory->get('description')->value ?? '',
    'icon' => $blogCategory->get('icon')->value ?? '',
    'color' => $blogCategory->get('color')->value ?? '',
    'weight' => (int) ($blogCategory->get('weight')->value ?? 0),
    'is_active' => (bool) ($blogCategory->get('is_active')->value ?? TRUE),
    'posts_count' => (int) ($blogCategory->get('posts_count')->value ?? 0),
    'meta_title' => $blogCategory->get('meta_title')->value ?? '',
    'meta_description' => $blogCategory->get('meta_description')->value ?? '',
    'tenant_id' => $blogCategory->get('tenant_id')->target_id ?? NULL,
  ];

  if (!$dryRun) {
    $contentCategory = $contentCategoryStorage->create($values);
    $contentCategory->save();
    $categoryIdMap[(int) $blogCategory->id()] = (int) $contentCategory->id();
    echo "  OK Category '$name' → ContentCategory ID={$contentCategory->id()}\n";
  }
  else {
    echo "  [DRY] Would create ContentCategory '$name' (slug=$slug)\n";
  }
  $categoriesCreated++;
}

// Resolve parent_id references after all categories are created.
if (!$dryRun) {
  foreach ($blogCategories as $blogCategory) {
    $parentId = $blogCategory->get('parent_id')->target_id ?? NULL;
    if ($parentId && isset($categoryIdMap[(int) $parentId])) {
      $newCategoryId = $categoryIdMap[(int) $blogCategory->id()] ?? NULL;
      if ($newCategoryId) {
        $contentCategory = $contentCategoryStorage->load($newCategoryId);
        if ($contentCategory && $contentCategory->hasField('parent')) {
          $contentCategory->set('parent', $categoryIdMap[(int) $parentId]);
          $contentCategory->save();
          echo "  LINK Category ID=$newCategoryId → parent={$categoryIdMap[(int) $parentId]}\n";
        }
      }
    }
  }
}

echo "  Categories: $categoriesCreated created, $categoriesSkipped skipped.\n\n";

// =========================================================================
// Phase 2: Migrate BlogAuthor → ContentAuthor
// =========================================================================

echo "=== Phase 2: Migrating Authors ===\n";

$blogAuthorStorage = $entityTypeManager->getStorage('blog_author');
$contentAuthorStorage = $entityTypeManager->getStorage('content_author');

$blogAuthors = $blogAuthorStorage->loadMultiple();
$authorIdMap = []; // blog_author_id => content_author_id
$authorsCreated = 0;
$authorsSkipped = 0;

foreach ($blogAuthors as $blogAuthor) {
  $slug = $blogAuthor->get('slug')->value ?? '';
  $displayName = $blogAuthor->get('display_name')->value ?? '';

  if (empty($slug)) {
    echo "  SKIP Author ID={$blogAuthor->id()}: no slug.\n";
    $authorsSkipped++;
    continue;
  }

  // Check if already migrated (match by slug).
  $existing = $contentAuthorStorage->loadByProperties(['slug' => $slug]);
  if (!empty($existing)) {
    $existingEntity = reset($existing);
    $authorIdMap[(int) $blogAuthor->id()] = (int) $existingEntity->id();
    echo "  EXISTS Author '$displayName' (slug=$slug) → ContentAuthor ID={$existingEntity->id()}\n";
    $authorsSkipped++;
    continue;
  }

  $values = [
    'display_name' => $displayName,
    'slug' => $slug,
    'bio' => $blogAuthor->get('bio')->value ?? '',
    'user_id' => $blogAuthor->get('user_id')->target_id ?? NULL,
    'avatar' => $blogAuthor->get('avatar')->target_id ?? NULL,
    'social_twitter' => $blogAuthor->get('social_twitter')->value ?? '',
    'social_linkedin' => $blogAuthor->get('social_linkedin')->value ?? '',
    'social_website' => $blogAuthor->get('social_website')->value ?? '',
    'is_active' => (bool) ($blogAuthor->get('is_active')->value ?? TRUE),
    'posts_count' => (int) ($blogAuthor->get('posts_count')->value ?? 0),
    'tenant_id' => $blogAuthor->get('tenant_id')->target_id ?? NULL,
  ];

  if (!$dryRun) {
    $contentAuthor = $contentAuthorStorage->create($values);
    $contentAuthor->save();
    $authorIdMap[(int) $blogAuthor->id()] = (int) $contentAuthor->id();
    echo "  OK Author '$displayName' → ContentAuthor ID={$contentAuthor->id()}\n";
  }
  else {
    echo "  [DRY] Would create ContentAuthor '$displayName' (slug=$slug)\n";
  }
  $authorsCreated++;
}

echo "  Authors: $authorsCreated created, $authorsSkipped skipped.\n\n";

// =========================================================================
// Phase 3: Migrate BlogPost → ContentArticle
// =========================================================================

echo "=== Phase 3: Migrating Articles ===\n";

$blogPostStorage = $entityTypeManager->getStorage('blog_post');
$contentArticleStorage = $entityTypeManager->getStorage('content_article');

$blogPosts = $blogPostStorage->loadMultiple();
$articlesCreated = 0;
$articlesSkipped = 0;

foreach ($blogPosts as $blogPost) {
  $slug = $blogPost->get('slug')->value ?? '';
  $title = $blogPost->get('title')->value ?? '';

  if (empty($slug)) {
    echo "  SKIP Post ID={$blogPost->id()}: no slug.\n";
    $articlesSkipped++;
    continue;
  }

  // Check if already migrated (match by slug).
  $existing = $contentArticleStorage->loadByProperties(['slug' => $slug]);
  if (!empty($existing)) {
    $existingArticle = reset($existing);
    echo "  EXISTS Article '$title' (slug=$slug) → ContentArticle ID={$existingArticle->id()}\n";
    $articlesSkipped++;
    continue;
  }

  // Resolve category reference.
  $blogCategoryId = $blogPost->get('category_id')->target_id ?? NULL;
  $contentCategoryId = NULL;
  if ($blogCategoryId && isset($categoryIdMap[(int) $blogCategoryId])) {
    $contentCategoryId = $categoryIdMap[(int) $blogCategoryId];
  }

  // Resolve author reference.
  $blogAuthorId = $blogPost->get('author_id')->target_id ?? NULL;
  $contentAuthorId = NULL;
  if ($blogAuthorId && isset($authorIdMap[(int) $blogAuthorId])) {
    $contentAuthorId = $authorIdMap[(int) $blogAuthorId];
  }

  // Map status values (identical mapping, but verify).
  $status = $blogPost->get('status')->value ?? 'draft';
  $validStatuses = ['draft', 'review', 'scheduled', 'published', 'archived'];
  if (!in_array($status, $validStatuses, TRUE)) {
    $status = 'draft';
  }

  $values = [
    'title' => $title,
    'slug' => $slug,
    'excerpt' => $blogPost->get('excerpt')->value ?? '',
    'body' => [
      'value' => $blogPost->get('body')->value ?? '',
      'format' => $blogPost->get('body')->format ?? 'full_html',
    ],
    'status' => $status,
    'category' => $contentCategoryId,
    'content_author' => $contentAuthorId,
    'author' => $blogPost->get('uid')->target_id ?? 1,
    'tenant_id' => $blogPost->get('tenant_id')->target_id ?? NULL,
    // Field renames.
    'seo_title' => $blogPost->get('meta_title')->value ?? '',
    'seo_description' => $blogPost->get('meta_description')->value ?? '',
    'publish_date' => $blogPost->get('published_at')->value ?? NULL,
    // Direct mappings.
    'tags' => $blogPost->get('tags')->value ?? '',
    'featured_image' => $blogPost->get('featured_image')->target_id ?? NULL,
    'featured_image_alt' => $blogPost->get('featured_image_alt')->value ?? '',
    'is_featured' => (bool) ($blogPost->get('is_featured')->value ?? FALSE),
    'reading_time' => (int) ($blogPost->get('reading_time')->value ?? 0),
    'views_count' => (int) ($blogPost->get('views_count')->value ?? 0),
    'schema_type' => $blogPost->get('schema_type')->value ?? 'BlogPosting',
    'og_image' => $blogPost->get('og_image')->target_id ?? NULL,
    'scheduled_at' => $blogPost->get('scheduled_at')->value ?? NULL,
    // New fields — safe defaults for migrated content.
    'layout_mode' => 'legacy',
    'ai_generated' => FALSE,
    'canvas_data' => '{}',
    'rendered_html' => '',
    'answer_capsule' => '',
    'engagement_score' => 0,
  ];

  // Remove NULL values to let entity defaults handle them.
  $values = array_filter($values, function ($v) {
    return $v !== NULL;
  });

  if (!$dryRun) {
    try {
      $contentArticle = $contentArticleStorage->create($values);
      $contentArticle->save();
      echo "  OK Article '$title' → ContentArticle ID={$contentArticle->id()}\n";
      $articlesCreated++;
    }
    catch (\Exception $e) {
      echo "  ERROR Article '$title': " . $e->getMessage() . "\n";
      $articlesSkipped++;
    }
  }
  else {
    echo "  [DRY] Would create ContentArticle '$title' (slug=$slug)\n";
    $articlesCreated++;
  }
}

echo "  Articles: $articlesCreated created, $articlesSkipped skipped.\n\n";

// =========================================================================
// Summary
// =========================================================================

echo "=== Migration Summary ===\n";
echo "  Categories: $categoriesCreated created, $categoriesSkipped skipped\n";
echo "  Authors:    $authorsCreated created, $authorsSkipped skipped\n";
echo "  Articles:   $articlesCreated created, $articlesSkipped skipped\n";

if ($dryRun) {
  echo "\n[DRY-RUN] No changes were made. Remove --dry-run to execute.\n";
}
else {
  echo "\nMigration complete. Run 'lando drush cr' to clear caches.\n";
  echo "Verify at: /blog (Content Hub routes should now serve migrated content).\n";
}

echo "\nNext steps:\n";
echo "  1. Verify migrated content at /blog\n";
echo "  2. Disable jaraba_blog: lando drush pm:uninstall jaraba_blog\n";
echo "  3. Remove from core.extension.yml\n";
echo "  4. Run: lando drush cr\n";
