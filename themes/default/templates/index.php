<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $site['name'], 'pageDescription' => 'Welcome to ' . $site['name']]) ?>

        <div class="container">
            <?php if (isset($content)): ?>
                <article class="entry">
                    <header class="entry-header">
                        <h1><?= $ava->e($content->title()) ?></h1>
                        <?php if ($content->date()): ?>
                            <div class="entry-meta">
                                <time datetime="<?= $content->date()->format('c') ?>">
                                    <?= $ava->date($content->date()) ?>
                                </time>
                            </div>
                        <?php endif; ?>
                    </header>

                    <div class="entry-content">
                        <?= $ava->body($content) ?>
                    </div>
                </article>
            <?php elseif (isset($query)): ?>
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
                                <?php if ($entry->excerpt()): ?>
                                    <p class="excerpt"><?= $ava->e($entry->excerpt()) ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?= $ava->pagination($query) ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="page-header">
                    <h1>Welcome to <?= $ava->e($site['name']) ?></h1>
                    <p class="subtitle">A site powered by Ava CMS</p>
                </div>
            <?php endif; ?>
        </div>

<?= $ava->partial('footer') ?>
