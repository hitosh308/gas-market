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
$tagCount = count($availableTags);
$latestProject = $projects[0] ?? null;
$latestUpdated = $latestProject ? formatDate($latestProject['updated_at'] ?? $latestProject['created_at'] ?? '') : '';
$featureCount = array_reduce($projects, static function (int $carry, array $item): int {
    return $carry + count($item['features'] ?? []);
}, 0);
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
<header class="site-header">
    <div class="orb orb--one" aria-hidden="true"></div>
    <div class="orb orb--two" aria-hidden="true"></div>
    <div class="container header-inner">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">G</span>
            <div>
                <h1 class="site-title">Google Apps Script ギャラリー</h1>
                <p class="site-description">社内展開や再利用のヒントになる自動化アイデアをワンストップで。</p>
            </div>
        </div>
        <div class="header-links">
            <a class="header-link" href="admin.php">管理ログイン</a>
        </div>
    </div>
</header>
<main>
    <div class="container">
        <section class="hero">
            <div class="hero-content">
                <p class="hero-badge">Automation × Collaboration</p>
                <h2>業務をスマートに進化させる<br>GASテンプレート集</h2>
                <p class="hero-text">Google Workspace を活用した自動化・通知・集計系のスクリプトを厳選。タグで用途別に絞り込み、詳細ページで構成や連携先をチェックできます。</p>
                <div class="hero-actions">
                    <a class="button primary" href="#projects">プロジェクトを探す</a>
                    <a class="button ghost" href="https://script.google.com/" target="_blank" rel="noopener">GAS 公式サイト</a>
                </div>
                <dl class="hero-metrics">
                    <div>
                        <dt>掲載プロジェクト</dt>
                        <dd><?= $totalCount; ?></dd>
                    </div>
                    <div>
                        <dt>登録タグ</dt>
                        <dd><?= $tagCount; ?></dd>
                    </div>
                    <div>
                        <dt>紹介している機能</dt>
                        <dd><?= $featureCount; ?></dd>
                    </div>
                    <?php if ($latestUpdated !== ''): ?>
                        <div>
                            <dt>最新更新</dt>
                            <dd><?= h($latestUpdated); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
            <div class="hero-visual" aria-hidden="true">
                <div class="hero-visual__glow"></div>
                <img src="assets/images/hero-illustration.svg" alt="GASのダッシュボードを表現したイラスト">
            </div>
        </section>

        <section class="filters" aria-label="検索とフィルター">
            <form class="search-bar" method="get" action="index.php">
                <div class="search-field">
                    <input type="search" name="q" placeholder="キーワードで検索" value="<?= h($searchQuery); ?>" aria-label="キーワード検索">
                </div>
                <?php if ($selectedTag !== ''): ?>
                    <input type="hidden" name="tag" value="<?= h($selectedTag); ?>">
                <?php endif; ?>
                <button type="submit">検索</button>
            </form>
            <div class="filter-meta">
                <p><?= $filteredCount; ?> 件 / <?= $totalCount; ?> 件を表示中</p>
                <?php if ($selectedTag !== ''): ?>
                    <a class="reset-filter" href="index.php">絞り込みを解除</a>
                <?php endif; ?>
            </div>
            <?php if ($availableTags): ?>
                <nav class="tag-filter" aria-label="タグフィルター">
                    <a href="index.php" class="tag-pill <?= $selectedTag === '' ? 'active' : ''; ?>">すべて (<?= $totalCount; ?>)</a>
                    <?php foreach ($availableTags as $tag): ?>
                        <?php
                        $isActive = $selectedTag === $tag;
                        $query = http_build_query(array_filter([
                            'q' => $searchQuery !== '' ? $searchQuery : null,
                            'tag' => $tag,
                        ]));
                        ?>
                        <a href="index.php?<?= $query; ?>" class="tag-pill <?= $isActive ? 'active' : ''; ?>">#<?= h($tag); ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </section>

        <section id="projects" class="card-grid" aria-live="polite">
            <?php if ($filteredProjects): ?>
                <?php foreach ($filteredProjects as $project): ?>
                    <?php
                    $thumbnail = trim((string) ($project['thumbnail'] ?? ''));
                    $detailUrl = 'detail.php?id=' . urlencode($project['id']);
                    ?>
                    <article class="project-card">
                        <figure class="project-card__media">
                            <?php if ($thumbnail !== ''): ?>
                                <img src="<?= h($thumbnail); ?>" alt="<?= h(($project['title'] ?? 'GAS') . 'のイメージ'); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="project-card__placeholder" aria-hidden="true">GAS</div>
                            <?php endif; ?>
                        </figure>
                        <div class="project-card__body">
                            <p class="project-card__badge"><?= h(formatDate($project['updated_at'] ?? $project['created_at'] ?? '')); ?> 更新</p>
                            <h3><a href="<?= $detailUrl; ?>"><?= h($project['title']); ?></a></h3>
                            <p><?= h($project['summary']); ?></p>
                        </div>
                        <?php if (!empty($project['tags'])): ?>
                            <ul class="project-card__tags">
                                <?php foreach ($project['tags'] as $tag): ?>
                                    <li>#<?= h($tag); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <div class="project-card__footer">
                            <span class="project-card__meta">ID: <?= h($project['id']); ?></span>
                            <a class="detail-link" href="<?= $detailUrl; ?>">詳しく見る</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">条件に一致するGASが見つかりませんでした。キーワードやタグを変えてみてください。</p>
            <?php endif; ?>
        </section>
    </div>
</main>
<footer class="site-footer">
    <div class="container">
        <small>管理用ページは <a href="admin.php">こちら</a></small>
    </div>
</footer>
</body>
</html>
