<?php
require_once __DIR__ . '/includes/auth_check.php';
if (!is_logged_in()) {
    redirect('auth/login.php');
}
redirect(dashboard_path_by_role(current_user()['role']));
