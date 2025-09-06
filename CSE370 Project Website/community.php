<?php 
include 'DBconnect.php'; 
session_start(); 

if(!isset($_SESSION['user_id'])) { 
  echo "<div style='color:red; padding:1rem;'>Login required.</div>"; 
  exit();
} 

$uid = $_SESSION['user_id']; 
$msg = "";

// Handle new post
if(isset($_POST['add_post'])){
    $content = $conn->real_escape_string($_POST['content']); 
    $anon = isset($_POST['anonymous']) ? 1 : 0;
    $sql = "INSERT INTO community_posts(user_id,content,anonymous_flag) VALUES ($uid,'$content',$anon)";
    $msg = $conn->query($sql) ? "Posted to Cycle Buddies." : "Error: ".$conn->error;
}

// Handle delete post
if(isset($_POST['delete_post'])){
    $post_id = intval($_POST['post_id']);
    // Only delete if the logged-in user is the owner
    $conn->query("DELETE FROM community_posts WHERE post_id=$post_id AND user_id=$uid");
    $msg = "Post deleted successfully.";
}

// Handle new comment
if(isset($_POST['add_comment'])){
    $post_id = intval($_POST['post_id']);
    $content = $conn->real_escape_string($_POST['comment_content']); 
    $anon = isset($_POST['anonymous']) ? 1 : 0;
    $sql = "INSERT INTO community_comments(post_id,user_id,content,anonymous_flag) 
            VALUES ($post_id,$uid,'$content',$anon)";
    $msg = $conn->query($sql) ? "Comment added." : "Error: ".$conn->error;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Community</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background: #f9f9fb; color: #333; }
    header { background: #6b46c1; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
    nav a { color: #fff; margin: 0 .6rem; text-decoration: none; font-weight: bold; }
    .container { max-width: 900px; margin: 1.5rem auto; padding: 1rem; }
    h3 { color: #6b46c1; margin-bottom: 0.5rem; }
    textarea { width: 100%; padding: .6rem; border-radius: 8px; border: 1px solid #ddd; margin-bottom: .5rem; }
    button { padding: .5rem 1rem; border: 0; border-radius: 6px; background: #6b46c1; color: #fff; cursor: pointer; margin-right:0.3rem; }
    button:hover { opacity: 0.85; }
    .post-card { background: #fff; padding: 1rem; margin-bottom: 1rem; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,.05); }
    .post-header { font-weight: bold; color: #6b46c1; margin-bottom: 0.3rem; display: flex; justify-content: space-between; align-items: center; }
    .post-date { font-size: 0.85rem; color: #777; }
    .post-content { margin: 0.5rem 0; }
    .success { background:#e6ffed;border-left:4px solid #22c55e;padding:.6rem;margin:.6rem 0;border-radius:6px; }
    .comment-box { margin-top:1rem; padding-left:1rem; border-left:3px solid #eee; }
    .comment { margin:0.3rem 0; }
    .comment span.name { color:#6b46c1; font-weight:bold; }
    .comment-date { font-size:0.8rem; color:#777; }
    .delete-btn { background:#6b46c1; }
  </style>
</head>
<body>

<header>
  <div><strong>Period Tracker & Mental Health Support Portal</strong></div>
  <nav>
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="cycles.php">Cycles</a>
    <a href="symptoms.php">Symptoms</a>
    <a href="activity.php">Activity</a>
    <a href="reminders.php">Reminders</a>
    <a href="community.php">Community</a>
    <a href="support.php">Support</a>
    <a href="report.php">Report</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<div class="container">
<?php if($msg) echo "<div class='success'>$msg</div>"; ?>

<h3>Cycle Buddies (Anonymous Community)</h3>
<form method="post">
    <textarea name="content" placeholder="Share something (tips, feelings, questions)" required></textarea>
    <label><input type="checkbox" name="anonymous" checked> Post anonymously</label><br><br>
    <button type="submit" name="add_post">Post</button>
</form>

<h3>Recent Posts</h3>

<?php
// Fetch all posts
$q = "SELECT cp.*, u.user_name 
      FROM community_posts cp 
      JOIN users u ON cp.user_id=u.user_id 
      ORDER BY cp.post_date DESC";
$res = $conn->query($q);

while($r = $res->fetch_assoc()){
    $by = $r['anonymous_flag'] ? 'Anonymous' : $r['user_name'];
    echo "<div class='post-card'>
            <div class='post-header'>
              <span>$by</span>
              <span class='post-date'>{$r['post_date']}</span>
            </div>
            <div class='post-content'>{$r['content']}</div>";

    // Delete button (only for post owner)
    if($r['user_id']==$uid){
        echo "<form method='post' style='display:inline;' onsubmit=\"return confirm('Are you sure you want to delete this post?');\">
                <input type='hidden' name='post_id' value='{$r['post_id']}'>
                <button name='delete_post' class='delete-btn'>Delete</button>
              </form>";
    }

    // Fetch comments for this post
    $comments = $conn->query("SELECT cc.*, u.user_name 
                              FROM community_comments cc 
                              JOIN users u ON cc.user_id=u.user_id 
                              WHERE cc.post_id={$r['post_id']} 
                              ORDER BY cc.comment_date ASC");

    echo "<div class='comment-box'><strong>Comments:</strong>";
    while($c = $comments->fetch_assoc()){
        $c_by = $c['anonymous_flag'] ? 'Anonymous' : $c['user_name'];
        echo "<div class='comment'>
                <span class='name'>$c_by</span>: {$c['content']}
                <span class='comment-date'>({$c['comment_date']})</span>
              </div>";
    }

    // Comment form (everyone can comment)
    echo "<form method='post' style='margin-top:0.5rem;'>
            <input type='hidden' name='post_id' value='{$r['post_id']}'>
            <textarea name='comment_content' rows='2' placeholder='Write a comment...' required></textarea>
            <label><input type='checkbox' name='anonymous' checked> Comment anonymously</label><br>
            <button type='submit' name='add_comment'>Comment</button>
          </form>";

    echo "</div>"; // comment-box end
    echo "</div>"; // post-card end
}
?>
</div>
</body>
</html>