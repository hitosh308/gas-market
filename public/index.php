<?php

declare(strict_types=1);

require __DIR__ . '/../lib/gas_data.php';
require __DIR__ . '/../lib/helpers.php';

$projects = loadGasProjects();

usort($projects, static function (array $a, array $b): int {
    $dateA = strtotime($a['updated_at'] ?? $a['created_at'] ?? '');
    $dateB = strtotime($b['updated_at'] ?? $b['created_at'] ?? '');

    return ($dateB ?: 0) <=> ($dateA ?: 0);
});

$allTags = [];
foreach ($projects as $project) {
    foreach ($project['tags'] ?? [] as $tag) {
        $allTags[$tag] = true;
    }
}
ksort($allTags);
$availableTags = array_keys($allTags);

$searchQuery = trim($_GET['q'] ?? '');
$selectedTag = trim($_GET['tag'] ?? '');

$filteredProjects = array_values(array_filter($projects, static function (array $project) use ($searchQuery, $selectedTag): bool {
    if ($selectedTag !== '' && !in_array($selectedTag, $project['tags'] ?? [], true)) {
        return false;
    }

    if ($searchQuery === '') {
        return true;
    }

    $fields = [
        $project['title'] ?? '',
        $project['summary'] ?? '',
        $project['description'] ?? '',
    ];

    foreach ($project['tags'] ?? [] as $tag) {
        $fields[] = $tag;
    }

    foreach ($fields as $field) {
        if ($field === '') {
            continue;
        }
        if (function_exists('mb_stripos')) {
            if (mb_stripos($field, $searchQuery) !== false) {
                return true;
            }
        } else {
            if (stripos($field, $searchQuery) !== false) {
                return true;
            }
        }
    }

    return false;
}));

$totalCount = count($projects);
$filteredCount = count($filteredProjects);
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Google Apps Script ギャラリー</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
    <div class="container">
        <div>
            <h1 class="site-title">Google Apps Script ギャラリー</h1>
            <p class="site-description">自作したGASプロジェクトをまとめたショーケース。社内展開や再利用のヒントに。</p>
        </div>
    </div>
</header>
<div class="container">
    <section class="hero">
        <h2>業務効率化のアイデア集</h2>
        <p>Google Workspace を活用した自動化・通知・集計系のスクリプトを掲載しています。タグで用途別に絞り込み、詳細ページから構成や連携先を確認できます。</p>
    </section>

    <form class="search-bar" method="get" action="index.php">
        <input type="search" name="q" placeholder="キーワードで検索" value="<?= h($searchQuery); ?>" aria-label="キーワード検索">
        <?php if ($selectedTag !== ''): ?>
            <input type="hidden" name="tag" value="<?= h($selectedTag); ?>">
        <?php endif; ?>
        <button type="submit">検索</button>
    </form>

    <?php if ($availableTags): ?>
        <nav class="tag-filter" aria-label="タグフィルター">
            <a href="index.php" class="<?= $selectedTag === '' ? 'active' : ''; ?>">すべて (<?= $totalCount; ?>)</a>
            <?php foreach ($availableTags as $tag): ?>
                <?php
                $isActive = $selectedTag === $tag;
                $query = http_build_query(array_filter([
                    'q' => $searchQuery !== '' ? $searchQuery : null,
                    'tag' => $tag,
                ]));
                ?>
                <a href="index.php?<?= $query; ?>" class="<?= $isActive ? 'active' : ''; ?>">#<?= h($tag); ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <p><?= $filteredCount; ?> 件 / <?= $totalCount; ?> 件を表示中</p>

    <section class="card-grid" aria-live="polite">
        <?php if ($filteredProjects): ?>
            <?php foreach ($filteredProjects as $project): ?>
                <article class="project-card">
                    <div>
                        <h3><a href="detail.php?id=<?= urlencode($project['id']); ?>"><?= h($project['title']); ?></a></h3>
                        <p><?= h($project['summary']); ?></p>
                    </div>
                    <?php if (!empty($project['tags'])): ?>
                        <div class="tag-list">
                            <?php foreach ($project['tags'] as $tag): ?>
                                <span class="tag">#<?= h($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-footer">
                        <span><?= h(formatDate($project['updated_at'] ?? $project['created_at'] ?? '')); ?> 更新</span>
                        <a class="detail-link" href="detail.php?id=<?= urlencode($project['id']); ?>">詳細を見る</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>条件に一致するGASが見つかりませんでした。</p>
        <?php endif; ?>
    </section>

    <footer style="margin-bottom: 3rem; text-align: center; color: #6c7a89;">
        <small>管理用ページは <a href="admin.php">こちら</a></small>
    </footer>
</div>
</body>
</html>
