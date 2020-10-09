<?php
if (!file_exists('log.txt')) {
    die('log missing');
}
$content = file_get_contents('log.txt');
$content = explode('##############################################', $content);
foreach (array_diff(scandir('.'), ['.', '..']) as $files__value) {
    if (strpos($files__value, '.html') === false) {
        continue;
    }
    @unlink($files__value);
}
foreach ($content as $content__key => $content__value) {
    if ($content__key % 2 === 0) {
        continue;
    }
    $log = json_decode(trim($content__value));
    $filename_result = ($content__key + 1) / 2 . '_result.html';
    $filename_expected = ($content__key + 1) / 2 . '_expected.html';

    $log[0] = str_replace(' ', PHP_EOL, $log[0]);
    $log[1] = str_replace(' ', PHP_EOL, $log[1]);

    file_put_contents($filename_result, $log[0]);
    file_put_contents($filename_expected, $log[1]);

    //shell_exec('npx prettier --write ' . $filename_result);
    //shell_exec('npx prettier --write ' . $filename_expected);
}
echo 'done' . PHP_EOL;
die();
