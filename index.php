<?php
require_once 'config.php';

$db = getDB();

$error = '';
$success = '';

// handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $action = $_POST['action'];

    // add new task
    if ($action == 'add') {
        $title = trim($_POST['title']);

        if ($title == '') {
            $error = 'Please write something first!';
        } else if (strlen($title) > 255) {
            $error = 'Task is too long!';
        } else {
            $stmt = $db->prepare('INSERT INTO tasks (title) VALUES (:title)');
            $stmt->execute([':title' => $title]);
            $success = 'Task added!';
        }
    }

    // mark task as done or not done
    if ($action == 'toggle') {
        $id = $_POST['id'];
        $stmt = $db->prepare('UPDATE tasks SET is_completed = CASE WHEN is_completed = 1 THEN 0 ELSE 1 END WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    // delete task
    if ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $db->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $success = 'Task deleted!';
    }

    // redirect so the form doesnt resubmit on refresh
    if ($success) {
        header('Location: index.php?msg=' . urlencode($success));
    } else if ($error) {
        header('Location: index.php?err=' . urlencode($error));
    } else {
        header('Location: index.php');
    }
    exit;
}

// get messages from url
if (isset($_GET['msg'])) {
    $success = $_GET['msg'];
}
if (isset($_GET['err'])) {
    $error = $_GET['err'];
}

// figure out which filter is active
$filter = 'all';
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
}

// get tasks from database
$sql = 'SELECT * FROM tasks';

if ($filter == 'active') {
    $sql .= ' WHERE is_completed = 0';
} else if ($filter == 'completed') {
    $sql .= ' WHERE is_completed = 1';
}

$sql .= ' ORDER BY created_at DESC';

$tasks = $db->query($sql)->fetchAll();

// count tasks for the tabs
$allTasks = $db->query('SELECT * FROM tasks')->fetchAll();
$totalCount = count($allTasks);
$activeCount = 0;
$completedCount = 0;

foreach ($allTasks as $t) {
    if ($t['is_completed'] == 1) {
        $completedCount++;
    } else {
        $activeCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Todo List</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">

    <header>
        <div class="logo">do<span>.</span>it</div>
        <p class="subtitle">Your tasks, your pace.</p>
    </header>

    <?php if ($success != '') { ?>
        <div class="flash success"><?php echo $success; ?></div>
    <?php } ?>

    <?php if ($error != '') { ?>
        <div class="flash error"><?php echo $error; ?></div>
    <?php } ?>

    <!-- add task form -->
    <form class="add-form" method="POST" action="index.php">
        <input type="hidden" name="action" value="add">
        <input type="text" name="title" placeholder="What needs to be done?" maxlength="255" autocomplete="off" autofocus>
        <button class="btn-add" type="submit">+</button>
    </form>

    <!-- filter tabs -->
    <div class="filters">
        <a href="?filter=all" class="filter-link <?php if($filter == 'all') echo 'active'; ?>">
            All <span class="count"><?php echo $totalCount; ?></span>
        </a>
        <a href="?filter=active" class="filter-link <?php if($filter == 'active') echo 'active'; ?>">
            Active <span class="count"><?php echo $activeCount; ?></span>
        </a>
        <a href="?filter=completed" class="filter-link <?php if($filter == 'completed') echo 'active'; ?>">
            Completed <span class="count"><?php echo $completedCount; ?></span>
        </a>
    </div>

    <!-- task list -->
    <div class="task-list">
        <?php if (count($tasks) == 0) { ?>
            <div class="empty">
                <span class="icon">✓</span>
                <p>
                    <?php
                    if ($filter == 'completed') {
                        echo 'No completed tasks yet!';
                    } else if ($filter == 'active') {
                        echo "You're all caught up!";
                    } else {
                        echo 'No tasks yet. Add one above.';
                    }
                    ?>
                </p>
            </div>
        <?php } else { ?>
            <?php foreach ($tasks as $task) { ?>
                <div class="task-item <?php if($task['is_completed']) echo 'completed'; ?>">

                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                        <button class="btn-toggle" type="submit">
                            <?php if($task['is_completed']) echo '✓'; ?>
                        </button>
                    </form>

                    <span class="task-title"><?php echo htmlspecialchars($task['title']); ?></span>

                    <span class="task-date"><?php echo date('M j', strtotime($task['created_at'])); ?></span>

                    <form method="POST" action="index.php" onsubmit="return confirm('Delete this task?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                        <button class="btn-delete" type="submit">✕</button>
                    </form>

                </div>
            <?php } ?>
        <?php } ?>
    </div>

    <p class="footer-note">Built with PHP + SQL Server</p>

</div>
</body>
</html>