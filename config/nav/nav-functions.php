<?php
/**
 * Determines if a page is the active page based on the current page.
 *
 * This function checks whether the given page name matches the current page
 * and whether the current page exists within a predefined list of valid pages.
 *
 * @param string $pageName The name of the page to check.
 * @param string $currentPage The currently active page.
 * @return string Returns 'active' if the page is active, otherwise an empty string.
 */
function validateActivePage($pageName, $currentPage)
{
    $validPages = ['home', 'users', 'projects', 'products', 'promos']; // List of valid pages

    // Check if the current page exists in the valid pages list and matches the given page name
    if (in_array($currentPage, $validPages) && $pageName === $currentPage) {
        return 'active'; // Return 'active' if the page is valid and matches
    }
    return ''; // Return an empty string if the page is not active
}

/**
 * Generates an HTML navigation item with an active state.
 *
 * This function creates a navigation link (`<a>` element) with a dynamic active 
 * class based on the current page. It also applies HTML escaping for security.
 *
 * @param string $href The URL the navigation item should link to.
 * @param string $pageName The name of the page associated with the navigation item.
 * @param string $currentPage The currently active page.
 * @param string $iconClass The CSS class for the icon inside the navigation item.
 * @param string $label The text label for the navigation item.
 * @return string The generated HTML string for the navigation item.
 */
function generateNavItem($href, $pageName, $currentPage, $iconClass, $label)
{
    $activeClass = validateActivePage($pageName, $currentPage); // Determine if the item should be active

    return sprintf(
        '<a href="%s" class="nav-link text-white %s" data-page="%s"><i class="%s me-2"></i> %s</a>',
        htmlspecialchars($href), // Escape href to prevent XSS
        $activeClass, // Apply active class if needed
        htmlspecialchars($pageName), // Escape page name
        htmlspecialchars($iconClass), // Escape icon class
        htmlspecialchars($label) // Escape label
    );
}

/**
 * Renders the navigation bar for the admin panel.
 *
 * This function generates an HTML navbar with a sidebar menu. It includes a 
 * toggle button for small screens and a list of navigation items dynamically 
 * generated from an array. The active page is highlighted based on the current page.
 *
 * @param string $currentPage The currently active page.
 * @return string The generated HTML string for the navbar.
 */
function renderNavbar($currentPage)
{
    $menuItems = [ // Define menu items
        ['href' => 'admin-dashboard.php', 'page' => 'home', 'icon' => 'fa-solid fa-house', 'label' => 'Home'],
        ['href' => 'manage_users.php', 'page' => 'users', 'icon' => 'fa-solid fa-users', 'label' => 'Users'],
        ['href' => 'manage_products.php', 'page' => 'products', 'icon' => 'fa-solid fa-box', 'label' => 'Products'],
    ];

    ob_start(); ?>
    <button class="btn btn-primary d-lg-none m-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebar">
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column p-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-white">Admin Panel</h4> <!-- Sidebar title -->
                </div>
                <?php foreach ($menuItems as $item): ?>
                    <?= generateNavItem($item['href'], $item['page'], $currentPage, $item['icon'], $item['label']) ?>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
    <?php
    return ob_get_clean(); // Return the buffered content as a string
}