<?php
header('Content-Type: application/json');

$api_base = 'https://ja.vekixx.com/';
$mode = $_GET['mode'] ?? 'keyword';
$keyword = trim($_GET['keyword'] ?? '');
$page = intval($_GET['page'] ?? 1);
$sortBy = $_GET['sortBy'] ?? 'date';
$sortOrder = $_GET['sortOrder'] ?? 'desc';
$result = ['html' => '', 'hasMore' => false];

// 图片封面优先使用 DMM sample 图片（避免 javbus 防盗链）
function proxyImage($info) {
    if (isset($info['samples'][0]['src'])) {
        return $info['samples'][0]['src']; // DMM 图源，无防盗链
    }
    return $info['img']; // 回退
}

// ✅ 关键词搜索
if ($mode === 'keyword' && $keyword !== '') {
    $url = "$api_base/movies/search?keyword=" . urlencode($keyword) . "&page=$page&magnet=all";
    $res = json_decode(@file_get_contents($url), true);
    if (!isset($res['movies'])) {
        echo json_encode($result);
        exit;
    }

    foreach ($res['movies'] as $movie) {
        $img = proxyImage($movie);
        $tags = isset($movie['tags']) ? implode(' ', array_map(fn($t) => "<span class='am-badge am-badge-success am-margin-right-sm'>{$t}</span>", $movie['tags'])) : '';
        $title = htmlspecialchars($movie['title']);
        $result['html'] .= <<<HTML
<div class="card" onclick="openCodeSearch('{$movie['id']}')">
  <img src="{$img}" alt="{$movie['id']}">
  <div class="card-content">
    <h5>{$title}</h5>
    <p><b>番号：</b>{$movie['id']}</p>
    <p><b>标签：</b>{$tags}</p>
  </div>
</div>
HTML;
    }

    $result['hasMore'] = $res['pagination']['hasNextPage'] ?? false;
    echo json_encode($result);
    exit;
}

// ✅ 演员搜索（正确使用 filterType=star）
if ($mode === 'star' && $keyword !== '') {
    $url = "$api_base/movies?page=$page&magnet=all&filterType=star&filterValue=" . urlencode($keyword);
    $res = json_decode(@file_get_contents($url), true);

    foreach ($res['movies'] ?? [] as $movie) {
        $img = $movie['img']; // 使用你原始逻辑，不动图片代理
        $tags = isset($movie['tags']) ? implode(' ', array_map(fn($t) => "<span class='am-badge am-badge-success'>{$t}</span>", $movie['tags'])) : '';
        $title = htmlspecialchars($movie['title']);

        $result['html'] .= <<<HTML
<div class="card" onclick="openCodeSearch('{$movie['id']}')">
  <img src="{$img}" alt="{$movie['id']}">
  <div class="card-content">
    <h5>{$title}</h5>
    <p><b>番号：</b>{$movie['id']}</p>
    <p><b>标签：</b>{$tags}</p>
  </div>
</div>
HTML;
    }

    $result['hasMore'] = $res['pagination']['hasNextPage'] ?? false;
    echo json_encode($result);
    exit;
}

// ✅ 番号搜索
if ($mode === 'code' && $keyword !== '') {
    $info = json_decode(@file_get_contents("$api_base/movies/{$keyword}"), true);
    if (!$info || !isset($info['id'])) {
        echo json_encode($result);
        exit;
    }

    $gid = $info['gid'] ?? '';
    $uc = $info['uc'] ?? '';
    $query = http_build_query(['gid' => $gid, 'uc' => $uc, 'sortBy' => $sortBy, 'sortOrder' => $sortOrder]);
    $magnets = json_decode(@file_get_contents("$api_base/magnets/{$keyword}?" . $query), true);

    $actors = implode('', array_map(fn($s) => "<span class='am-badge am-badge-primary am-margin-right-sm'>{$s['name']}</span>", $info['stars'] ?? []));
    $tags = implode('', array_map(fn($s) => "<span class='am-badge am-badge-success am-margin-right-sm'>{$s['name']}</span>", $info['genres'] ?? []));
    $img = proxyImage($info);

    $result['html'] .= <<<HTML
<div class="am-panel am-panel-default">
  <div class="am-panel-hd">影片信息 - {$info['id']}</div>
  <div class="am-panel-bd">
    <div class="am-g">
      <div class="am-u-sm-4"><img src="{$img}" class="am-img-responsive" /></div>
      <div class="am-u-sm-8">
        <p><b>标题：</b>{$info['title']}</p>
        <p><b>日期：</b>{$info['date']}</p>
        <p><b>演员：</b>{$actors}</p>
        <p><b>标签：</b>{$tags}</p>
      </div>
    </div>
  </div>
</div>
HTML;

    $count = 0;
    foreach ($magnets as $magnet) {
        $count++;
        $label = $sortBy === 'date' ? "日期：{$magnet['shareDate']}" : "大小：{$magnet['size']}";
        $inputId = "magnet{$count}";
        $link = htmlspecialchars($magnet['link']);
        $result['html'] .= <<<HTML
<div class="magnet-box">
  <div class="am-g">
    <div class="am-u-sm-9"><input id="{$inputId}" class="am-form-field" value="{$link}" readonly></div>
    <div class="am-u-sm-3">
      <button class="am-btn am-btn-success am-btn-block" onclick="copyText('{$inputId}')">复制磁力</button>
      <div class="am-text-sm am-text-muted">{$label}</div>
    </div>
  </div>
</div>
HTML;
    }

    $result['hasMore'] = ($count >= 10);
    echo json_encode($result);
    exit;
}

// 默认返回空
echo json_encode($result);
