<?php
session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1):
include_once 'inc/functions.inc.php';
include_once 'inc/db.inc.php';
$db = new PDO(DB_INFO, DB_USER, DB_PASS);
if (isset($_GET['page'])) {
    $page = htmlentities(strip_tags($_GET['page']));
} else {
    $page = 'blog';
}
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    if ($_POST['submit'] == 'Yes') {
        $url = htmlentities(strip_tags($_POST['url']));
        if (deleteEntry($db, $url)) {
            header("Location: /");
            exit;
        } else {
            exit("Error deleting the entry!");
        }
    } else {
        header("Location: /$url");
        exit;
    }
}
if (isset($_GET['url'])) {
    $url = htmlentities(strip_tags($_GET['url']));
    if ($page == 'delete') {
        $confirm = confirmDelete($db, $url);
    }
    $legend = "Edit This Entry";
    $e = retrieveEntries($db, $page, $url);
    $id = $e['id'];
    $title = $e['title'];
    $entry = $e['entry'];
} else {
    if ($page == 'createuser') {
        $create = createUserForm();
    }
    $legend = "New Entry Submission";
    $id = null;
    $title = null;
    $entry = null;
}
?>
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type"
          content="text/html;charset=utf-8"/>
    <link rel="stylesheet" href="/css/stiluri.css.css" type="text/css"/>
    <title> Simple Blog </title>
</head>
<body>
<h1> Simple Blog Application </h1>
<?php
if ($page == 'delete'):
{
    echo $confirm;
} elseif ($page == 'createuser'):
{
    echo $create;
} else:
    ?>
    <form method="post" action="/inc/update.inc.php"
          enctype="multipart/form-data">
        <fieldset>
            <legend><?php echo $legend ?></legend>
            <label>Title <br/>
                <input type="text" name="title" maxlength="150"
                       value="<?php echo htmlentities($title) ?>"/>
            </label>
            <br/>
            <label>Image <br/>
                <input type="file" name="image"/>
            </label>
            <br/>
            <label>Entry <br/>
                <textarea name="entry" cols="45"
                          rows="10"><?php echo sanitizeData(
                        $entry
                    ) ?></textarea>
            </label>
            <input type="hidden" name="id"
                   value="<?php echo $id ?>"/>
            <input type="hidden" name="page"
                   value="<?php echo $page ?>"/>
            <br/>
            <input type="submit" name="submit" value="Save Entry"/>
            <input type="submit" name="submit" value="Cancel"/>
        </fieldset>
    </form>
<?php endif; ?>
</body>
</html>
<?php
else:
?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type"
          content="text/html;charset=utf-8" />
    <link rel="stylesheet"
          href="/simple_blog/css/default.css" type="text/css" />
    <title> Please Log In </title>
</head>
<body>
<form method="post"
      action="/inc/update.inc.php"
      enctype="multipart/form-data">
    <fieldset>
        <legend>Please Log In To Continue</legend>
        <label>Username <br/>
            <input type="text" name="username" maxlength="75" />
        </label> <br/>
        <label>Password <br/>
            <input type="password" name="password"
                   maxlength="150" />
        </label> <br/>
        <input type="hidden" name="action" value="login" />
        <input type="submit" name="submit" value="Log In" />
    </fieldset>
</form>
</body>
</html>
<?php endif; ?>