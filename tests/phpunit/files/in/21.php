<!DOCTYPE html>
<html>
    <body>
        <?php for ($i = 0; $i < 2500; $i++) {
            for ($j = 0; $j < 10; $j++) {
                echo '<div>';
            }
            echo 'Dies ist ein ' . $i . ' Test!';
            for ($j = 0; $j < 10; $j++) {
                echo '</div>';
            }
        } ?>
    </body>
</html>