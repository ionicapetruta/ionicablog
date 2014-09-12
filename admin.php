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

<form method="post" action="inc/update.inc.php">
    <fieldset>
        <legend>New Entry Submission</legend>
        <label>Title
            <h1>
                <input type="text" name="title" maxlength="150"/>
            </h1>
        </label>
        <label>Entry
            <h1>
                <textarea name="entry" cols="45" rows="10"></textarea>
            </h1>
        </label>
        <input type="submit" name="submit" value="Save Entry"/>
        <input type="submit" name="submit" value="Cancel"/>
    </fieldset>
</form>
</body>
</html>

