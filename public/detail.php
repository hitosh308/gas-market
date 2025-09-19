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
<header>
    <div class="container">
        <div>
            <h1 class="site-title">Google Apps Script ギャラリー</h1>
            <p class="site-description"><a href="index.php">一覧に戻る</a></p>
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
        <main class="project-detail">
            <h1><?= h($project['title']); ?></h1>
            <p class="summary"><?= nl2br(h($project['summary'] ?? '')); ?></p>

            <div class="meta-info">
                <?php if (!empty($project['updated_at'])): ?>
                    <span>最終更新日: <?= h(formatDate($project['updated_at'])); ?></span>
                <?php endif; ?>
                <?php if (!empty($project['created_at'])): ?>
                    <span>初回公開日: <?= h(formatDate($project['created_at'])); ?></span>
                <?php endif; ?>
                <span>ID: <?= h($project['id']); ?></span>
            </div>

            <?php if (!empty($project['tags'])): ?>
                <div class="tag-pills">
                    <?php foreach ($project['tags'] as $tag): ?>
                        <span class="tag">#<?= h($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($project['description'])): ?>
                <section>
                    <h2>概要</h2>
                    <p><?= nl2br(h($project['description'])); ?></p>
                </section>
            <?php endif; ?>

            <?php if (!empty($project['features'])): ?>
                <section>
                    <h2>主な機能</h2>
                    <ul>
                        <?php foreach ($project['features'] as $feature): ?>
                            <li><?= h($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <div class="button-row">
                <?php if (!empty($project['script_url'])): ?>
                    <a class="button primary" href="<?= h($project['script_url']); ?>" target="_blank" rel="noopener noreferrer">スクリプトを開く</a>
                <?php endif; ?>
                <?php if (!empty($project['repository_url'])): ?>
                    <a class="button secondary" href="<?= h($project['repository_url']); ?>" target="_blank" rel="noopener noreferrer">ソースコードを見る</a>
                <?php endif; ?>
                <a class="button secondary" href="index.php">一覧へ戻る</a>
            </div>
        </main>
    <?php endif; ?>

    <footer style="margin-bottom: 3rem; text-align: center; color: #6c7a89;">
        <small>管理用ページは <a href="admin.php">こちら</a></small>
    </footer>
</div>
</body>
</html>
