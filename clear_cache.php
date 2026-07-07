<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "PHP Opcache reset successfully.\n";
} else {
    echo "Opcache is not enabled or function does not exist.\n";
}
// Also clear session
session_start();
session_destroy();
echo "Session cleared.\n";
