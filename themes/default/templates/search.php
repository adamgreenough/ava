<?php $pageTitle = 'Search' . ($searchQuery ? ': ' . $searchQuery : '') . ' - ' . $site['name']; ?>
<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $pageTitle]) ?>

        <div class="container">
            <header class="page-header">
                <h1>Search</h1>
            </header>

            <form class="search-form" action="/search" method="get">
                <input 
                    type="search" 
                    name="q" 
                    class="search-input" 
                    placeholder="Search content..." 
                    value="<?= $ava->e($searchQuery) ?>"
                    autofocus
                >
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <?php if ($searchQuery !== ''): ?>
                <?php $results = $query->get(); ?>
                <?php $total = $query->count(); ?>

                <p class="search-results-info">
                    Found <?= $total ?> result<?= $total !== 1 ? 's' : '' ?> for "<?= $ava->e($searchQuery) ?>"
                </p>

                <?php if (empty($results)): ?>
                    <div class="search-empty">
                        <p>No results found. Try a different search term.</p>
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

                                <div class="meta">
                                    <span><?= $ava->e(ucfirst($entry->type())) ?></span>
                                    <?php if ($entry->date()): ?>
                                        &middot;
                                        <time datetime="<?= $entry->date()->format('c') ?>">
                                            <?= $ava->date($entry->date()) ?>
                                        </time>
                                    <?php endif; ?>
                                </div>

                                <?php if ($entry->excerpt()): ?>
                                    <p class="excerpt"><?= $ava->e($entry->excerpt()) ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?= $ava->pagination($query, '/search?q=' . urlencode($searchQuery)) ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="search-empty">
                    <p>Enter a search term above to find content.</p>
                </div>
            <?php endif; ?>
        </div>

<?= $ava->partial('footer') ?>
