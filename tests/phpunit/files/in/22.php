<!DOCTYPE html>
<html>
    <body>
        <div>
            <?php for ($i = 0; $i < 750; $i++) {
                echo '<strong>';
                echo 'Dies ist ein ' . $i . ' Test!';
                echo '</strong>';
            } ?>
        </div>
        <div>
            <?php for ($i = 0; $i < 750; $i++) {
                echo '<strong>';
                echo 'Dies ist ein ' . $i . ' Test!';
                echo '</strong>';
                echo 'Test';
            } ?>
        </div>
    </body>
</html>