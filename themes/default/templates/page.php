<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $content->title() . ' - ' . $site['name']]) ?>

        <div class="container">
            <article class="entry">
                <header class="entry-header">
                    <h1><?= $ava->e($content->title()) ?></h1>
                </header>

                <div class="entry-content">
                    <?= $ava->body($content) ?>
                </div>
            </article>
        </div>

<?= $ava->partial('footer') ?>
