<?php

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function getCurrentUser() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    if (is_array($roles)) {
        return in_array($user['Role'], $roles);
    }
    return $user['Role'] === $roles;
}

function requireRole($roles) {
    if (!hasRole($roles)) {
        // Simple error page or redirect
        echo "<div class='p-8 text-center text-red-600'>Permission Denied</div>";
        exit;
    }
}

