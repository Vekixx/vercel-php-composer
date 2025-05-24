<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>磁力链接查询</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/amazeui@2.7.2/dist/css/amazeui.min.css">
  <style>
    .card { display: flex; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 6px; }
    .card img { width: 120px; height: 145px; object-fit: cover; margin-right: 10px; }
    .card-content { flex: 1; }
    .magnet-box { margin: 10px 0; border: 1px solid #ccc; padding: 10px; border-radius: 5px; }
    #loadMoreBtn { margin: 10px 0; }
    #progressBarContainer { display: none; margin-top: 15px; }
    #progressLabel { margin-bottom: 5px; color: #555; }
    .progress-bar-animated {
      background: #409eff;
      height: 8px;
      width: 100%;
      animation: progress 1.2s linear infinite;
    }
    @keyframes progress {
      0% { background-position: 0% 0; }
      100% { background-position: 100% 0; }
    }
  </style>
</head>
<body>
<div class="am-container am-margin-top">
  <h2 class="am-text-center">磁力链接查询</h2>
  <p class="am-text-center">支持番号、关键词、演员搜索</p>

  <ul class="am-nav am-nav-pills am-nav-justify" id="searchTabs">
    <li class="am-active"><a href="#" onclick="setMode('keyword')">关键词</a></li>
    <li><a href="#" onclick="setMode('code')">番号</a></li>
    <li><a href="#" onclick="setMode('star')">演员</a></li>
  </ul>

  <form class="am-form am-margin-top" id="searchForm" onsubmit="search(); return false;">
    <input type="text" id="keyword" placeholder="请输入关键词、番号或演员">
    <div id="sortOptions" class="am-margin-top">
      <label class="am-radio-inline"><input type="radio" name="sortBy" value="date" checked> 按日期</label>
      <label class="am-radio-inline"><input type="radio" name="sortBy" value="size"> 按大小</label>
      <label class="am-radio-inline"><input type="radio" name="sortOrder" value="desc" checked> 降序</label>
      <label class="am-radio-inline"><input type="radio" name="sortOrder" value="asc"> 升序</label>
    </div>
    <button type="submit" class="am-btn am-btn-primary am-margin-top">搜索</button>
  </form>

  <div id="progressBarContainer">
    <div id="progressLabel">正在查询...</div>
    <div class="am-progress am-progress-striped am-active">
      <div class="am-progress-bar progress-bar-animated" style="width: 100%"></div>
    </div>
  </div>

  <div id="noResult" class="am-alert am-alert-danger am-margin-top" style="display: none;">
    <strong>没有您要的结果！</strong>
    <p>尝试缩小您的搜索关键词，去除前缀空格，只需输入一个单词：</p>
    <p>☆ 如搜索《番号》，格式请按【STAR-433】或【STARD433】搜索</p>
    <p>☆ 如搜索《女优》，请确保按照维基百科常见中文名，如【水菜丽】请改成【みづなれい】搜索</p>
    <p>☆ 如搜索《影片标题》，请缩短字符数，并优先使用【日文】搜索</p>
  </div>

  <div id="results" class="am-margin-top"></div>
  <button id="loadMoreBtn" class="am-btn am-btn-secondary am-btn-block" onclick="loadMore()" style="display: none;">加载更多</button>

  <hr>
  <div class="am-panel am-panel-default">
    <div class="am-panel-hd">帮助</div>
    <div class="am-panel-bd">可通过“关键词”、“番号”或“演员”搜索影片，番号搜索将显示磁力链接。</div>
  </div>
  <div class="am-panel am-panel-default">
    <div class="am-panel-hd">免责声明</div>
    <div class="am-panel-bd">本站不存储、不传播任何影片，仅供学习交流使用，如侵犯您的权益请及时联系处理。</div>
  </div>
  <p class="am-text-center">Mod By: 龙崎崎 v1.7.9 © 2025 <a href="#">版权声明</a></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
  let currentMode = 'keyword';
  let currentPage = 1;
  let currentKeyword = '';

  function setMode(mode) {
    currentMode = mode;
    currentPage = 1;
    $('#searchTabs li').removeClass('am-active');
    $(`#searchTabs li:has(a[onclick*="${mode}"])`).addClass('am-active');
    $('#results').empty();
    $('#loadMoreBtn').hide();
    $('#noResult').hide();
    if (mode === 'code') $('#sortOptions').show(); else $('#sortOptions').hide();
  }

  function updateProgress(text, show = true) {
    if (show) {
      $('#progressLabel').text(text);
      $('#progressBarContainer').show();
    } else {
      $('#progressBarContainer').hide();
    }
  }

  async function search(isLoadMore = false) {
    if (!isLoadMore) {
      currentPage = 1;
      $('#results').empty();
      $('#loadMoreBtn').hide();
      $('#noResult').hide();
    }

    const keyword = $('#keyword').val().trim();
    currentKeyword = keyword;
    if (!keyword) return;

    const sortBy = $('input[name=sortBy]:checked').val();
    const sortOrder = $('input[name=sortOrder]:checked').val();

    updateProgress("正在获取影片信息");

    const res = await fetch(`api.php?mode=${currentMode}&keyword=${encodeURIComponent(keyword)}&page=${currentPage}&sortBy=${sortBy}&sortOrder=${sortOrder}`);
    const data = await res.json();

    if (!data.html) {
      if (!isLoadMore) $('#noResult').show();
      updateProgress("", false);
      return;
    }

    const htmlDom = $('<div>' + data.html + '</div>');
    $('#results').append(htmlDom);

    if (data.hasMore) {
      $('#loadMoreBtn').show();
      currentPage++;
    } else {
      $('#loadMoreBtn').hide();
    }

    updateProgress("正在获取封面");

    // 异步获取高清图
    const cards = htmlDom.find('.card');
    const promises = [];
    cards.each(function () {
      const card = $(this);
      const idMatch = card.attr('onclick')?.match(/'(.*?)'/);
      if (!idMatch) return;
      const id = idMatch[1];
      promises.push(
        fetch(`api.php?mode=code&keyword=${id}`)
          .then(r => r.json())
          .then(d => {
            const newImg = $(d.html).find('img').attr('src');
            if (newImg) card.find('img').attr('src', newImg);
          })
      );
    });

    await Promise.allSettled(promises);
    updateProgress("搜索完成", true);
    setTimeout(() => updateProgress("", false), 1500);
  }

  function loadMore() {
    search(true);
  }

  function openCodeSearch(code) {
    $('#keyword').val(code);
    setMode('code');
    search();
  }

  function copyText(inputId) {
    const input = document.getElementById(inputId);
    input.select();
    input.setSelectionRange(0, 99999);
    try {
      document.execCommand('copy');
      alert('已复制到剪贴板');
    } catch (err) {
      alert('复制失败');
    }
  }
</script>
</body>
</html>
