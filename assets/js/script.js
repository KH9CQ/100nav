// 初始化主题
function initTheme() {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const savedTheme = localStorage.getItem('theme') || (prefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeButton();
}

// 切换主题
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeButton();
}

// 更新主题切换按钮文本
function updateThemeButton() {
    const button = document.querySelector('.theme-switch button');
    const currentTheme = document.documentElement.getAttribute('data-theme');
    if (button) {
        button.textContent = currentTheme === 'light' ? '深色模式' : '浅色模式';
    }
}

// 搜索引擎配置
const searchEngines = {
    google: {
        name: '谷歌',
        url: 'https://www.google.com/search?q='
    },
    baidu: {
        name: '百度',
        url: 'https://www.baidu.com/s?wd='
    },
    douyin: {
        name: '抖音',
        url: 'https://www.douyin.com/search/'
    },
    bilibili: {
        name: 'B站',
        url: 'https://search.bilibili.com/all?keyword='
    },
    taobao: {
        name: '淘宝',
        url: 'https://s.taobao.com/search?q='
    },
    1688: {
        name: '1688',
        url: 'https://s.1688.com/selloffer/offer_search.htm?keywords='
    },
    sogou: {
        name: '搜狗',
        url: 'https://www.sogou.com/web?query='
    },
    so360: {
        name: '360',
        url: 'https://www.so.com/s?q='
    },
    bing: {
        name: '必应',
        url: 'https://cn.bing.com/search?q='
    }
};

// 初始化搜索
function initSearch() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.querySelector('.search-input');
    const engineButtons = document.querySelectorAll('.engine-btn');
    let currentEngine = 'google';

    // 切换搜索引擎
    engineButtons.forEach(button => {
        button.addEventListener('click', () => {
            engineButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentEngine = button.dataset.engine;
        });
    });

    // 处理搜索
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const query = searchInput.value.trim();
        if (query) {
            window.open(searchEngines[currentEngine].url + encodeURIComponent(query), '_blank');
        }
    });
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initSearch();
});

// 图片懒加载
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    lazyImages.forEach(function(img) {
        imageObserver.observe(img);
    });
});

// 添加健康检查函数
function checkHealth() {
    fetch('/health.php', {
        method: 'GET',
        cache: 'no-cache',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Health check failed');
        }
        return response.json();
    })
    .catch(error => {
        console.warn('Connection issue detected, refreshing page...');
        window.location.reload();
    });
}

// 监听页面可见性变化
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        checkHealth();
    }
});

// 监听网络状态变化
window.addEventListener('online', function() {
    checkHealth();
});

// 定期检查（每60秒）
setInterval(checkHealth, 60000);