<?= $ava->partial('header', ['request' => $request]) ?>

        <div class="container">
            <article class="entry">
                <header class="entry-header">
                    <h1><?= $ava->e($content->title()) ?></h1>
                    
                    <div class="entry-meta">
                        <?php if ($content->date()): ?>
                            <time datetime="<?= $content->date()->format('c') ?>">
                                <?= $ava->date($content->date()) ?>
                            </time>
                        <?php endif; ?>

                        <?php $categories = $content->terms('category'); ?>
                        <?php if (!empty($categories)): ?>
                            <span>
                                in
                                <?php foreach ($categories as $i => $cat): ?>
                                    <a href="<?= $ava->termUrl('category', $cat) ?>"><?= $ava->e($cat) ?></a><?= $i < count($categories) - 1 ? ', ' : '' ?>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="entry-content">
                    <?= $ava->body($content) ?>
                </div>

                <?php $tags = $content->terms('tag'); ?>
                <?php if (!empty($tags)): ?>
                    <footer class="entry-footer">
                        <div class="entry-tags">
                            <?php foreach ($tags as $tag): ?>
                                <a href="<?= $ava->termUrl('tag', $tag) ?>" class="tag">#<?= $ava->e($tag) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </footer>
                <?php endif; ?>
            </article>
        </div>

<?= $ava->partial('footer') ?>
