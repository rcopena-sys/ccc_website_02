<!-- Sidebar -->
<nav id="sidebar" class="active">
    <div class="sidebar-header">
        <h4>CCC Admin Panel</h4>
    </div>

    <ul class="list-unstyled components">
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'homepage.php' ? 'active' : ''; ?>">
            <a href="homepage.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_list.php', 'add_user.php', 'edit_user.php', 'user_role.php']) ? 'active' : ''; ?>">
            <a href="#usersSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                <i class="fas fa-users"></i> User Management
            </a>
            <ul class="collapse list-unstyled <?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_list.php', 'add_user.php', 'edit_user.php', 'user_role.php']) ? 'show' : ''; ?>" id="usersSubmenu">
                <li><a href="user_list.php"><i class="fas fa-list"></i> User List</a></li>
                <li><a href="add_user.php"><i class="fas fa-user-plus"></i> Add New User</a></li>
                <li><a href="user_role.php"><i class="fas fa-user-shield"></i> User Roles</a></li>
            </ul>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'calendars.php' ? 'active' : ''; ?>">
            <a href="calendars.php">
                <i class="fas fa-calendar-alt"></i> Calendar
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'activity.php' ? 'active' : ''; ?>">
            <a href="activity.php" class="flex items-center space-x-2 w-full text-gray-700 hover:text-white">
                <i class="fas fa-clipboard-list text-lg"></i>
                <span>Activity Logs</span>
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_feedback.php' ? 'active' : ''; ?>">
            <a href="view_feedback.php">
                <i class="fas fa-comment-alt"></i> Feedback
            </a>
        </li>
    </ul>
    
    <!-- Account Section -->
    <div class="position-absolute bottom-0 w-100 p-3">
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle me-2"></i>
                <strong><?php echo htmlspecialchars($_SESSION['firstname'] ?? 'Admin'); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sign out</a></li>
            </ul>
        </div>
    </div>
</nav>
    </div>
</div>
