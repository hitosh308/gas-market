<?php

declare(strict_types=1);

require __DIR__ . '/../lib/gas_data.php';
require __DIR__ . '/../lib/helpers.php';

$projectId = isset($_GET['id']) ? (string) $_GET['id'] : '';
$projects = loadGasProjects();
$project = $projectId !== '' ? getGasProjectById($projects, $projectId) : null;

if (!$project) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $project ? h($project['title']) . '｜' : ''; ?>Google Apps Script ギャラリー</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header detail-header">
    <div class="orb orb--one" aria-hidden="true"></div>
    <div class="orb orb--two" aria-hidden="true"></div>
    <div class="container header-inner">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">G</span>
            <div>
                <h1 class="site-title">Google Apps Script ギャラリー</h1>
                <p class="site-description"><a href="index.php">一覧に戻る</a></p>
            </div>
        </div>
        <div class="header-links">
            <a class="header-link" href="admin.php">管理ログイン</a>
        </div>
    </div>
</header>
<div class="container">
    <?php if (!$project): ?>
        <main class="project-detail">
            <h1>ページが見つかりません</h1>
            <p>指定されたGASの情報が見つかりませんでした。URLをご確認のうえ、<a href="index.php">一覧ページ</a>からアクセスしてください。</p>
        </main>
    <?php else: ?>
        <?php
        $images = $project['images'] ?? [];
        $primaryImage = '';
        if ($images !== []) {
            $primaryImage = (string) $images[0];
        } elseif (!empty($project['thumbnail'])) {
            $primaryImage = (string) $project['thumbnail'];
        }
        ?>
        <main class="project-detail">
            <section class="detail-hero">
                <div class="detail-hero__media">
                    <?php if ($primaryImage !== ''): ?>
                        <img src="<?= h($primaryImage); ?>" alt="<?= h(($project['title'] ?? 'GAS') . 'のイメージ'); ?>">
                    <?php else: ?>
                        <div class="detail-hero__placeholder" aria-hidden="true">GAS</div>
                    <?php endif; ?>
                </div>
                <div class="detail-hero__content">
                    <p class="hero-badge">最終更新 <?= h(formatDate($project['updated_at'] ?? $project['created_at'] ?? '')); ?></p>
                    <h1><?= h($project['title']); ?></h1>
                    <p class="summary"><?= nl2br(h($project['summary'] ?? '')); ?></p>
                    <div class="detail-meta">
                        <?php if (!empty($project['created_at'])): ?>
                            <span><strong>初回公開日</strong><?= h(formatDate($project['created_at'])); ?></span>
                        <?php endif; ?>
                        <span><strong>ID</strong><?= h($project['id']); ?></span>
                    </div>
                    <?php if (!empty($project['tags'])): ?>
                        <div class="tag-pills">
                            <?php foreach ($project['tags'] as $tag): ?>
                                <span class="tag">#<?= h($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($images !== []): ?>
                <section class="project-gallery" aria-label="イメージギャラリー">
                    <?php foreach ($images as $image): ?>
                        <figure class="project-gallery__item">
                            <img src="<?= h($image); ?>" alt="<?= h(($project['title'] ?? 'GAS') . 'のスクリーンショット'); ?>" loading="lazy">
                        </figure>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <div class="detail-content">
                <?php if (!empty($project['description'])): ?>
                    <section class="detail-panel">
                        <h2>概要</h2>
                        <p><?= nl2br(h($project['description'])); ?></p>
                    </section>
                <?php endif; ?>

                <?php if (!empty($project['features'])): ?>
                    <section class="detail-panel">
                        <h2>主な機能</h2>
                        <ul>
                            <?php foreach ($project['features'] as $feature): ?>
                                <li><?= h($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </div>

            <div class="button-row">
                <?php if (!empty($project['script_url'])): ?>
                    <a class="button primary" href="<?= h($project['script_url']); ?>" target="_blank" rel="noopener noreferrer">スクリプトを開く</a>
                <?php endif; ?>
                <?php if (!empty($project['repository_url'])): ?>
                    <a class="button secondary" href="<?= h($project['repository_url']); ?>" target="_blank" rel="noopener noreferrer">ソースコードを見る</a>
                <?php endif; ?>
                <a class="button ghost" href="index.php">一覧へ戻る</a>
            </div>
        </main>
    <?php endif; ?>

    <footer class="site-footer">
        <div class="container">
            <small>管理用ページは <a href="admin.php">こちら</a></small>
        </div>
    </footer>
</div>
</body>
</html>
