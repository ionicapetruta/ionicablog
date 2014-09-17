<?php

/**
 * Retrieves entries from database.
 *
 * @param object $db
 * @param string $page
 * @param null|string $url
 * @return array|null
 */
function retrieveEntries($db, $page, $url = null)
{
    if (isset($url)) {
        $sql = "SELECT id, page, title, image, entry, created
FROM entries
WHERE url=?
LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($url));
        $e = $stmt->fetch();
        $fulldisp = 1;
    }
    else {
        $sql = "SELECT id, page, title, image, entry, url, created
FROM entries
WHERE page=?
ORDER BY created DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($page));
        $e = null;
        while ($row = $stmt->fetch()) {
            $e[] = $row;
            $fulldisp = 0;
        }
        if (!is_array($e)) {
            $fulldisp = 1;
            $e = array(
                'title' => 'No Entries Yet',
                'entry' => '<a href="/admin.php">Post an entry!</a>'
            );
        }
    }
    array_push($e, $fulldisp);
    return $e;
}

/**
 * Admin links.
 *
 * @param string $page
 * @param string $url
 * @return mixed
 */
function adminLinks($page, $url)
{
    $editURL = "/admin/$page/$url";
    $deleteURL = "/admin/delete/$url";
    $admin['edit'] = "<a href=\"$editURL\">edit</a>";
    $admin['delete'] = "<a href=\"$deleteURL\">delete</a>";

    return $admin;
}

/**
 * Sanitize data.
 *
 * @param string $data
 *   The data to be sanitized.
 * @return array|string
 *   The sanitized data.
 */
function sanitizeData($data)
{
    if (!is_array($data)) {
        return strip_tags($data, "<a>");
    }
    else {
        return array_map('sanitizeData', $data);
    }
}


/**
 * MakeUrl.
 * @param string $title
 * @return mixed
 */
function makeUrl($title)
{
    $patterns = array(
        '/\s+/',
        '/(?!-)\W+/'
    );
    $replacements = array('-', '');

    return preg_replace($patterns, $replacements, strtolower($title));
}

/**
 * Confirm the delete.
 *
 * @param database $db
 * @param string $url
 *  the URL of the entry to be deleted.
 * @return form
 */
function confirmDelete($db, $url)
{
    $e = retrieveEntries($db, '', $url);

    return <<<FORM
    <form action="/admin.php" method="post">
        <fieldset>
            <legend>Are You Sure?</legend>
            <p>Are you sure you want to delete the entry "$e[title]"?</p>
            <input type="submit" name="submit" value="Yes" />
            <input type="submit" name="submit" value="No" />
            <input type="hidden" name="action" value="delete" />
            <input type="hidden" name="url" value="$url" />
        </fieldset>
    </form>
FORM;
}

/**
 * Delete entry.
 *
 *This function place the url into a delete query.
 *
 * @param database $db
 * @param string $url
 * @return mixed
 */
function deleteEntry($db, $url)
{
    $sql = "DELETE FROM entries WHERE url=? LIMIT 1";
    $stmt = $db->prepare($sql);

    return $stmt->execute(array($url));
}

/**
 * Format images.
 *
 * @param null $img
 * @param null $alt
 * @return null|string
 */
function formatImage($img = null, $alt = null)
{
    if (isset($img)) {
        return '<img src="' . $img . '" alt="' . $alt . '" />';
    } else {
        return null;
    }
}

/**
 * Create an user form.
 *
 * @return string
 */
function createUserForm()
{
    return <<<FORM
    <form action="/inc/update.inc.php" method="post">
        <fieldset>
            <legend>Create a New Administrator</legend>
            <label>Username <br/>
                <input type="text" name="username" maxlength="75" />
            </label> <br/>
            <label>Password <br/>
                <input type="password" name="password" />
            </label> <br/>
            <input type="submit" name="submit" value="Create" />
            <input type="submit" name="submit" value="Cancel" />
            <input type="hidden" name="action" value="createuser" />
        </fieldset>
    </form>
FORM;
}

/**
 * Shorten url.
 *
 * @param $url
 */
function shortenUrl($url)
{
    $api = 'http://api.bit.ly/shorten';
    $param = 'version=2.0.1&longUrl=' . urlencode($url) . '&login=phpfab'
        . '&apiKey=R_7473a7c43c68a73ae08b68ef8e16388e&format=xml';
    $uri = $api . "?" . $param;
    $response = file_get_contents($uri);
    $bitly = simplexml_load_string($response);

    return $bitly->results->nodeKeyVal->shortUrl;
}

/**
 * Post to Twitter.
 *
 * @param $title
 * @return string
 */
function postToTwitter($title)
{
    $full = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $short = shortenUrl($full);
    $status = $title . ' ' . $short;

    return 'http://twitter.com/?status=' . urlencode($status);
}