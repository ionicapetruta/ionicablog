<?php
include_once 'db.inc.php';

class Comments
{
    public $db;
    public $comments;
    // Upon class instantiation, open a database connection
    /**
     *  The construct.
     *  Open a database connection and store it.
     */
    public function __construct()
    {
        $this->db = new PDO(DB_INFO, DB_USER, DB_PASS);
    }

    // Display a form for users to enter new comments with
    /**
     * Show comment form.
     *
     * @param $blog_id
     * @return string
     */
    public function showCommentForm($blog_id)
    {
        $errors = array(
            1 => '<p class="error">Something went wrong while '
                . 'saving your comment. Please try again!</p>',
            2 => '<p class="error">Please provide a valid '
                . 'email address!</p>',
            3 => '<p class="error">Please answer the anti-spam '
                . 'question correctly!</p>'
        );
        if (isset($_SESSION['error'])) {
            $error = $errors[$_SESSION['error']];
        } else {
            $error = null;
        }
        if (isset($_SESSION['c_name'])) {
            $n = $_SESSION['c_name'];
        } else {
            $n = null;
        }
        if (isset($_SESSION['c_email'])) {
            $e = $_SESSION['c_email'];
        } else {
            $e = null;
        }
        if (isset($_SESSION['c_comment'])) {
            $c = $_SESSION['c_comment'];
        } else {
            $c = null;
        }
        $challenge = $this->generateChallenge();

        return <<<FORM
        <form action="/inc/update.inc.php"
        method="post" id="comment-form">
            <fieldset>
               <legend>Post a Comment</legend>$error
                <label>Name
                    <input type="text" name="name" maxlength="75" value="$n" />
                </label>
                <label>Email
                   <input type="text" name="email" maxlength="150" value="$e" />
                </label>
                <label>Comment
                   <textarea rows="10" cols="45" name="comment">$c</textarea>
              </label>$challenge
                <input type="hidden" name="blog_id" value="$blog_id" />
                <input type="submit" name="submit" value="Post Comment" />
                <input type="submit" name="submit" value="Cancel" />
            </fieldset>
        </form>
FORM;
    }

    /**
     * Save comment to the database.
     *
     * @param  string $p
     * @return bool
     */
    public function saveComment($p)
    {
        $_SESSION['c_name'] = htmlentities($p['name'], ENT_QUOTES);
        $_SESSION['c_email'] = htmlentities($p['email'], ENT_QUOTES);
        $_SESSION['c_comment'] = htmlentities(
            $p['cmnt'],
            ENT_QUOTES
        );
        if ($this->validateEmail($p['email']) === false) {
            $_SESSION['error'] = 2;

            return;
        }
        if (!$this->verifyResponse($p['s_q'], $p['s_1'], $p['s_2'])) {
            $_SESSION['error'] = 3;

            return;
        }
        $blog_id = htmlentities(strip_tags($p['blog_id']), ENT_QUOTES);
        $name = htmlentities(strip_tags($p['name']), ENT_QUOTES);
        $email = htmlentities(strip_tags($p['email']), ENT_QUOTES);
        $comment = htmlentities(strip_tags($p['comment']), ENT_QUOTES);
        $comment = nl2br(trim($comment));
        $sql = "INSERT INTO comments (blog_id, name, email, comment)
VALUES (?, ?, ?, ?)";
        if ($stmt = $this->db->prepare($sql)) {
            $stmt->execute(array($blog_id, $name, $email, $comment));
            $stmt->closeCursor();
            unset($_SESSION['c_name'], $_SESSION['c_email'],
            $_SESSION['c_comment'], $_SESSION['error']);

            return true;
        } else {
            $_SESSION['error'] = 1;

            return;
        }
    }

    /**
     * This function check if the email is valid.
     *
     * @param $email
     * @return bool
     */
    private function validateEmail($email)
    {
        $p = '/^[\w-]+(\.[\w-]+)*@[a-z0-9-]+'
            . '(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i';

             return (preg_match($p, $email)) ? true : false;
    }

    // Load all comments for a blog entry into memory
    /**
     * This function load all comments for a blog entry into memory.
     *
     * @param $blog_id
     */
    public function retrieveComments($blog_id)
    {
        $sql = "SELECT id, name, email, comment, date
FROM comments
WHERE blog_id=?
ORDER BY date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($blog_id));
        // Loop through returned rows
        while ($comment = $stmt->fetch()) {
            // Store in memory for later use
            $this->comments[] = $comment;
        }
        // Set up a default response if no comments exist
        if (empty($this->comments)) {
            $this->comments[] = array(
                'id' => null,
                'name' => null,
                'email' => null,
                'comment' => "There are no comments on this entry.",
                'date' => null
            );
        }
    }

    /**
     * Generates HTML markup for displaying comments.
     *
     * @param $blog_id
     * @return null|string
     */
    public function showComments($blog_id)
    {
        $display = null;
        $this->retrieveComments($blog_id);
        foreach ($this->comments as $c) {
            if (!empty($c['date']) && !empty($c['name'])) {
                $format = "F j, Y \a\\t g:iA";
                $date = date($format, strtotime($c['date']));
                $byline = "<span><strong>$c[name]</strong>
                [Posted on $date]</span>";
                if (isset($_SESSION['loggedin'])
                    && $_SESSION['loggedin'] == 1
                ) {
                    $admin = "<a href=\"/simple_blog/inc/update.inc.php"
                        . "?action=comment_delete&id=$c[id]\""
                        . " class=\"admin\">delete</a>";
                } else {
                    $admin = null;
                }
            } else {
                $byline = null;
                $admin = null;
            }
            $display .= "<p class=\"comment\">$byline$c[comment]$admin</p>";
        }

        return $display;
    }

    /**
     * Ensure the user really wants to delete the comment.
     *
     * @param number $id
     * @return string
     */
    public function confirmDelete($id)
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        }
        else {
            $url = '../';
        }

        return <<<FORM
        <html>
        <head>
            <title>Please Confirm Your Decision</title>
            <link rel="stylesheet" type="text/css"
            href="/simple_blog/css/default.css" />
        </head>
        <body>
            <form action="/inc/update.inc.php" method="post">
                <fieldset>
                    <legend>Are You Sure?</legend>
                    <p>
                        Are you sure you want to delete this comment?
                    </p>
                    <input type="hidden" name="id" value="$id" />
                    <input type="hidden" name="action" value="comment_delete" />
                    <input type="hidden" name="url" value="$url" />
                    <input type="submit" name="confirm" value="Yes" />
                    <input type="submit" name="confirm" value="No" />
                </fieldset>
            </form>
        </body>
        </html>
FORM;
    }

    // Removes the comment corresponding to $id from the database
    /**
     * Delete comment.
     *
     * @param number $id
     * @return bool
     */
    public function deleteComment($id)
    {
        $sql = "DELETE FROM comments
WHERE id=?
LIMIT 1";
        if ($stmt = $this->db->prepare($sql)) {
            $stmt->execute(array($id));
            $stmt->closeCursor();

            return true;
        } else {
            return false;
        }
    }

    /**
     * This function generate challange.
     *
     * @return string
     */
    private function generateChallenge()
    {
        $numbers = array(mt_rand(1, 4), mt_rand(1, 4));
        $_SESSION['challenge'] = $numbers[0] + $numbers[1];
        $converted = array_map('ord', $numbers);

        return "
        <label>&#87;&#104;&#97;&#116;&#32;&#105;&#115;&#32;
                 &#$converted[0];&#32;&#43;&#32;&#$converted[1];&#63;
            <input type=\"text\" name=\"s_q\" />
        </label>";
    }

    /**
     * Check response.
     *
     * @param string $resp
     * @return bool
     */
    private function verifyResponse($resp)
    {
        $val = $_SESSION['challenge'];
        unset($_SESSION['challenge']);

        return $resp == $val;
    }
}
?>