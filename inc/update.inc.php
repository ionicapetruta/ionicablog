<?php
// Include the functions so you can create a URL
include_once 'functions.inc.php';
// Include the image handling class
include_once 'images.inc.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['submit'] == 'Save Entry'
    && !empty($_POST['page']) && !empty($_POST['title']) && !empty($_POST['entry'])

) {
    // Create a URL to save in the database
    $url = makeUrl($_POST['title']);
    if (isset($_FILES['image']['tmp_name'])) {
        try {
            // Instantiate the class and set a save dir
            $img = new ImageHandler("/simple_blog/images/");
            // Process the uploaded image and save the returned path
            $img_path = $img->processUploadedImage($_FILES['image']);
        } catch (Exception $e) {
            // If an error occurred, output your custom error message
            die($e->getMessage());
        }
    } else {
        // Avoids a notice if no image was uploaded
        $img_path = null;
    }
    // Include database credentials and connect to the database
    include_once 'db.inc.php';
    $db = new PDO(DB_INFO, DB_USER, DB_PASS);
    // Edit an existing entry
    if (!empty($_POST['id'])) {
        $sql = "UPDATE entries SET title=?, image=?, entry=?, url=?WHERE id=? LIMIT 1";
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
    } // Create a new entry
    else {
        // Save the entry into the database
        $sql = "INSERT INTO entries (page, title, image, entry, url) VALUES (?, ?, ?, ?, ?)";
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
    // Sanitize the page information for use in the success URL
    $page = htmlentities(strip_tags($_POST['page']));
    // Get the ID of the entry we just saved
    $id_obj = $db->query("SELECT LAST_INSERT_ID()");
    $id = $id_obj->fetch();
    $id_obj->closeCursor();
    // Send the user to the new entry
    header('Location: /' . $page . '/' . $url);
    exit;

} // If both conditions aren't met, sends the user back to the main page
// If a comment is being posted, handle it here
else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST'
        && $_POST['submit'] == 'Post Comment'
    ) {
        // Include and instantiate the Comments class
        include_once 'comments.inc.php';
        $comments = new Comments();
        // Save the comment
        if ($comments->saveComment($_POST)) {
            // If available, store the entry the user came from
            if (isset($_SERVER['HTTP_REFERER'])) {
                $loc = $_SERVER['HTTP_REFERER'];
            } else {
                $loc = '../';
            }
            // Send the user back to the entry
            header('Location: ' . $loc);
            exit;
        } // If saving fails, output an error message
        else {
            exit('Something went wrong while saving the comment.');
        }
    } // If the delete link is clicked on a comment, confirm it here
    else {
        if ($_GET['action'] == 'comment_delete') {
            // Include and instantiate the Comments class
            include_once 'comments.inc.php';
            $comments = new Comments();
            echo $comments->confirmDelete($_GET['id']);
            exit;
        } // If the confirmDelete() form was submitted, handle it here
        else {
            if ($_SERVER['REQUEST_METHOD'] == 'POST'
                && $_POST['action'] == 'comment_delete'
            ) {
                // If set, store the entry from which we came
                $loc = isset($_POST['url']) ? $_POST['url'] : '../';
                // If the user clicked "Yes", continue with deletion
                if ($_POST['confirm'] == "Yes") {
                    // Include and instantiate the Comments class
                    include_once 'comments.inc.php';
                    $comments = new Comments();
                    // Delete the comment and return to the entry
                    if ($comments->deleteComment($_POST['id'])) {
                        header('Location: ' . $loc);
                        exit;
                    } // If deleting fails, output an error message
                    else {
                        exit('Could not delete the comment.');
                    }
                } // If the user clicked "No", do nothing and return to the entry
                else {
                    header('Location: ' . $loc);
                    exit;
                }
            } else {
                header('Location: ../');
                exit;
            }
        }
    }
}
?>