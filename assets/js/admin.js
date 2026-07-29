// Theme selector function
function setAdminTheme(theme) {
    document.body.setAttribute('data-theme', theme);
    localStorage.setItem('admin-theme', theme);
    
    // Update active button state
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.style.borderColor = 'transparent';
        btn.classList.remove('active');
    });
    
    const activeBtn = document.querySelector(`.theme-btn-${theme}`);
    if (activeBtn) {
        activeBtn.style.borderColor = 'var(--admin-accent)';
        activeBtn.classList.add('active');
    }
}

// Restore active border on load and bind toggle button
document.addEventListener('DOMContentLoaded', () => {
    const theme = localStorage.getItem('admin-theme') || 'dark';
    setAdminTheme(theme);

    const adminThemeToggle = document.getElementById('adminThemeToggle');
    adminThemeToggle?.addEventListener('click', () => {
        const currentTheme = localStorage.getItem('admin-theme') || 'dark';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        setAdminTheme(newTheme);
    });

    // Auto-dismiss alert notifications with a close/dismiss button
    const alerts = document.querySelectorAll('.success-message, .form-error, .flash');
    alerts.forEach(alert => {
        alert.style.position = 'relative';
        alert.style.paddingRight = '45px';
        alert.style.transition = 'all 0.5s ease';
        alert.style.display = 'flex';
        alert.style.justifyContent = 'space-between';
        alert.style.alignItems = 'center';

        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.style.cssText = `
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: currentColor;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            padding: 4px 8px;
            opacity: 0.7;
            transition: opacity 0.2s ease, transform 0.2s ease;
        `;
        closeBtn.addEventListener('mouseover', () => {
            closeBtn.style.opacity = '1';
            closeBtn.style.transform = 'translateY(-50%) scale(1.1)';
        });
        closeBtn.addEventListener('mouseout', () => {
            closeBtn.style.opacity = '0.7';
            closeBtn.style.transform = 'translateY(-50%)';
        });

        const dismissAlert = () => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        };

        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            dismissAlert();
        });

        alert.appendChild(closeBtn);

        setTimeout(() => {
            if (alert.style.display !== 'none') {
                dismissAlert();
            }
        }, 4000);
    });
});
