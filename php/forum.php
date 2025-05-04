<?php
//  Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Set up pagination
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Check for search query
$search_query = isset($_GET['search']) ? clean($conn, $_GET['search']) : '';
$filter_subject = isset($_GET['subject']) ? clean($conn, $_GET['subject']) : '';
$filter_status = isset($_GET['status']) ? clean($conn, $_GET['status']) : '';

// Base SQL query
$sql = "SELECT q.id, q.title, q.subject, q.content, q.created_at, q.is_resolved, 
               u.username, u.user_type, COUNT(a.id) as answer_count
        FROM questions q
        JOIN users u ON q.user_id = u.id
        LEFT JOIN answers a ON q.id = a.question_id";

// Add WHERE clauses based on filters
$where_clauses = [];
$params = [];
$param_types = "";

if(!empty($search_query)) {
    $where_clauses[] = "(q.title LIKE ? OR q.content LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $param_types .= "ss";
}

if(!empty($filter_subject)) {
    $where_clauses[] = "q.subject = ?";
    $params[] = $filter_subject;
    $param_types .= "s";
}

if(!empty($filter_status)) {
    if($filter_status == 'resolved') {
        $where_clauses[] = "q.is_resolved = 1";
    } else if($filter_status == 'unresolved') {
        $where_clauses[] = "q.is_resolved = 0";
    }
}

// Combine WHERE clauses if any
if(!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Group by and order
$sql .= " GROUP BY q.id ORDER BY q.created_at DESC";

// Count total results for pagination
$count_sql = "SELECT COUNT(*) as total FROM (" . $sql . ") as counted";
$count_stmt = mysqli_prepare($conn, $count_sql);

if(!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}

mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_results = $count_row['total'];
$total_pages = ceil($total_results / $results_per_page);

// Add LIMIT for pagination
$sql .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $results_per_page;
$param_types .= "ii";

// Prepare and execute the main query
$stmt = mysqli_prepare($conn, $sql);

if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get subject list for filter dropdown
$subject_sql = "SELECT DISTINCT subject FROM questions ORDER BY subject";
$subject_result = mysqli_query($conn, $subject_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q&A Forum - Sage</title>
    <link rel="stylesheet" href="../css/forum.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Sage</h2>
                <p class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="forum.php" class="active">Q&A Forum</a></li>
                    <?php if($_SESSION["user_type"] == 'student') { ?>
                    <li><a href="tutors.php">Find Tutors</a></li>
                    <li><a href="my_sessions.php">My Sessions</a></li>
                    <?php } else { ?>
                    <li><a href="my_students.php">My Students</a></li>
                    <li><a href="schedule.php">My Schedule</a></li>
                    <?php } ?>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Q&A Forum</h1>
                <div class="header-actions">
                    <span class="user-type"><?php echo ucfirst($_SESSION["user_type"]); ?></span>
                </div>
            </header>
            
            <div class="forum-content">
                <div class="forum-actions">
                    <a href="ask_question.php" class="btn btn-primary">Ask a Question</a>
                    
                    <form class="search-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
                        <div class="search-input">
                            <input type="text" name="search" placeholder="Search questions..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" class="search-btn">🔍</button>
                        </div>
                        
                        <div class="filter-options">
                            <select name="subject" class="filter-select">
                                <option value="">All Subjects</option>
                                <?php
                                while($subject_row = mysqli_fetch_assoc($subject_result)) {
                                    $selected = ($filter_subject == $subject_row['subject']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($subject_row['subject']) . '" ' . $selected . '>' 
                                         . htmlspecialchars($subject_row['subject']) . '</option>';
                                }
                                ?>
                            </select>
                            
                            <select name="status" class="filter-select">
                                <option value="">All Status</option>
                                <option value="resolved" <?php echo ($filter_status == 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                <option value="unresolved" <?php echo ($filter_status == 'unresolved') ? 'selected' : ''; ?>>Unresolved</option>
                            </select>
                            
                            <button type="submit" class="btn btn-filter">Filter</button>
                            <?php if(!empty($search_query) || !empty($filter_subject) || !empty($filter_status)) { ?>
                                <a href="forum.php" class="btn btn-clear">Clear Filters</a>
                            <?php } ?>
                        </div>
                    </form>
                </div>
                
                <div class="questions-list">
                    <?php
                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                            $is_resolved = $row['is_resolved'] ? 'resolved' : 'unresolved';
                            $status_label = $row['is_resolved'] ? 'Resolved' : 'Unresolved';
                            $status_icon = $row['is_resolved'] ? '✓' : '?';
                            
                            echo '<div class="question-item ' . $is_resolved . '">';
                            echo '<div class="question-status status-' . $is_resolved . '">' . $status_icon . '</div>';
                            echo '<div class="question-content">';
                            echo '<div class="question-meta">';
                            echo '<div class="question-subject">' . htmlspecialchars($row['subject']) . '</div>';
                            echo '<div class="question-author">' . htmlspecialchars($row['username']) . ' (' . ucfirst($row['user_type']) . ')</div>';
                            echo '</div>';
                            echo '<h2 class="question-title"><a href="view_question.php?id=' . $row['id'] . '">' . htmlspecialchars($row['title']) . '</a></h2>';
                            echo '<p class="question-excerpt">' . htmlspecialchars(substr($row['content'], 0, 150)) . (strlen($row['content']) > 150 ? '...' : '') . '</p>';
                            echo '<div class="question-details">';
                            echo '<span class="question-date">' . date('M j, Y \a\t g:i A', strtotime($row['created_at'])) . '</span>';
                            echo '<span class="question-answers">' . $row['answer_count'] . ' ' . ($row['answer_count'] == 1 ? 'answer' : 'answers') . '</span>';
                            echo '<span class="question-status-label status-' . $is_resolved . '">' . $status_label . '</span>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                        
                        // Pagination
                        if($total_pages > 1) {
                            echo '<div class="pagination">';
                            
                            // Previous page link
                            if($page > 1) {
                                echo '<a href="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '?page=' . ($page - 1) 
                                     . '&search=' . urlencode($search_query) 
                                     . '&subject=' . urlencode($filter_subject) 
                                     . '&status=' . urlencode($filter_status) 
                                     . '" class="page-link">&laquo; Previous</a>';
                            }
                            
                            // Page numbers
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for($i = $start_page; $i <= $end_page; $i++) {
                                if($i == $page) {
                                    echo '<span class="page-link current">' . $i . '</span>';
                                } else {
                                    echo '<a href="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '?page=' . $i 
                                         . '&search=' . urlencode($search_query) 
                                         . '&subject=' . urlencode($filter_subject) 
                                         . '&status=' . urlencode($filter_status) 
                                         . '" class="page-link">' . $i . '</a>';
                                }
                            }
                            
                            // Next page link
                            if($page < $total_pages) {
                                echo '<a href="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '?page=' . ($page + 1) 
                                     . '&search=' . urlencode($search_query) 
                                     . '&subject=' . urlencode($filter_subject) 
                                     . '&status=' . urlencode($filter_status) 
                                     . '" class="page-link">Next &raquo;</a>';
                            }
                            
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="no-results">';
                        echo '<p>No questions found. Be the first to ask!</p>';
                        echo '<a href="ask_question.php" class="btn btn-primary">Ask a Question</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
 