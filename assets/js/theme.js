// 主题切换功能
function initTheme() {
    // 获取系统主题偏好
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    // 获取保存的主题设置
    const savedTheme = localStorage.getItem('theme');
    
    // 初始化主题
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    } else if (prefersDark) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
    
    // 更新按钮文本
    updateThemeButton();
}

// 切换主题
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    updateThemeButton();
}

// 更新主题切换按钮文本
function updateThemeButton() {
    const button = document.querySelector('.theme-switch button');
    if (!button) return;
    
    const currentTheme = document.documentElement.getAttribute('data-theme');
    button.innerHTML = currentTheme === 'dark' ? 
        '<svg viewBox="0 0 24 24"><path d="M12 18C8.68629 18 6 15.3137 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 15.3137 15.3137 18 12 18ZM12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16Z"/></svg>浅色模式' : 
        '<svg viewBox="0 0 24 24"><path d="M12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21ZM12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19Z"/></svg>深色模式';
}

// 页面加载完成后初始化主题
document.addEventListener('DOMContentLoaded', initTheme);