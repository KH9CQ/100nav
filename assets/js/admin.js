// 健康检查函数
function checkAdminHealth() {
    fetch('/admin/health.php', {
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
    .then(data => {
        if (!data.session) {
            window.location.href = '/login.php';
        }
    })
    .catch(error => {
        console.warn('Connection issue detected, refreshing...');
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    });
}

// 监听页面可见性变化
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        checkAdminHealth();
    }
});

// 监听网络状态变化
window.addEventListener('online', checkAdminHealth);

// 定期检查（每30秒）
setInterval(checkAdminHealth, 30000);

// 初始检查
document.addEventListener('DOMContentLoaded', checkAdminHealth);
