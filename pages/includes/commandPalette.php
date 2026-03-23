<?php
// includes/commandPalette.php
// A global Ctrl+K Command Palette for quick navigation and actions.

if (session_status() === PHP_SESSION_NONE) session_start();

$role = $_SESSION['role'] ?? 'guest';
$isLoggedIn = $role !== 'guest';

// Build the array of commands dynamically based on user role
$commands = [
    // Global Navigation
    ['id' => 'nav-home',     'title' => __('cmd_home'),            'icon' => 'bi-house',          'url' => '/e-commerce/index.php', 'category' => __('cmd_cat_nav')],
    ['id' => 'nav-products', 'title' => __('cmd_browse_products'), 'icon' => 'bi-grid',           'url' => '/e-commerce/pages/product/productList.php', 'category' => __('cmd_cat_nav')],
    ['id' => 'nav-blog',     'title' => __('cmd_blog'),            'icon' => 'bi-journal-text',   'url' => '/e-commerce/pages/blog/index.php', 'category' => __('cmd_cat_nav')],
    ['id' => 'nav-docs',     'title' => __('cmd_docs'),            'icon' => 'bi-book',           'url' => '/e-commerce/pages/documentation.php', 'category' => __('cmd_cat_nav')],

    // Actions
    ['id' => 'action-theme', 'title' => __('cmd_theme'), 'icon' => 'bi-moon-stars', 'action' => 'toggleTheme', 'category' => __('cmd_cat_actions')],
    
    // Language Switches
    ['id' => 'lang-en', 'title' => __('cmd_lang_en'), 'icon' => 'bi-translate', 'url' => '/e-commerce/pages/includes/lang/setLanguage.php?lang=en', 'category' => __('cmd_cat_lang')],
    ['id' => 'lang-fr', 'title' => __('cmd_lang_fr'), 'icon' => 'bi-translate', 'url' => '/e-commerce/pages/includes/lang/setLanguage.php?lang=fr', 'category' => __('cmd_cat_lang')],
    ['id' => 'lang-es', 'title' => __('cmd_lang_es'), 'icon' => 'bi-translate', 'url' => '/e-commerce/pages/includes/lang/setLanguage.php?lang=es', 'category' => __('cmd_cat_lang')],
];

if (!$isLoggedIn) {
    $commands[] = ['id' => 'auth-login',  'title' => __('cmd_login'),  'icon' => 'bi-box-arrow-in-right', 'url' => '/e-commerce/pages/auth/login.php',  'category' => __('cmd_cat_account')];
    $commands[] = ['id' => 'auth-signup', 'title' => __('cmd_signup'), 'icon' => 'bi-person-plus',        'url' => '/e-commerce/pages/auth/signup.php', 'category' => __('cmd_cat_account')];
} else {
    // Logged In Global
    $commands[] = ['id' => 'nav-cart',     'title' => __('cmd_cart'),       'icon' => 'bi-cart3',           'url' => '/e-commerce/pages/cart/viewCart.php',   'category' => __('cmd_cat_shop')];
    $commands[] = ['id' => 'nav-checkout', 'title' => __('cmd_checkout'),   'icon' => 'bi-credit-card',     'url' => '/e-commerce/pages/cart/checkout.php',   'category' => __('cmd_cat_shop')];
    $commands[] = ['id' => 'nav-profile',  'title' => __('cmd_profile'),    'icon' => 'bi-person',          'url' => '/e-commerce/pages/user/profile.php',    'category' => __('cmd_cat_account')];
    $commands[] = ['id' => 'nav-orders',   'title' => __('cmd_orders'),     'icon' => 'bi-bag',             'url' => '/e-commerce/pages/user/orders.php',     'category' => __('cmd_cat_account')];
    $commands[] = ['id' => 'blog-create',  'title' => __('cmd_write_post'), 'icon' => 'bi-pencil-square',   'url' => '/e-commerce/pages/blog/createPost.php', 'category' => __('cmd_cat_blog')];

    // Dashboards
    if ($role === 'admin') {
        $commands[] = ['id' => 'dash-admin', 'title' => __('cmd_dash_admin'),   'icon' => 'bi-speedometer2', 'url' => '/e-commerce/pages/admin/dashboard.php',  'category' => __('cmd_cat_admin')];
        $commands[] = ['id' => 'adm-prods',  'title' => __('cmd_manage_products'), 'icon' => 'bi-box',       'url' => '/e-commerce/pages/admin/product.php',    'category' => __('cmd_cat_admin')];
        $commands[] = ['id' => 'adm-add',    'title' => __('cmd_add_product'),   'icon' => 'bi-plus-circle',  'url' => '/e-commerce/pages/admin/addProduct.php','category' => __('cmd_cat_admin')];
        $commands[] = ['id' => 'adm-users',  'title' => __('cmd_manage_users'),  'icon' => 'bi-people',       'url' => '/e-commerce/pages/admin/users.php',     'category' => __('cmd_cat_admin')];
        $commands[] = ['id' => 'adm-orders', 'title' => __('cmd_manage_orders'), 'icon' => 'bi-bag-check',    'url' => '/e-commerce/pages/admin/orders.php',    'category' => __('cmd_cat_admin')];
        $commands[] = ['id' => 'adm-blog',   'title' => __('cmd_manage_blog'),   'icon' => 'bi-journal-text', 'url' => '/e-commerce/pages/admin/blog.php',      'category' => __('cmd_cat_admin')];
    } elseif ($role === 'student') {
        $commands[] = ['id' => 'dash-student', 'title' => __('cmd_dash_student'), 'icon' => 'bi-mortarboard', 'url' => '/e-commerce/pages/student/dashboard.php', 'category' => __('cmd_cat_student')];
    } else {
        $commands[] = ['id' => 'dash-user', 'title' => __('cmd_dash_user'), 'icon' => 'bi-speedometer2', 'url' => '/e-commerce/pages/user/dashboard.php', 'category' => __('cmd_cat_user')];
    }

    $commands[] = ['id' => 'auth-logout', 'title' => __('cmd_logout'), 'icon' => 'bi-box-arrow-right', 'url' => '/e-commerce/pages/auth/logout.php', 'category' => __('cmd_cat_account')];
}
?>

<!-- Add dark mode variables so Toggle Theme actually works globally -->
<style>
  html.dark-theme {
    --bg: #0f0f11;
    --bg2: #161618;
    --bg3: #1f1f22;
    --border: #2c2c30;
    --accent: #ffffff;
    --accent2: #dddddd;
    --text: #eeeeee;
    --text-muted: #888888;
    --success: #4ade80;
    --danger: #f87171;
    --info: #60a5fa;
    --card-hover: #1c1c1f;
  }
  
  /* Command Palette Modal Styles */
  #cmdPaletteOverlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: flex-start;
    justify-content: center;
    padding-top: 10vh;
    opacity: 0;
    transition: opacity 0.2s ease;
  }
  #cmdPaletteOverlay.open {
    display: flex;
    opacity: 1;
  }
  #cmdPaletteBox {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    transform: scale(0.98) translateY(-10px);
    transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex;
    flex-direction: column;
  }
  #cmdPaletteOverlay.open #cmdPaletteBox {
    transform: scale(1) translateY(0);
  }
  #cmdPaletteHeader {
    padding: 1rem 1.2rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 0.8rem;
  }
  #cmdPaletteHeader i {
    color: var(--text-muted);
    font-size: 1.2rem;
  }
  #cmdPaletteInput {
    border: none;
    background: transparent;
    width: 100%;
    font-size: 1.1rem;
    color: var(--text);
    outline: none;
    font-weight: 500;
  }
  #cmdPaletteInput::placeholder {
    color: var(--text-muted);
    opacity: 0.6;
  }
  #cmdPaletteList {
    max-height: 400px;
    overflow-y: auto;
    padding: 0.5rem;
  }
  .cmd-item {
    padding: 0.8rem 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--text);
    text-decoration: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.1s, color 0.1s;
  }
  .cmd-item i {
    color: var(--text-muted);
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
  }
  .cmd-item:hover, .cmd-item.selected {
    background: var(--accent);
    color: var(--bg);
  }
  .cmd-item:hover i, .cmd-item.selected i {
    color: var(--bg);
  }
  .cmd-item-desc {
    font-size: 0.95rem;
    font-weight: 500;
  }
  .cmd-category {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    padding: 0.8rem 1rem 0.3rem 1rem;
  }
  #cmdPaletteFooter {
    padding: 0.8rem 1.2rem;
    border-top: 1px solid var(--border);
    background: var(--bg2);
    font-size: 0.75rem;
    color: var(--text-muted);
    display: flex;
    justify-content: space-between;
  }
  .cmd-kbd {
    background: var(--border);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.7rem;
    color: var(--text);
  }
  .cmd-empty {
    padding: 2rem;
    text-align: center;
    color: var(--text-muted);
    font-size: 0.9rem;
  }
</style>

<div id="cmdPaletteOverlay">
  <div id="cmdPaletteBox">
    <div id="cmdPaletteHeader">
      <i class="bi bi-search"></i>
      <input type="text" id="cmdPaletteInput" placeholder="<?= __('cmd_placeholder') ?>" autocomplete="off">
    </div>
    <div id="cmdPaletteList">
      <!-- Items injected via JS -->
    </div>
    <div id="cmdPaletteFooter">
      <div>
        <span class="cmd-kbd">↑</span> <span class="cmd-kbd">↓</span> to navigate
      </div>
      <div>
        <span class="cmd-kbd">Enter</span> to select
      </div>
      <div>
        <span class="cmd-kbd">Esc</span> to close
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    
    // --- Dark Mode Logic ---
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        html.classList.add('dark-theme');
    }

    window.toggleTheme = function() {
        html.classList.toggle('dark-theme');
        const isDark = html.classList.contains('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        closePalette();
    };

    // --- Command Palette Logic ---
    const commandsData = <?= json_encode($commands) ?>;
    
    const overlay = document.getElementById('cmdPaletteOverlay');
    const input = document.getElementById('cmdPaletteInput');
    const list = document.getElementById('cmdPaletteList');
    
    let isPaletteOpen = false;
    let selectedIndex = 0;
    let filteredCommands = [];

    function openPalette() {
        isPaletteOpen = true;
        input.value = '';
        overlay.classList.add('open');
        input.focus();
        renderList('');
    }

    function closePalette() {
        isPaletteOpen = false;
        overlay.classList.remove('open');
        input.blur();
    }

    function renderList(query) {
        query = query.toLowerCase().trim();
        
        filteredCommands = commandsData.filter(cmd => 
            cmd.title.toLowerCase().includes(query) || 
            cmd.category.toLowerCase().includes(query)
        );

        list.innerHTML = '';
        
        if (filteredCommands.length === 0) {
            list.innerHTML = '<div class="cmd-empty">No commands found matching "'+query+'"</div>';
            selectedIndex = -1;
            return;
        }

        selectedIndex = 0;
        let currentCat = null;

        filteredCommands.forEach((cmd, idx) => {
            if (cmd.category !== currentCat) {
                const catEl = document.createElement('div');
                catEl.className = 'cmd-category';
                catEl.textContent = cmd.category;
                list.appendChild(catEl);
                currentCat = cmd.category;
            }

            const item = document.createElement('a');
            item.className = 'cmd-item' + (idx === 0 ? ' selected' : '');
            item.href = cmd.url ? cmd.url : '#';
            item.id = 'cmd-idx-' + idx;
            
            // If action, override click
            if (cmd.action) {
                item.onclick = (e) => {
                    e.preventDefault();
                    if (window[cmd.action]) window[cmd.action]();
                };
            }

            // Also support clicking items to test them manually
            item.addEventListener('mouseover', () => {
               updateSelection(idx); 
            });

            item.innerHTML = `
                <i class="bi ${cmd.icon}"></i>
                <div class="cmd-item-desc">${cmd.title}</div>
            `;
            list.appendChild(item);
        });
    }

    function updateSelection(newIdx) {
        if (filteredCommands.length === 0 || newIdx < 0 || newIdx >= filteredCommands.length) return;
        const prev = document.getElementById('cmd-idx-' + selectedIndex);
        if (prev) prev.classList.remove('selected');
        
        selectedIndex = newIdx;
        const next = document.getElementById('cmd-idx-' + selectedIndex);
        if (next) {
            next.classList.add('selected');
            next.scrollIntoView({ block: 'nearest' });
        }
    }

    function executeSelected() {
        if (selectedIndex < 0 || selectedIndex >= filteredCommands.length) return;
        const cmd = filteredCommands[selectedIndex];
        if (cmd.action) {
            if (window[cmd.action]) window[cmd.action]();
        } else if (cmd.url) {
            window.location.href = cmd.url;
        }
    }

    // Event Listeners
    document.addEventListener('keydown', (e) => {
        // Toggle: Ctrl+K or Cmd+K
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (isPaletteOpen) closePalette();
            else openPalette();
        }
        
        // Escape
        if (e.key === 'Escape' && isPaletteOpen) {
            closePalette();
        }

        // Navigation
        if (isPaletteOpen) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                updateSelection(selectedIndex + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                updateSelection(selectedIndex - 1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                executeSelected();
            }
        }
    });

    // Close on click outside
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closePalette();
        }
    });

    // Input filtering
    input.addEventListener('input', (e) => {
        renderList(e.target.value);
    });
});
</script>
