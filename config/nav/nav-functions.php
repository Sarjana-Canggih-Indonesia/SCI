<?php
/**
 * Determines whether the given page name matches the current page.
 *
 * This function checks if the provided `$pageName` is the same as the 
 * `$currentPage`. If they match, it returns the string `'active'`, 
 * which is commonly used in navigation to highlight the active menu item.
 *
 * @param string $pageName The name of the page to check.
 * @param string $currentPage The currently active page.
 * @return string Returns 'active' if `$pageName` matches `$currentPage`, otherwise returns an empty string.
 */
function is_active(string $pageName, string $currentPage): string
{
    return $pageName === $currentPage ? 'active' : '';
}
