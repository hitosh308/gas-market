<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../lib/gas_data.php';
require __DIR__ . '/../lib/helpers.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (session_id() !== '') {
        session_destroy();
    }
    header('Location: admin.php');
    exit;
}

$isLoggedIn = !empty($_SESSION['is_admin']);

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$loginError = '';
$formErrors = [];

if (!$isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_password'])) {
    $password = (string) ($_POST['login_password'] ?? '');
    if (hash_equals(getAdminPassword(), $password)) {
        $_SESSION['is_admin'] = true;
        header('Location: admin.php');
        exit;
    }

    $loginError = 'パスワードが正しくありません。';
}

$projects = $isLoggedIn ? loadGasProjects() : [];
usort($projects, static function (array $a, array $b): int {
    $dateA = strtotime($a['updated_at'] ?? $a['created_at'] ?? '') ?: 0;
    $dateB = strtotime($b['updated_at'] ?? $b['created_at'] ?? '') ?: 0;

    return $dateB <=> $dateA;
});

$editId = $isLoggedIn ? trim((string) ($_GET['id'] ?? '')) : '';
$projectToEdit = ($isLoggedIn && $editId !== '') ? getGasProjectById($projects, $editId) : null;
$currentOriginalId = $projectToEdit['id'] ?? '';

if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    $currentOriginalId = trim((string) ($_POST['original_id'] ?? ''));
    $title = trim((string) ($_POST['title'] ?? ''));
    $summary = trim((string) ($_POST['summary'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $projectIdInput = trim((string) ($_POST['project_id'] ?? ''));
    $updatedAt = trim((string) ($_POST['updated_at'] ?? ''));
    $createdAt = trim((string) ($_POST['created_at'] ?? ''));

    if ($title === '') {
        $formErrors[] = 'タイトルは必須です。';
    }

    if ($summary === '') {
        $formErrors[] = '概要は必須です。';
    }

    $projectId = $projectIdInput !== '' ? sanitizeProjectId($projectIdInput) : generateProjectId($title);

    if ($currentOriginalId === '' && getGasProjectById($projects, $projectId)) {
        $formErrors[] = '指定したIDは既に使用されています。別のIDに変更してください。';
    }

    if ($currentOriginalId !== '' && $projectId !== $currentOriginalId && getGasProjectById($projects, $projectId)) {
        $formErrors[] = '指定したIDは既に使用されています。';
    }

    if ($updatedAt === '') {
        $updatedAt = date('Y-m-d');
    }

    if ($createdAt === '') {
        $createdAt = $currentOriginalId !== '' ? ($projectToEdit['created_at'] ?? $updatedAt) : $updatedAt;
    }

    $projectData = [
        'id' => $projectId,
        'title' => $title,
        'summary' => $summary,
        'description' => $description,
        'features' => textareaLinesToArray((string) ($_POST['features'] ?? '')),
        'tags' => commaListToArray((string) ($_POST['tags'] ?? '')),
        'script_url' => trim((string) ($_POST['script_url'] ?? '')),
        'repository_url' => trim((string) ($_POST['repository_url'] ?? '')),
        'updated_at' => $updatedAt,
        'created_at' => $createdAt,
    ];

    if ($formErrors === []) {
        if ($currentOriginalId !== '' && $projectId !== $currentOriginalId) {
            $projects = array_values(array_filter($projects, static fn ($item) => ($item['id'] ?? '') !== $currentOriginalId));
        }

        $projects = replaceProject($projects, $projectData);

        if (!saveGasProjects($projects)) {
            $formErrors[] = 'データの保存に失敗しました。ファイルの書き込み権限を確認してください。';
        } else {
            $_SESSION['flash_success'] = 'GAS情報を保存しました。';
            header('Location: admin.php?id=' . urlencode($projectId));
            exit;
        }
    }

    $projectToEdit = $projectData;
}

if (!$projectToEdit) {
    $projectToEdit = [
        'id' => '',
        'title' => '',
        'summary' => '',
        'description' => '',
        'features' => [],
        'tags' => [],
        'script_url' => '',
        'repository_url' => '',
        'updated_at' => date('Y-m-d'),
        'created_at' => date('Y-m-d'),
    ];
    $currentOriginalId = '';
}

$isEditing = $currentOriginalId !== '';
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理画面｜Google Apps Script ギャラリー</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
    <div class="container">
        <div>
            <h1 class="site-title">管理画面</h1>
            <p class="site-description"><a href="index.php">公開サイトを表示</a></p>
        </div>
    </div>
</header>

<?php if (!$isLoggedIn): ?>
    <div class="admin-layout">
        <section class="admin-card">
            <h2>ログイン</h2>
            <?php if ($flashError): ?>
                <div class="alert error"><?= h($flashError); ?></div>
            <?php endif; ?>
            <?php if ($loginError): ?>
                <div class="alert error"><?= h($loginError); ?></div>
            <?php endif; ?>
            <form method="post" action="admin.php">
                <div class="form-group">
                    <label for="login_password">管理者パスワード</label>
                    <input type="password" name="login_password" id="login_password" required>
                </div>
                <div class="form-actions">
                    <button class="button primary" type="submit">ログイン</button>
                </div>
            </form>
        </section>
    </div>
<?php else: ?>
    <div class="admin-layout">
        <?php if ($flashSuccess): ?>
            <div class="alert success"><?= h($flashSuccess); ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert error"><?= h($flashError); ?></div>
        <?php endif; ?>
        <?php if ($formErrors): ?>
            <div class="alert error">
                <ul style="margin:0;padding-left:1.2rem;">
                    <?php foreach ($formErrors as $error): ?>
                        <li><?= h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="admin-card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
                <h2>登録済みGAS</h2>
                <div>
                    <a class="button secondary" href="admin.php">新規登録</a>
                    <a class="button secondary" href="admin.php?logout=1">ログアウト</a>
                </div>
            </div>
            <?php if ($projects): ?>
                <ul class="project-list">
                    <?php foreach ($projects as $project): ?>
                        <li>
                            <span>
                                <strong><?= h($project['title']); ?></strong><br>
                                <small><?= h(formatDate($project['updated_at'] ?? $project['created_at'] ?? '')); ?> 更新 / ID: <?= h($project['id']); ?></small>
                            </span>
                            <a href="admin.php?id=<?= urlencode($project['id']); ?>">編集</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>まだGASは登録されていません。</p>
            <?php endif; ?>
        </section>

        <section class="admin-card">
            <h2><?= $isEditing ? 'GAS情報の編集' : '新規GAS登録'; ?></h2>
            <form method="post" action="admin.php<?= $isEditing ? '?id=' . urlencode($currentOriginalId) : ''; ?>">
                <input type="hidden" name="save_project" value="1">
                <input type="hidden" name="original_id" value="<?= h($currentOriginalId); ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">タイトル</label>
                        <input type="text" name="title" id="title" value="<?= h($projectToEdit['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="project_id">公開ID (URL用)</label>
                        <input type="text" name="project_id" id="project_id" value="<?= h($projectToEdit['id']); ?>" placeholder="例: form-automation">
                        <small style="color:var(--muted-text);">半角英数字とハイフンのみ。未入力の場合はタイトルから自動生成します。</small>
                    </div>
                    <div class="form-group">
                        <label for="summary">概要</label>
                        <textarea name="summary" id="summary" required><?= h($projectToEdit['summary']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="tags">タグ</label>
                        <input type="text" name="tags" id="tags" value="<?= h(arrayToCommaList($projectToEdit['tags'])); ?>" placeholder="例: 自動化, Slack連携">
                        <small style="color:var(--muted-text);">カンマ区切りで入力</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">詳細説明</label>
                    <textarea name="description" id="description" rows="6"><?= h($projectToEdit['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="features">主な機能 (1行につき1項目)</label>
                    <textarea name="features" id="features" rows="5"><?= h(arrayToTextarea($projectToEdit['features'])); ?></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="script_url">スクリプトURL</label>
                        <input type="url" name="script_url" id="script_url" value="<?= h($projectToEdit['script_url']); ?>" placeholder="https://script.google.com/...">
                    </div>
                    <div class="form-group">
                        <label for="repository_url">リポジトリURL</label>
                        <input type="url" name="repository_url" id="repository_url" value="<?= h($projectToEdit['repository_url']); ?>" placeholder="https://github.com/...">
                    </div>
                    <div class="form-group">
                        <label for="updated_at">最終更新日</label>
                        <input type="date" name="updated_at" id="updated_at" value="<?= h($projectToEdit['updated_at']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="created_at">初回公開日</label>
                        <input type="date" name="created_at" id="created_at" value="<?= h($projectToEdit['created_at']); ?>">
                    </div>
                </div>
                <div class="form-actions">
                    <button class="button primary" type="submit">保存する</button>
                    <a class="button secondary" href="index.php" target="_blank" rel="noopener">公開ページを確認</a>
                </div>
            </form>
        </section>
    </div>
<?php endif; ?>
</body>
</html>
