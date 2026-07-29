const navToggle = document.getElementById('navToggle');
const mainNav = document.getElementById('mainNav');
const searchButton = document.getElementById('searchButton');
const searchPanel = document.getElementById('searchPanel');
const siteHeader = document.getElementById('siteHeader');

navToggle?.addEventListener('click', () => mainNav?.classList.toggle('open'));
searchButton?.addEventListener('click', () => {
  if (searchPanel) {
    searchPanel.classList.toggle('open');
    if (searchPanel.classList.contains('open')) {
      searchPanel.querySelector('input')?.focus();
    }
  }
});

window.addEventListener('scroll', () => {
  siteHeader?.classList.toggle('scrolled', window.scrollY > 20);
});

// ==========================================
// Advanced Navbar Interactive Elements
// ==========================================

// Sidebar Drawer Elements
const drawerToggleBtn = document.getElementById('drawerToggleBtn');
const sidebarDrawer = document.getElementById('sidebarDrawer');
const drawerClose = document.getElementById('drawerClose');
const drawerOverlay = document.getElementById('drawerOverlay');

// Open Drawer
drawerToggleBtn?.addEventListener('click', () => {
  if (sidebarDrawer) {
    sidebarDrawer.classList.add('open');
    sidebarDrawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden'; // Disable scroll under drawer
  }
});

// Close Drawer
const closeDrawer = () => {
  if (sidebarDrawer) {
    sidebarDrawer.classList.remove('open');
    sidebarDrawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }
};

drawerClose?.addEventListener('click', closeDrawer);
drawerOverlay?.addEventListener('click', closeDrawer);

// Escape Key Handler
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeDrawer();
    document.getElementById('currencyDropdown')?.classList.remove('open');
  }
});

// Theme Switcher Logic
const themeToggle = document.getElementById('themeToggle');
themeToggle?.addEventListener('click', () => {
  const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
  const newTheme = currentTheme === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);
});

// Currency Switcher Logic
const currencyTrigger = document.getElementById('currencyTrigger');
const currencyDropdown = document.getElementById('currencyDropdown');

currencyTrigger?.addEventListener('click', (e) => {
  e.stopPropagation();
  currencyDropdown?.classList.toggle('open');
});

currencyDropdown?.querySelectorAll('button').forEach(btn => {
  btn.addEventListener('click', () => {
    const selectedCurr = btn.getAttribute('data-currency');
    const currencyMap = {
      'INR': '🌐 EN / ₹ INR',
      'USD': '🌐 EN / $ USD',
      'EUR': '🌐 EN / € EUR'
    };
    if (currencyTrigger && currencyMap[selectedCurr]) {
      currencyTrigger.querySelector('span').textContent = currencyMap[selectedCurr];
      localStorage.setItem('currency', selectedCurr);
    }
    currencyDropdown.classList.remove('open');
  });
});

// Document Initial Configuration Restoration
document.addEventListener('DOMContentLoaded', () => {
  // Currency Selector Restore
  const savedCurrency = localStorage.getItem('currency');
  if (savedCurrency && currencyTrigger) {
    const currencyMap = {
      'INR': '🌐 EN / ₹ INR',
      'USD': '🌐 EN / $ USD',
      'EUR': '🌐 EN / € EUR'
    };
    if (currencyMap[savedCurrency]) {
      currencyTrigger.querySelector('span').textContent = currencyMap[savedCurrency];
    }
  }

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

// Close dropdown when clicking outside
document.addEventListener('click', () => {
  currencyDropdown?.classList.remove('open');
});

const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) entry.target.classList.add('visible');
  });
}, { threshold: 0.15 });

document.querySelectorAll('.reveal').forEach((element) => revealObserver.observe(element));

function togglePasswordVisibility(button) {
  const container = button.closest('.password-input-container');
  if (!container) return;
  const passwordInput = container.querySelector('input');
  if (!passwordInput) return;
  
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    button.innerHTML = `
      <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
        <line x1="1" y1="1" x2="23" y2="23"></line>
      </svg>
    `;
  } else {
    passwordInput.type = 'password';
    button.innerHTML = `
      <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
        <circle cx="12" cy="12" r="3"></circle>
      </svg>
    `;
  }
}

// AJAX Wishlist Submission with Toast Notifications & Badge Animation
document.addEventListener('submit', async (e) => {
  const form = e.target;
  if (form && form.getAttribute('action')?.includes('wishlist_action.php')) {
    e.preventDefault();

    const formData = new FormData(form);
    try {
      const response = await fetch(form.getAttribute('action'), {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          // 1. Show dynamic premium toast notification
          showToast(data.message, data.action === 'added' ? 'success' : 'info');

          // 2. Update and animate the wishlist count badge in the header
          const badge = document.getElementById('wishlistBadge');
          if (badge) {
            badge.textContent = data.wishlist_count;
            badge.classList.remove('badge-bump');
            void badge.offsetWidth; // trigger reflow to restart keyframe animation
            badge.classList.add('badge-bump');
          }

          // 3. Update the heart button icon colors and attributes dynamically
          const button = form.querySelector('button[type="submit"]');
          if (button) {
            const svg = button.querySelector('svg');
            if (data.action === 'added') {
              button.setAttribute('title', 'Remove from Wishlist');
              button.style.color = '#ff5252';
              if (svg) svg.setAttribute('fill', 'currentColor');
            } else if (data.action === 'removed') {
              button.setAttribute('title', 'Add to Wishlist');
              button.style.color = '#888';
              if (svg) svg.setAttribute('fill', 'none');
            }
          }

          // 4. Special logic for wishlist.php page to fade out and remove cards instantly
          if (window.location.pathname.includes('wishlist.php')) {
            const productCard = form.closest('.product-card');
            if (productCard) {
              productCard.style.opacity = '0';
              productCard.style.transform = 'scale(0.9)';
              productCard.style.transition = 'all 0.3s ease';
              setTimeout(() => {
                productCard.remove();
                const remaining = document.querySelectorAll('.wishlist-grid .product-card');
                if (remaining.length === 0) {
                  window.location.reload();
                }
              }, 300);
            }
          }
        } else {
          // If redirect requested (e.g. not logged in), go to login
          if (data.redirect) {
            window.location.href = data.redirect;
          } else {
            form.submit();
          }
        }
      } else {
        form.submit();
      }
    } catch (err) {
      console.error('AJAX wishlist error:', err);
      form.submit();
    }
  }
});

// Toast Notification Generator
function showToast(message, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = `
      position: fixed;
      top: 90px;
      right: 24px;
      z-index: 10000;
      display: flex;
      flex-direction: column;
      gap: 12px;
      pointer-events: none;
      max-width: 350px;
      width: min(90%, 350px);
    `;
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.style.cssText = `
    background: rgba(13, 12, 11, 0.95);
    color: #ffffff;
    border: 1px solid rgba(255, 106, 42, 0.25);
    border-left: 4px solid var(--accent, #ff6a2a);
    padding: 14px 20px;
    border-radius: 12px;
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 500;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    opacity: 0;
    transform: translateY(-20px) scale(0.9);
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    pointer-events: auto;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
  `;

  let icon = '⚡';
  if (type === 'success') {
    icon = '✨';
    toast.style.borderLeftColor = '#ff6a2a';
  } else if (type === 'info') {
    icon = '🤍';
    toast.style.borderLeftColor = '#a09a95';
  }

  toast.innerHTML = `
    <span style="font-size: 1.1rem; line-height: 1;">${icon}</span>
    <span style="flex: 1;">${message}</span>
  `;

  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0) scale(1)';
  });

  toast.addEventListener('click', () => dismissToast(toast));
  setTimeout(() => dismissToast(toast), 3500);
}

function dismissToast(toast) {
  toast.style.opacity = '0';
  toast.style.transform = 'translateY(-10px) scale(0.9)';
  toast.style.transition = 'all 0.25s ease';
  setTimeout(() => toast.remove(), 250);
}

