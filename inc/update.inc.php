<?php
session_start();
include_once 'functions.inc.php';
include_once 'images.inc.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST'
    && $_POST['submit'] == 'Save Entry'
    && !empty($_POST['page'])
    && !empty($_POST['title'])
    && !empty($_POST['entry'])
) {
    $url = makeUrl($_POST['title']);
    if (strlen($_FILES['image']['tmp_name']) > 0) {
        try {
            $image = new ImageHandler("/simple_blog/images/");
            $img_path = $image->processUploadedImage($_FILES['image']);
        } catch (Exception $e) {
            die($e->getMessage());
        }
    } else {
        $img_path = null;
    }
    include_once 'db.inc.php';
    $db = new PDO(DB_INFO, DB_USER, DB_PASS);
    if (!empty($_POST['id'])) {
        $sql = "UPDATE entries
SET title=?, image=?, entry=?, url=?
WHERE id=?
LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(
            array(
                $_POST['title'],
                $img_path,
                $_POST['entry'],
                $url,
                $_POST['id']
            )
        );
        $stmt->closeCursor();
    }
    else {
        $sql = "INSERT INTO entries (page, title, image, entry, url)
VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(
            array(
                $_POST['page'],
                $_POST['title'],
                $img_path,
                $_POST['entry'],
                $url
            )
        );
        $stmt->closeCursor();
    }
    $page = htmlentities(strip_tags($_POST['page']));
    header('Location: /simple_blog/' . $page . '/' . $url);
    exit;
}
else
    if ($_SERVER['REQUEST_METHOD'] == 'POST'
        && $_POST['submit'] == 'Post Comment'
    ) {
        include_once 'comments.inc.php';
        $comments = new Comments();
        $comments->saveComment($_POST);
        if(isset($_SERVER['HTTP_REFERER']))
        {
            $loc = $_SERVER['HTTP_REFERER'];
        }
        else
        {
            $loc = '../';
        }
        header('Location: '.$loc);
        exit;
    }
    else {
        unset($_SESSION['c_name'], $_SESSION['c_email'],
        $_SESSION['c_comment'], $_SESSION['error']);
        header('Location: ../');
        exit;
        }
?>