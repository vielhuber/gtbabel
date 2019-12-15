<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, minimum-scale=1" />
    <title>.</title>
    <link rel="stylesheet" href="http://yui.yahooapis.com/3.4.1/build/cssreset/cssreset-min.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="style.css" />
	<script src="script.js"></script> 
    <script>
    $(document).ready(function()
    {

    });
    </script>
    <style>
	*
    {
		box-sizing: border-box;
	}
    </style>
</head>
<body>

    <div>
    <?php for ($i = 0; $i < 5000; $i++) {
        echo '<strong>';
        echo 'Dies ist ein ' . $i . ' Test!';
        echo '</strong>';
    } ?>
    </div>

</body>
</html>