<?php
$contentType = $route->getParam('content_type', 'Archive');
$pageTitle = ucfirst($contentType) . ' - ' . $site['name'];
?>
<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $pageTitle]) ?>

        <div class="container">
            <header class="page-header">
                <h1><?= $ava->e(ucfirst($contentType)) ?></h1>
            </header>

            <?php $results = $query->get(); ?>

            <?php if (empty($results)): ?>
                <div class="search-empty">
                    <p>No content found.</p>
                </div>
            <?php else: ?>
                <div class="archive-list">
                    <?php foreach ($results as $entry): ?>
                        <article class="archive-item">
                            <h2>
                                <a href="<?= $ava->url($entry->type(), $entry->slug()) ?>">
                                    <?= $ava->e($entry->title()) ?>
                                </a>
                            </h2>

                            <?php if ($entry->date()): ?>
                                <div class="meta">
                                    <time datetime="<?= $entry->date()->format('c') ?>">
                                        <?= $ava->date($entry->date()) ?>
                                    </time>
                                </div>
                            <?php endif; ?>

                            <?php if ($entry->excerpt()): ?>
                                <p class="excerpt"><?= $ava->e($entry->excerpt()) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?= $ava->pagination($query, $request->path()) ?>
            <?php endif; ?>
        </div>

<?= $ava->partial('footer') ?>
