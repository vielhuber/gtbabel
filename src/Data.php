<?php
namespace vielhuber\gtbabel;

use vielhuber\stringhelper\__;
use vielhuber\dbhelper\dbhelper;

class Data
{
    public $data;
    public $db;
    public $table;

    public $utils;
    public $host;
    public $settings;
    public $tags;
    public $log;
    public $publish;

    function __construct(
        Utils $utils = null,
        Host $host = null,
        Settings $settings = null,
        Tags $tags = null,
        Log $log = null,
        Publish $publish = null
    ) {
        $this->utils = $utils ?: new Utils();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
        $this->tags = $tags ?: new Tags();
        $this->log = $log ?: new Log();
        $this->publish = $publish ?: new Publish();
    }

    function initDatabase()
    {
        /* we need to connect to the database and initialize the whole database (in case of sqlite) and table */
        /* performance here is not crucial: the following operations take ~2/1000s
         /* (so we can call them on every page load to avoid manually calling setup functions beforehand) */
        /* we chose a flat db structure to avoid expensive joins on every page load */
        /* we also add unique index so we can INSERT OR REPLACE later on */
        $this->db = new dbhelper();
        $db_settings = $this->settings->get('database');
        $this->table = $db_settings['table'];

        if ($db_settings['type'] === 'sqlite') {
            $filename = $db_settings['filename'];
            if (!file_exists($filename)) {
                file_put_contents($filename, '');
                $this->db->connect('pdo', 'sqlite', $filename);
                $this->db->query(
                    'CREATE TABLE IF NOT EXISTS ' .
                        $this->table .
                        '(
                            id INTEGER PRIMARY KEY AUTOINCREMENT, 
                            str TEXT NOT NULL,
                            context VARCHAR(20),
                            lng_source VARCHAR(20) NOT NULL,
                            lng_target VARCHAR(20) NOT NULL,
                            trans TEXT NOT NULL,
                            added TEXT NOT NULL,
                            checked INTEGER NOT NULL,
                            shared INTEGER NOT NULL,
                            discovered_last_time TEXT,
                            discovered_last_url_orig TEXT,
                            discovered_last_url TEXT
                        )'
                );
                $this->db->query(
                    'CREATE UNIQUE INDEX ' .
                        $this->table .
                        '_idx ON ' .
                        $this->table .
                        '(str, context, lng_source, lng_target)'
                );
            } else {
                $this->db->connect('pdo', 'sqlite', $filename);
            }
        } else {
            if (isset($db_settings['port'])) {
                $port = $db_settings['port'];
            } elseif ($db_settings['type'] === 'mysql') {
                $port = 3306;
            } elseif ($db_settings['type'] === 'postgres') {
                $port = 5432;
            }
            $this->db->connect(
                'pdo',
                $db_settings['type'],
                $db_settings['host'],
                $db_settings['username'],
                $db_settings['password'],
                $db_settings['database'],
                $port
            );
            $this->db->query(
                'CREATE TABLE IF NOT EXISTS ' .
                    $this->table .
                    '(
                        id BIGINT PRIMARY KEY AUTO_INCREMENT, 
                        str TEXT NOT NULL,
                        context VARCHAR(20),
                        lng_source VARCHAR(20) NOT NULL,
                        lng_target VARCHAR(20) NOT NULL,
                        trans TEXT NOT NULL,
                        added TEXT NOT NULL,
                        checked TINYINT NOT NULL,
                        shared TINYINT NOT NULL,
                        discovered_last_time TEXT,
                        discovered_last_url_orig TEXT,
                        discovered_last_url TEXT
                    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            try {
                $this->db->query(
                    'CREATE UNIQUE INDEX ' .
                        $this->table .
                        '_str_context_lng ON ' .
                        $this->table .
                        '(str(100), context, lng_source, lng_target)'
                );
            } catch (\Exception $e) {
            }
        }
    }

    function preloadDataInCache()
    {
        $this->data = [
            'cache' => [],
            'cache_reverse' => [],
            'checked_strings' => [],
            'save' => []
        ];

        if ($this->db !== null) {
            $result = $this->db->fetch_all('SELECT * FROM ' . $this->table . '');
            if (!empty($result)) {
                foreach ($result as $result__value) {
                    $this->data['cache'][$result__value['lng_source'] ?? ''][$result__value['lng_target'] ?? ''][
                        $result__value['context'] ?? ''
                    ][$result__value['str']] = $result__value['trans'];
                    $this->data['cache_reverse'][$result__value['lng_source'] ?? ''][
                        $result__value['lng_target'] ?? ''
                    ][$result__value['context'] ?? ''][$result__value['trans']] = $result__value['str'];
                    $this->data['checked_strings'][$result__value['lng_source'] ?? ''][
                        $result__value['lng_target'] ?? ''
                    ][$result__value['context'] ?? ''][$result__value['str']] =
                        $result__value['checked'] == '1' ? true : false;
                }
            }
        }
    }

    function saveCacheToDatabase()
    {
        if ($this->settings->get('auto_add_translations') === false) {
            return;
        }

        $date = $this->utils->getCurrentTime();
        $discovered_last_url_orig = $this->host->getCurrentUrlWithArgsConverted();
        $discovered_last_url = $this->host->getCurrentUrlWithArgs();
        foreach (['discovered_last_url_orig', 'discovered_last_url'] as $url__value) {
            // extract path
            ${$url__value} = str_replace($this->host->getCurrentHost(), '', ${$url__value});
            // strip server sided requests initiated by auto translation
            $pos = strpos(${$url__value}, '?');
            if ($pos !== false) {
                $args = explode('&', substr(${$url__value}, $pos + 1));
                foreach ($args as $args__key => $args__value) {
                    if (strpos($args__value, 'gtbabel_') === 0) {
                        unset($args[$args__key]);
                    }
                }
                ${$url__value} = substr(${$url__value}, 0, $pos);
                if (!empty($args)) {
                    ${$url__value} .= '?' . implode('&', $args);
                }
            }
            // trim
            ${$url__value} = '/' . trim(${$url__value}, '/');
        }

        // insert batch wise (because sqlite has limits)
        if (!empty($this->data['save']['insert'])) {
            $batch_size = 100;
            for ($batch_cur = 0; $batch_cur * $batch_size < count($this->data['save']['insert']); $batch_cur++) {
                $query = '';
                $query .= 'INSERT';
                if ($this->db->sql->engine === 'sqlite') {
                    $query .= ' OR REPLACE';
                } else {
                    $query .= ' IGNORE';
                }
                $query .=
                    ' INTO ' .
                    $this->table .
                    ' (str, context, lng_source, lng_target, trans, added, checked, shared, discovered_last_time, discovered_last_url_orig, discovered_last_url) VALUES ';
                $query_q = [];
                $query_a = [];
                foreach ($this->data['save']['insert'] as $save__key => $save__value) {
                    if ($save__key < $batch_size * $batch_cur || $save__key >= $batch_size * ($batch_cur + 1)) {
                        continue;
                    }
                    $query_q[] = '(?,?,?,?,?,?,?,?,?,?,?)';
                    $query_a = array_merge($query_a, [
                        $save__value['str'],
                        $save__value['context'],
                        $save__value['lng_source'],
                        $save__value['lng_target'],
                        $save__value['trans'],
                        $date,
                        $save__value['checked'],
                        $save__value['shared'],
                        $date,
                        $discovered_last_url_orig,
                        $discovered_last_url
                    ]);
                }
                $query .= implode(',', $query_q);
                $this->db->query($query, $query_a);
            }
        }
        if (!empty($this->data['save']['discovered'])) {
            $batch_size = 100;
            for ($batch_cur = 0; $batch_cur * $batch_size < count($this->data['save']['discovered']); $batch_cur++) {
                $query =
                    '
                    UPDATE ' .
                    $this->table .
                    ' SET
                    shared = (CASE WHEN discovered_last_url_orig <> ? THEN 1 ELSE shared END),
                    ' .
                    ($this->settings->get('auto_set_discovered_strings_checked') === true ? 'checked = 1,' : '') .
                    '
                    discovered_last_time = ?,
                    discovered_last_url_orig = ?,
                    discovered_last_url = ?
                    WHERE
                ';
                $query_q = [];
                $query_a = [];
                $query_a = array_merge($query_a, [
                    $discovered_last_url_orig,
                    $date,
                    $discovered_last_url_orig,
                    $discovered_last_url
                ]);
                foreach ($this->data['save']['discovered'] as $save__key => $save__value) {
                    if ($save__key < $batch_size * $batch_cur || $save__key >= $batch_size * ($batch_cur + 1)) {
                        continue;
                    }
                    $query_q[] =
                        $this->caseSensitiveCol('str') . ' = ? AND context = ? AND lng_source = ? AND lng_target = ?';
                    $query_a = array_merge($query_a, [
                        $save__value['str'],
                        $save__value['context'],
                        $save__value['lng_source'],
                        $save__value['lng_target']
                    ]);
                }
                $query .= '(' . implode(') OR (', $query_q) . ')';
                $this->db->query($query, $query_a);
            }
        }
    }

    function caseSensitiveCol($col)
    {
        if ($this->db->sql->engine === 'sqlite') {
            return $col;
        }
        return 'BINARY ' . $col;
    }

    function trackDiscovered($str, $lng_source, $lng_target, $context = null)
    {
        if (!($this->settings->get('discovery_log') == '1')) {
            return;
        }
        if ($this->host->currentUrlIsExcluded()) {
            return;
        }
        $this->data['save']['discovered'][] = [
            'str' => $str,
            'context' => $context,
            'lng_source' => $lng_source,
            'lng_target' => $lng_target
        ];
    }

    function getExistingTranslationFromCache($str, $lng_source, $lng_target, $context = null)
    {
        $this->trackDiscovered($str, $lng_source, $lng_target, $context);
        if (
            $str === '' ||
            $str === null ||
            !array_key_exists($lng_source, $this->data['cache']) ||
            !array_key_exists($lng_target, $this->data['cache'][$lng_source]) ||
            !array_key_exists($context ?? '', $this->data['cache'][$lng_source][$lng_target]) ||
            !array_key_exists($str, $this->data['cache'][$lng_source][$lng_target][$context ?? '']) ||
            $this->data['cache'][$lng_source][$lng_target][$context ?? ''][$str] === ''
        ) {
            return false;
        }
        return $this->data['cache'][$lng_source][$lng_target][$context ?? ''][$str];
    }

    function getExistingTranslationReverseFromCache($str, $lng_source, $lng_target, $context = null)
    {
        if (
            $str === '' ||
            $str === null ||
            !array_key_exists($lng_source, $this->data['cache_reverse']) ||
            !array_key_exists($lng_target, $this->data['cache_reverse'][$lng_source]) ||
            !array_key_exists($context ?? '', $this->data['cache_reverse'][$lng_source][$lng_target]) ||
            !array_key_exists($str, $this->data['cache_reverse'][$lng_source][$lng_target][$context ?? '']) ||
            $this->data['cache_reverse'][$lng_source][$lng_target][$context ?? ''][$str] === ''
        ) {
            return false;
        }
        return $this->data['cache_reverse'][$lng_source][$lng_target][$context ?? ''][$str];
    }

    function getTranslationFromDatabase($str, $context = null, $lng_source = null, $lng_target = null)
    {
        return $this->db->fetch_row(
            'SELECT * FROM ' .
                $this->table .
                ' WHERE ' .
                $this->caseSensitiveCol('str') .
                ' = ? AND context = ? AND lng_source = ? AND lng_target = ?',
            $str,
            $context,
            $lng_source,
            $lng_target
        );
    }

    function getTranslationsFromDatabase()
    {
        return $this->db->fetch_all('SELECT * FROM ' . $this->table . ' ORDER BY id ASC');
    }

    function getGroupedTranslationsFromDatabase($lng_source, $lng_target = null, $order_by_string = true)
    {
        $data = [];

        /* the following approach is (surprisingly) much faster than a group by / join of a lot of columns via sql */
        $query = 'SELECT * FROM ' . $this->table . ' WHERE lng_source = ?';
        $query_args = [];
        $query_args[] = $lng_source;
        if ($lng_target !== null) {
            $query .= ' AND lng_target = ?';
            $query_args[] = $lng_target;
        }
        $result = $this->db->fetch_all($query, $query_args);
        $data_grouped = [];
        if (!empty($result)) {
            foreach ($result as $result__value) {
                $data_grouped[$result__value['str']][$result__value['context']]['str'] = $result__value['str'];
                $data_grouped[$result__value['str']][$result__value['context']]['context'] = $result__value['context'];
                if (!isset($data_grouped[$result__value['str']][$result__value['context']]['shared'])) {
                    $data_grouped[$result__value['str']][$result__value['context']]['shared'] = 0;
                }
                if ($result__value['shared'] == 1) {
                    $data_grouped[$result__value['str']][$result__value['context']]['shared'] = 1;
                }
                $data_grouped[$result__value['str']][$result__value['context']][
                    $result__value['lng_target'] . '_trans'
                ] = $result__value['trans'];
                $data_grouped[$result__value['str']][$result__value['context']][
                    $result__value['lng_target'] . '_added'
                ] = $result__value['added'];
                $data_grouped[$result__value['str']][$result__value['context']][
                    $result__value['lng_target'] . '_checked'
                ] = $result__value['checked'];
                $data_grouped[$result__value['str']][$result__value['context']][
                    $result__value['lng_target'] . '_shared'
                ] = $result__value['shared'];
                $data_grouped[$result__value['str']][$result__value['context']][
                    $result__value['lng_target'] . '_discovered_last_time'
                ] = $result__value['discovered_last_time'];
                $data_grouped[$result__value['str']][$result__value['context']][
                    $result__value['lng_target'] . '_discovered_last_url_orig'
                ] = $result__value['discovered_last_url_orig'];
                $data_grouped[$result__value['str']][$result__value['context']][
                    $result__value['lng_target'] . '_discovered_last_url'
                ] = $result__value['discovered_last_url'];
            }
        }
        foreach ($data_grouped as $data_grouped__value) {
            foreach ($data_grouped__value as $data_grouped__value__value) {
                $data[] = $data_grouped__value__value;
            }
        }

        usort($data, function ($a, $b) use ($order_by_string) {
            /*
            order_by_string = true (url is not set)
                context
                str
            order_by_string = false (url is set)
                shared
                context
                order
            */
            if ($order_by_string === false) {
                if ($a['shared'] !== $b['shared']) {
                    return $a['shared'] < $b['shared'] ? -1 : 1;
                }
            }
            if ($a['context'] != $b['context']) {
                if ($a['context'] == '') {
                    return -1;
                }
                if ($b['context'] == '') {
                    return 1;
                }
                if ($a['context'] === 'slug') {
                    return -1;
                }
                if ($b['context'] === 'slug') {
                    return 1;
                }
                if ($a['context'] === 'title') {
                    return -1;
                }
                if ($b['context'] === 'title') {
                    return 1;
                }
                if ($a['context'] === 'description') {
                    return -1;
                }
                if ($b['context'] === 'description') {
                    return 1;
                }
                return strnatcasecmp($a['context'], $b['context']);
            }
            if ($order_by_string === true) {
                return strnatcasecmp($a['str'], $b['str']);
            } else {
                return strcmp($a['order'], $b['order']);
            }
        });
        return $data;
    }

    function editTranslation(
        $str,
        $context = null,
        $lng_source,
        $lng_target,
        $trans = null,
        $checked = null,
        $shared = null,
        $added = null,
        $discovered_last_time = null,
        $discovered_last_url_orig = null,
        $discovered_last_url = null
    ) {
        $success = false;

        // slug collission detection
        if ($context === 'slug' && $trans != '') {
            $counter = 2;
            while (
                $this->db->fetch_var(
                    'SELECT COUNT(*) FROM ' .
                        $this->table .
                        ' WHERE str <> ? AND context = ? AND lng_source = ? AND lng_target = ? AND trans = ?',
                    $str,
                    $context,
                    $lng_source,
                    $lng_target,
                    $trans
                ) > 0
            ) {
                if ($counter > 2) {
                    $trans = mb_substr($trans, 0, mb_strrpos($trans, '-'));
                }
                $trans .= '-' . $counter;
                $counter++;
            }
        }

        // get existing
        $gettext = $this->db->fetch_row(
            'SELECT * FROM ' .
                $this->table .
                ' WHERE ' .
                $this->caseSensitiveCol('str') .
                ' = ? AND context = ? AND lng_source = ? AND lng_target = ?',
            $str,
            $context,
            $lng_source,
            $lng_target
        );

        if (!empty($gettext)) {
            // delete
            if ($trans === '') {
                $this->db->delete($this->table, ['id' => $gettext['id']]);
            }
            // update
            else {
                if ($trans !== null) {
                    $this->db->update($this->table, ['trans' => $trans], ['id' => $gettext['id']]);
                }
                foreach (['checked', 'shared'] as $cols__value) {
                    if (${$cols__value} !== null) {
                        $this->db->update(
                            $this->table,
                            [$cols__value => ${$cols__value} === true || ${$cols__value} == 1 ? 1 : 0],
                            ['id' => $gettext['id']]
                        );
                    }
                }
                foreach (
                    ['added', 'discovered_last_time', 'discovered_last_url_orig', 'discovered_last_url']
                    as $cols__value
                ) {
                    if (${$cols__value} !== null) {
                        $this->db->update($this->table, [$cols__value => ${$cols__value}], ['id' => $gettext['id']]);
                    }
                }
            }
            $success = true;
        }

        // create
        else {
            $this->db->insert($this->table, [
                'str' => $str,
                'context' => $context,
                'lng_source' => $lng_source,
                'lng_target' => $lng_target,
                'trans' => $trans ?? '',
                'added' => $added ?? $this->utils->getCurrentTime(),
                'checked' => $checked === true || $checked == 1 ? 1 : 0,
                'shared' => $shared === true || $shared == 1 ? 1 : 0,
                'discovered_last_time' => $discovered_last_time,
                'discovered_last_url_orig' => $discovered_last_url_orig,
                'discovered_last_url' => $discovered_last_url
            ]);
            $success = true;
        }

        return $success;
    }

    function setAllStringsToChecked()
    {
        $this->db->query('UPDATE ' . $this->table . ' SET checked = ?', 1);
        return true;
    }

    function editCheckedValue($str, $context = null, $lng_source, $lng_target, $checked)
    {
        $this->db->query(
            'UPDATE ' .
                $this->table .
                ' SET checked = ? WHERE ' .
                $this->caseSensitiveCol('str') .
                ' = ? AND context = ? AND lng_source = ? AND lng_target = ?',
            $checked === true ? 1 : 0,
            $str,
            $context,
            $lng_source,
            $lng_target
        );
        return true;
    }

    function resetSharedValues()
    {
        $this->db->query('UPDATE ' . $this->table . ' SET shared = ?', 0);
    }

    function deleteStringFromDatabase($str, $context, $lng_source, $lng_target = null)
    {
        $args = [];
        $args['str'] = $str;
        $args['context'] = $context;
        $args['lng_source'] = $lng_source;
        if ($lng_target !== null) {
            $args['lng_target'] = $lng_target;
        }
        $this->db->delete($this->table, $args);
        return true;
    }

    function addTranslationToDatabaseAndToCache($str, $trans, $lng_source, $lng_target, $context = null)
    {
        if ($lng_target === $this->settings->getSourceLanguageCode()) {
            return;
        }
        $this->data['save']['insert'][] = [
            'str' => $str,
            'context' => $context,
            'lng_source' => $lng_source,
            'lng_target' => $lng_target,
            'trans' => $trans,
            'checked' => $this->settings->get('auto_set_new_strings_checked') === true ? 1 : 0,
            'shared' => 0
        ];
        $this->data['cache'][$lng_source][$lng_target][$context ?? ''][$str] = $trans;
        $this->data['cache_reverse'][$lng_source][$lng_target][$context ?? ''][$trans] = $str;
    }

    function resetTranslations()
    {
        if ($this->db->sql->engine === 'sqlite') {
            @unlink($this->settings->get('database')['filename']);
        } else {
            $this->db->delete_table($this->table);
        }
    }

    function clearTable($lng_source = null, $lng_target = null)
    {
        if ($lng_source === null && $lng_target === null) {
            $this->db->clear($this->table);
        } else {
            $args = [];
            if ($lng_source !== null) {
                $args['lng_source'] = $lng_source;
            }
            if ($lng_target !== null) {
                $args['lng_target'] = $lng_target;
            }
            $this->db->delete($this->table, $args);
        }
    }

    function discoveryLogGet($time = null, $after = true, $url = null, $slim_output = true, $delete = false)
    {
        if ($this->db === null) {
            return;
        }
        $urls = null;
        if ($url !== null) {
            if (is_array($url)) {
                $urls = $url;
            }
            if (is_string($url)) {
                $urls = [$url];
            }
        }
        if ($urls !== null) {
            $current_host = $this->host->getCurrentHost();
            $urls = array_map(function ($urls__value) use ($current_host) {
                return '/' . trim(str_replace($current_host, '', $urls__value), '/');
            }, $urls);
        }
        $query = '';
        if ($delete === false) {
            $query .= 'SELECT';
            if ($slim_output === false) {
                $query .= ' *';
            } else {
                $query .= ' DISTINCT str, context';
            }
        } else {
            $query .= 'DELETE';
        }
        $query .= ' FROM ' . $this->table . ' WHERE 1=1';
        $args = [];
        if ($urls !== null) {
            $query .= ' AND discovered_last_url IN (' . str_repeat('?,', count($urls) - 1) . '?)';
            $args = array_merge($args, $urls);
        }
        if ($time !== null) {
            $query .= ' AND discovered_last_time ' . ($after === true ? '>=' : '<') . ' ?';
            $args[] = $time;
        }
        if ($delete === false) {
            $query .= ' ORDER BY context ASC, discovered_last_time ASC';
        }

        if ($delete === false) {
            return $this->db->fetch_all($query, $args);
        } else {
            return $this->db->query($query, $args);
        }
    }

    function discoveryLogGetAfter($time = null, $url = null, $slim_output = true)
    {
        return $this->discoveryLogGet($time, true, $url, $slim_output, false);
    }

    function discoveryLogGetBefore($time = null, $url = null, $slim_output = true)
    {
        return $this->discoveryLogGet($time, false, $url, $slim_output, false);
    }

    function discoveryLogDeleteAfter($time = null, $url = null)
    {
        return $this->discoveryLogGet($time, true, $url, false, true);
    }

    function discoveryLogDeleteBefore($time = null, $url = null)
    {
        return $this->discoveryLogGet($time, false, $url, false, true);
    }

    function getCurrentPrefix()
    {
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if (
                $this->host->getCurrentPath() === $languages__value ||
                mb_strpos($this->host->getCurrentPath(), '/' . $languages__value . '/') === 0
            ) {
                return $languages__value;
            }
        }
        return null;
    }

    function getCurrentLanguageCode()
    {
        if ($this->settings->get('lng_target') !== null) {
            return $this->settings->get('lng_target');
        }
        return $this->getCurrentPrefix() ?? $this->settings->getSourceLanguageCode();
    }

    function getBrowserLng()
    {
        if (@$_SERVER['HTTP_ACCEPT_LANGUAGE'] != '') {
            foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
                if (mb_strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], $languages__value) === 0) {
                    return $languages__value;
                }
            }
        }
        return $this->settings->getSourceLanguageCode();
    }

    function getPrefixFromUrl($url)
    {
        $path = str_replace($this->host->getCurrentHost(), '', $url);
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if ($path === $languages__value || mb_strpos($path, '/' . $languages__value . '/') === 0) {
                return $languages__value;
            }
        }
        return null;
    }

    function getLngFromUrl($url)
    {
        return $this->getPrefixFromUrl($url) ?? $this->settings->getSourceLanguageCode();
    }

    function getLanguagePickerData()
    {
        $data = [];
        foreach ($this->settings->getSelectedLanguageCodesLabels() as $languages__key => $languages__value) {
            if (!$this->host->responseCodeIsSuccessful()) {
                continue;
            }
            $url = $this->getUrlTranslationInLanguage($this->getCurrentLanguageCode(), $languages__key);
            if (
                $this->publish->isActive() &&
                $this->publish->isPrevented($this->host->getCurrentUrl(), $languages__key)
            ) {
                continue;
            }
            $data[] = [
                'code' => $languages__key,
                'label' => $languages__value,
                'url' => $url,
                'active' => rtrim($url, '/') === rtrim($this->host->getCurrentUrlWithArgs(), '/')
            ];
        }
        return $data;
    }

    function sourceLngIsCurrentLng()
    {
        if ($this->getCurrentLanguageCode() === $this->settings->getSourceLanguageCode()) {
            return true;
        }
        return false;
    }

    function prepareTranslationAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target, $context = null)
    {
        $context = $this->autoDetermineContext($orig, $context);

        if (($context === 'slug' || $context === 'file') && $this->host->urlIsExcluded($orig)) {
            return null;
        }

        if ($this->sourceLngIsCurrentLng() && $this->settings->getSourceLanguageCode() === $lng_target) {
            if ($context !== 'slug') {
                return null;
            } else {
                return $this->addPrefixToLink($orig, $lng_source, $lng_target);
            }
        }

        if ($context === 'slug') {
            $trans = $this->getTranslationOfLinkHrefAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target);
            if ($trans === null) {
                return $orig;
            }
            return $trans;
        }
        if ($context === 'file') {
            $trans = $this->getTranslationOfFileAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target);
            if ($trans === null) {
                return $orig;
            }
            return $trans;
        }
        if ($context === 'email') {
            $trans = $this->getTranslationOfEmailAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target);
            if ($trans === null) {
                return $orig;
            }
            return $trans;
        }
        if ($context === 'title') {
            $trans = $this->getTranslationOfTitleAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target);
            if ($trans === null) {
                return $orig;
            }
            return $trans;
        }

        if ($this->stringShouldNotBeTranslated($orig, $context)) {
            return null;
        }
        return $this->getTranslationAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target, $context);
    }

    function getTranslationOfTitleAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target)
    {
        $orig = str_replace(' ', ' ', $orig); // replace hidden &nbsp; chars
        $orig = html_entity_decode($orig);
        foreach (['|', '·', '•', '>', '-', '–', '—', ':', '*', '⋆', '~', '«', '»', '<'] as $delimiters__value) {
            if (mb_strpos($orig, ' ' . $delimiters__value . ' ') !== false) {
                $orig_parts = explode(' ' . $delimiters__value . ' ', $orig);
                foreach ($orig_parts as $orig_parts__key => $orig_parts__value) {
                    $trans = $this->getTranslationAndAddDynamicallyIfNeeded(
                        $orig_parts__value,
                        $lng_source,
                        $lng_target,
                        'title'
                    );
                    $orig_parts[$orig_parts__key] = $trans;
                }
                $trans = implode(' ' . $delimiters__value . ' ', $orig_parts);
                return $trans;
            }
        }
        return $this->getTranslationAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target, 'title');
    }

    function addPrefixToLink($link, $lng_source, $lng_target)
    {
        return $this->addPrefixToLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded(
            $link,
            $lng_source,
            $lng_target,
            false
        );
    }

    function getTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng_source, $lng_target)
    {
        return $this->addPrefixToLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded(
            $link,
            $lng_source,
            $lng_target,
            true
        );
    }

    function addPrefixToLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded(
        $link,
        $lng_source,
        $lng_target,
        $translate
    ) {
        if ($link === null || trim($link) === '') {
            return $link;
        }
        if (mb_strpos(trim($link, '/'), '#') === 0) {
            return $link;
        }
        if (mb_strpos(trim($link, '/'), '?') === 0) {
            return $link;
        }
        if (mb_strpos(trim($link, '/'), '&') === 0) {
            return $link;
        }
        $is_absolute_link = mb_strpos($link, $this->host->getCurrentHost()) === 0;
        if (mb_strpos($link, 'http') !== false && $is_absolute_link === false) {
            return $link;
        }
        if (mb_strpos($link, 'http') === false && mb_strpos($link, ':') !== false) {
            return $link;
        }

        // replace host/lng
        foreach (
            [
                $this->host->getCurrentHost() . '/' . $this->settings->getSourceLanguageCode(),
                $this->host->getCurrentHost(),
                '/' . $this->settings->getSourceLanguageCode(),
                $this->settings->getSourceLanguageCode()
            ]
            as $begin__value
        ) {
            if (mb_strpos($link, $begin__value) === 0) {
                $link = str_replace($begin__value, '', $link);
            }
        }

        if ($translate === true) {
            $url_parts = explode('/', $link);
            foreach ($url_parts as $url_parts__key => $url_parts__value) {
                if ($this->stringShouldNotBeTranslated($url_parts__value, 'slug')) {
                    continue;
                }
                $url_parts[$url_parts__key] = $this->getTranslationAndAddDynamicallyIfNeeded(
                    $url_parts__value,
                    $lng_source,
                    $lng_target,
                    'slug'
                );
            }
            $link = implode('/', $url_parts);
        }
        $link = (mb_strpos($link, '/') === 0 ? '/' : '') . $lng_target . '/' . ltrim($link, '/');
        if ($is_absolute_link === true) {
            $link = rtrim($this->host->getCurrentHost(), '/') . '/' . ltrim($link, '/');
        }
        return $link;
    }

    function getTranslationOfFileAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target)
    {
        $urls = [];
        // extract urls from style tag
        if (strpos($orig, 'url(') !== false) {
            preg_match_all('/url\((.+?)\)/', $orig, $matches);
            foreach ($matches[1] as $matches__value) {
                $urls[] = trim(trim($matches__value, '\''), '"');
            }
        } else {
            $urls[] = $orig;
        }
        foreach ($urls as $urls__value) {
            // always submit relative urls
            foreach (
                [
                    $this->host->getCurrentHost() . '/' . $this->settings->getSourceLanguageCode(),
                    $this->host->getCurrentHost(),
                    '/' . $this->settings->getSourceLanguageCode(),
                    $this->settings->getSourceLanguageCode()
                ]
                as $begin__value
            ) {
                if (strpos($urls__value, $begin__value) === 0) {
                    $urls__value = str_replace($begin__value, '', $urls__value);
                }
            }
            $urls__value = trim($urls__value, '/');
            // skip external files
            if (strpos($urls__value, 'http') === 0 && strpos($urls__value, $this->host->getCurrentHost()) === false) {
                continue;
            }
            if ($this->stringShouldNotBeTranslated($urls__value, 'file')) {
                continue;
            }
            $trans = $this->getExistingTranslationFromCache($urls__value, $lng_source, $lng_target, 'file');
            if ($trans === false) {
                $this->addTranslationToDatabaseAndToCache($urls__value, $urls__value, $lng_source, $lng_target, 'file');
            } elseif ($this->stringIsChecked($urls__value, $lng_source, $lng_target, 'file')) {
                $orig = str_replace($urls__value, $trans, $orig);
            }
        }
        return $orig;
    }

    function getTranslationOfEmailAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target)
    {
        $is_link = strpos($orig, 'mailto:') === 0;
        if ($is_link) {
            $orig = str_replace('mailto:', '', $orig);
        }
        $trans = $this->getExistingTranslationFromCache($orig, $lng_source, $lng_target, 'email');
        if ($trans === false) {
            $this->addTranslationToDatabaseAndToCache($orig, $orig, $lng_source, $lng_target, 'email');
        } elseif ($this->stringIsChecked($orig, $lng_source, $lng_target, 'email')) {
            return ($is_link ? 'mailto:' : '') . $trans;
        }
        return ($is_link ? 'mailto:' : '') . $orig;
    }

    function getTranslationAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target, $context = null)
    {
        /*
        $orig
        - <a href="https://tld.com" class="foo" data-bar="baz">Hallo</a> Welt!
        - Das deutsche <a href="https://1.com">Brot</a> <a href="https://2.com">vermisse</a> ich am meisten.
        - <a class="notranslate foo">Hallo</a> Welt!

        $origWithoutAttributes
        - <a>Hallo</a> Welt!
        - Das deutsche <a>Brot</a> <a>vermisse</a> ich am meisten.
        - <a class="notranslate">Hallo</a> Welt!

        $origWithIds
        - <a href="https://tld.com" class="foo" data-bar="baz" p="1">Hallo</a> Welt!
        - Das deutsche <a href="https://1.com" p="1">Brot</a> <a href="https://2.com" p="2">vermisse</a> ich am meisten.
        - <a class="notranslate foo" p="1">Hallo</a> Welt!

        $transWithIds
        - <a href="https://tld.com" class="foo" data-bar="baz" p="1">Hello</a> world!
        - I <a href="https://2.com" p="2">miss</a> German <a href="https://1.com" p="1">bread</a> the most.
        - <a class="notranslate foo" p="1">Hallo</a> world!

        $transWithoutAttributes
        - <a>Hello</a> world!
        - I <a p="2">miss</a> German <a p="1">bread</a> the most.
        - <a class="notranslate">Hallo</a> world!

        $trans
        - <a href="https://tld.com" class="foo" data-bar="baz">Hello</a> world!
        - I <a href="https://2.com">miss</a> German <a href="https://1.com">bread</a> the most.
        - <a class="notranslate foo">Hallo</a> world!
        */

        [$origWithoutAttributes, $mappingTable] = $this->tags->removeAttributesAndSaveMapping($orig);

        $transWithoutAttributes = $this->getExistingTranslationFromCache(
            $origWithoutAttributes,
            $lng_source,
            $lng_target,
            $context
        );

        if ($transWithoutAttributes === false) {
            $origWithIds = $this->tags->addIds($orig);
            $transWithIds = $this->autoTranslateString($origWithIds, $lng_source, $lng_target, $context);
            if ($transWithIds !== null) {
                $transWithoutAttributes = $this->tags->removeAttributesExceptIrregularIds($transWithIds);
                $this->addTranslationToDatabaseAndToCache(
                    $origWithoutAttributes,
                    $transWithoutAttributes,
                    $lng_source,
                    $lng_target,
                    $context
                );
            } else {
                $transWithoutAttributes = $this->tags->removeAttributesExceptIrregularIds($origWithIds);
            }
        }

        $trans = $this->tags->addAttributesAndRemoveIds($transWithoutAttributes, $mappingTable);

        if (!$this->stringIsChecked($origWithoutAttributes, $lng_source, $lng_target, $context)) {
            return $origWithoutAttributes;
        }

        return $trans;
    }

    function autoTranslateString($orig, $lng_source, $lng_target, $context = null)
    {
        if ($lng_source === null) {
            $lng_source = $this->settings->getSourceLanguageCode();
        }

        $trans = null;

        if ($this->settings->get('auto_translation') === true) {
            $lng_source_service = $this->settings->getApiLngCodeForService(
                $this->settings->get('auto_translation_service'),
                $lng_source
            );
            $lng_target_service = $this->settings->getApiLngCodeForService(
                $this->settings->get('auto_translation_service'),
                $lng_target
            );
            if ($lng_source_service === null || $lng_target_service === null) {
                return null;
            }
            if ($this->settings->get('auto_translation_service') === 'google') {
                $api_key = $this->settings->get('google_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                $trans = null;
                // sometimes google translation api has some hickups (especially in latin); we overcome this by trying it again
                $tries = 0;
                while ($tries < 10) {
                    try {
                        $trans = __::translate_google($orig, $lng_source_service, $lng_target_service, $api_key);
                        //$this->log->generalLog(['SUCCESSFUL TRANSLATION', $orig, $lng_source, $lng_target, $api_key, $trans]);
                        break;
                    } catch (\Throwable $t) {
                        //$this->log->generalLog(['FAILED TRANSLATION (TRIES: ' . $tries . ')',$t->getMessage(),$orig,$lng_source,$lng_target,$api_key,$trans]);
                        if (strpos($t->getMessage(), 'PERMISSION_DENIED') !== false) {
                            break;
                        }
                        sleep(1);
                        $tries++;
                    }
                }
                if ($trans === null || $trans === '') {
                    return null;
                }
                $this->log->statsLogIncrease('google', mb_strlen($orig));
            } elseif ($this->settings->get('auto_translation_service') === 'microsoft') {
                $api_key = $this->settings->get('microsoft_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                try {
                    $trans = __::translate_microsoft($orig, $lng_source_service, $lng_target_service, $api_key);
                } catch (\Throwable $t) {
                    $trans = null;
                }
                if ($trans === null || $trans === '') {
                    return null;
                }
                $this->log->statsLogIncrease('microsoft', mb_strlen($orig));
            } elseif ($this->settings->get('auto_translation_service') === 'deepl') {
                $api_key = $this->settings->get('deepl_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                try {
                    $trans = __::translate_deepl($orig, $lng_source_service, $lng_target_service, $api_key);
                } catch (\Throwable $t) {
                    $trans = null;
                }
                if ($trans === null || $trans === '') {
                    return null;
                }
                $this->log->statsLogIncrease('deepl', mb_strlen($orig));
            }
            if ($context === 'slug') {
                $trans = $this->utils->slugify($trans, $orig, $lng_target);
            }
        } else {
            $trans = $this->translateStringMock($orig, $lng_source, $lng_target, $context);
        }

        // slug collission detection
        if ($context === 'slug') {
            $counter = 2;
            while (
                $this->getExistingTranslationReverseFromCache($trans, $lng_source, $lng_target, $context) !== false
            ) {
                if ($counter > 2) {
                    $trans = mb_substr($trans, 0, mb_strrpos($trans, '-'));
                }
                $trans .= '-' . $counter;
                $counter++;
            }
        }

        if ($this->settings->get('debug_translations') === true) {
            if ($context !== 'slug') {
                $trans = '%|%' . $trans . '%|%';
            }
        }

        if ($trans === '') {
            return null;
        }

        return $trans;
    }

    function removeLineBreaks($orig)
    {
        $str = $orig;
        $str = trim($str);
        $str = str_replace(['&#13;', "\r"], '', $str); // replace nasty carriage returns \r
        $str = preg_replace('/[\t]+/', ' ', $str); // replace multiple tab spaces with one tab space
        $parts = explode(PHP_EOL, $str);
        foreach ($parts as $parts__key => $parts__value) {
            $parts__value = trim($parts__value);
            if ($parts__value == '') {
                unset($parts[$parts__key]);
            } else {
                $parts[$parts__key] = $parts__value;
            }
        }
        $str = implode(' ', $parts);
        return $str;
    }

    function reintroduceLineBreaks($str, $orig_withoutlb, $orig_with_lb)
    {
        $pos_lb_begin = 0;
        while (mb_substr($orig_with_lb, $pos_lb_begin, 1) !== mb_substr($orig_withoutlb, 0, 1)) {
            $pos_lb_begin++;
        }
        $pos_lb_end = mb_strlen($orig_with_lb) - 1;
        while (
            mb_substr($orig_with_lb, $pos_lb_end, 1) !== mb_substr($orig_withoutlb, mb_strlen($orig_withoutlb) - 1, 1)
        ) {
            $pos_lb_end--;
        }
        $str = mb_substr($orig_with_lb, 0, $pos_lb_begin) . $str . mb_substr($orig_with_lb, $pos_lb_end + 1);
        return $str;
    }

    function translateStringMock($str, $lng_source, $lng_target, $context = null)
    {
        if ($lng_source === null) {
            $lng_source = $this->settings->getSourceLanguageCode();
        }
        if ($context === 'slug') {
            $pos = mb_strlen($str) - mb_strlen('-' . $lng_source);
            if (mb_strrpos($str, '-' . $lng_source) === $pos) {
                $str = mb_substr($str, 0, $pos);
            }
            if ($lng_target === $this->settings->getSourceLanguageCode()) {
                return $str;
            }
            return $str . ($context != '' ? '-' . $context : '') . '-' . $lng_target;
        }
        return $str . ($context != '' ? '-' . $context : '') . '-' . $lng_target;
    }

    function stringShouldNotBeTranslated($str, $context = null)
    {
        if ($str === null || $str === true || $str === false || $str === '') {
            return true;
        }
        $str = trim($str, ' \'"');
        if ($str == '') {
            return true;
        }
        $length = mb_strlen($str);
        // numbers
        if (is_numeric($str)) {
            return true;
        }
        if (preg_match('/[a-zA-Z]/', $str) !== 1) {
            return true;
        }
        // lng codes
        if (in_array(strtolower($str), $this->settings->getSelectedLanguageCodes())) {
            return true;
        }
        if ($context === 'slug') {
            if (mb_strpos(trim($str, '/'), '#') === 0) {
                return true;
            }
            if (mb_strpos(trim($str, '/'), '?') === 0) {
                return true;
            }
            if (mb_strpos(trim($str, '/'), '&') === 0) {
                return true;
            }
            // static files like big-image.jpg
            if (preg_match('/.+\.[a-zA-Z\d]+$/', $str)) {
                return true;
            }
        }
        // detect paths to php scripts
        if (mb_strpos($str, ' ') === false && mb_strpos($str, '.php') !== false) {
            return true;
        }
        // detect print_r outputs
        if (mb_strpos($str, '(') === 0 && mb_strrpos($str, ')') === $length - 1 && mb_strpos($str, '=') !== false) {
            return true;
        }
        // detect mathjax/latex
        if (mb_strpos($str, '$$') === 0 && mb_strrpos($str, '$$') === $length - 2) {
            return true;
        }
        if (mb_strpos($str, '\\(') === 0 && mb_strrpos($str, '\\)') === $length - 2) {
            return true;
        }
        return false;
    }

    function autoDetermineContext($value, $suggestion = null)
    {
        $context = $suggestion;
        if ($context === null || $context == '') {
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $context = 'email';
            } elseif (mb_strpos($value, $this->host->getCurrentHost()) === 0) {
                $context = 'slug|file';
            } elseif (mb_strpos($value, 'http') === 0 && mb_strpos($value, ' ') === false) {
                $context = 'slug';
            }
        }
        if ($context === 'slug|file') {
            $value_modified = $value;
            if (!preg_match('/^[a-zA-Z]+?:.+$/', $value_modified)) {
                $value_modified = $this->host->getCurrentHost() . '/' . $value_modified;
            }
            $value_modified = str_replace(['.php', '.html'], '', $value_modified);
            if (preg_match('/\/.+\.[a-zA-Z\d]+$/', str_replace('://', '', $value_modified))) {
                $context = 'file';
            } else {
                $context = 'slug';
            }
        }
        return $context;
    }

    function stringIsChecked($str, $lng_source, $lng_target, $context = null)
    {
        if ($this->settings->get('only_show_checked_strings') !== true) {
            return true;
        }
        if ($lng_target === $this->settings->getSourceLanguageCode()) {
            return true;
        }
        if (
            $str === '' ||
            $str === null ||
            !array_key_exists($lng_source, $this->data['checked_strings']) ||
            !array_key_exists($lng_target, $this->data['checked_strings'][$lng_source]) ||
            !array_key_exists($context ?? '', $this->data['checked_strings'][$lng_source][$lng_target]) ||
            !array_key_exists($str, $this->data['checked_strings'][$lng_source][$lng_target][$context ?? '']) ||
            $this->data['checked_strings'][$lng_source][$lng_target][$context ?? ''][$str] != '1'
        ) {
            return false;
        }
        return true;
    }

    function getUrlTranslationInLanguage($from_lng, $to_lng, $url = null)
    {
        $path = null;
        if ($url !== null) {
            $path = str_replace($this->host->getCurrentHost(), '', $url);
        } else {
            $path = $this->host->getCurrentPathWithArgs();
        }
        return trim(
            trim($this->host->getCurrentHost(), '/') .
                '/' .
                trim($this->getPathTranslationInLanguage($from_lng, $to_lng, false, $path), '/'),
            '/'
        ) . (mb_strpos($path, '?') === false ? '/' : '');
    }

    function getTranslationInForeignLng($str, $to_lng, $from_lng = null, $context = null)
    {
        $data = [
            'trans' => false,
            'str_in_source_lng' => false,
            'checked_from' => true,
            'checked_to' => true
        ];
        if ($from_lng === $this->settings->getSourceLanguageCode()) {
            $data['str_in_source_lng'] = $str;
        } else {
            $data['str_in_source_lng'] = $this->getExistingTranslationReverseFromCache(
                $str,
                $this->settings->getSourceLanguageCode(),
                $from_lng,
                $context
            );
        }
        if ($data['str_in_source_lng'] === false) {
            return $data;
        }
        if (
            $to_lng === $this->settings->getSourceLanguageCode() ||
            $this->stringShouldNotBeTranslated($data['str_in_source_lng'], $context)
        ) {
            $data['trans'] = $data['str_in_source_lng'];
            return $data;
        }
        $data['checked_from'] = $this->stringIsChecked(
            $data['str_in_source_lng'],
            $this->settings->getSourceLanguageCode(),
            $from_lng,
            $context
        );
        $data['checked_to'] = $this->stringIsChecked(
            $data['str_in_source_lng'],
            $this->settings->getSourceLanguageCode(),
            $to_lng,
            $context
        );
        $data['trans'] = $this->getExistingTranslationFromCache(
            $data['str_in_source_lng'],
            $this->settings->getSourceLanguageCode(),
            $to_lng,
            $context
        );
        return $data;
    }

    function getTranslationInForeignLngAndAddDynamicallyIfNeeded(
        $str,
        $lng_target = null,
        $lng_source = null,
        $context = null
    ) {
        if ($lng_target === null) {
            $lng_target = $this->getCurrentLanguageCode();
        }
        if ($lng_source === null) {
            $lng_source = $this->settings->getSourceLanguageCode();
        }
        $data = $this->getTranslationInForeignLng($str, $lng_target, $lng_source, $context);
        $trans = $data['trans'];
        if ($trans === false) {
            if ($lng_source === $this->settings->getSourceLanguageCode()) {
                $str_in_source = $str;
            } else {
                $str_in_source = $this->autoTranslateString(
                    $str,
                    $lng_source,
                    $this->settings->getSourceLanguageCode(),
                    $context
                );
            }
            $trans = $this->autoTranslateString($str_in_source, $lng_source, $lng_target, $context);
            if ($trans !== null) {
                $this->addTranslationToDatabaseAndToCache($str_in_source, $str, $lng_target, $lng_source, $context);
                $this->addTranslationToDatabaseAndToCache($str_in_source, $trans, $lng_source, $lng_target, $context);
            } else {
                $trans = $str;
            }
        }
        if ($data['checked_from'] === false || $data['checked_to'] === false) {
            return $str;
        }
        return $trans;
    }

    function getPathTranslationInLanguage($from_lng, $to_lng, $always_remove_prefix = false, $path = null)
    {
        if ($path === null) {
            $path = $this->host->getCurrentPathWithArgs();
        }
        if ($from_lng === $to_lng) {
            return $path;
        }
        $path_parts = explode('/', $path);
        foreach ($path_parts as $path_parts__key => $path_parts__value) {
            if ($path_parts[$path_parts__key] == '') {
                unset($path_parts[$path_parts__key]);
            }
        }
        $path_parts = array_values($path_parts);

        // prefix
        if (
            $always_remove_prefix === true ||
            ($this->settings->getSourceLanguageCode() === $to_lng &&
                $this->settings->get('prefix_source_lng') === false)
        ) {
            if (@$path_parts[0] === $from_lng) {
                unset($path_parts[0]);
            }
        } else {
            if (@$path_parts[0] === $from_lng) {
                $path_parts[0] = $to_lng;
            } else {
                array_unshift($path_parts, $to_lng);
            }
        }

        foreach ($path_parts as $path_parts__key => $path_parts__value) {
            if (in_array($path_parts__value, $this->settings->getSelectedLanguageCodes())) {
                continue;
            }
            $data = $this->getTranslationInForeignLng($path_parts__value, $to_lng, $from_lng, 'slug');
            if ($this->settings->get('only_show_checked_strings') === true) {
                // no string has been found in general (unchecked or checked)
                // this is always the case, if you are on a unchecked url (like /en/impressum)
                // and try to translate that e.g. from english to french
                if ($data['trans'] === false) {
                    $data = $this->getTranslationInForeignLng($path_parts__value, $to_lng, $from_lng, 'slug');
                }
                if ($data['checked_from'] === false && $data['checked_to'] === false) {
                    $trans = false;
                } elseif ($data['checked_from'] === true && $data['checked_to'] === false) {
                    $trans = $data['str_in_source_lng'];
                } elseif ($data['checked_from'] === false && $data['checked_to'] === true) {
                    $trans = false;
                } elseif ($data['checked_from'] === true && $data['checked_to'] === true) {
                    $trans = $data['trans'];
                }
            } else {
                $trans = $data['trans'];
            }
            if ($trans !== false) {
                $path_parts[$path_parts__key] = $trans;
            }
        }
        $path = implode('/', $path_parts);
        return $path;
    }

    function addCurrentUrlToTranslations()
    {
        if (!$this->sourceLngIsCurrentLng()) {
            return;
        }
        if ($this->host->isAjaxRequest()) {
            return;
        }
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }
        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            $this->prepareTranslationAndAddDynamicallyIfNeeded(
                $this->host->getCurrentUrl(),
                $this->settings->getSourceLanguageCode(),
                $languages__value,
                'slug'
            );
        }
    }
}
