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
        /* performance here is not crucial: the following operations take ~1/1000s */
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
                            lng VARCHAR(20) NOT NULL,
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
                    'CREATE UNIQUE INDEX ' . $this->table . '_idx ON ' . $this->table . '(str, context, lng)'
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
                        lng VARCHAR(20) NOT NULL,
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
                    'CREATE UNIQUE INDEX ' . $this->table . '_idx ON ' . $this->table . '(str(255), context, lng)'
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
                    $this->data['cache'][$result__value['lng'] ?? ''][$result__value['context'] ?? ''][
                        $result__value['str']
                    ] = $result__value['trans'];
                    $this->data['cache_reverse'][$result__value['lng'] ?? ''][$result__value['context'] ?? ''][
                        $result__value['trans']
                    ] = $result__value['str'];
                    $this->data['checked_strings'][$result__value['lng'] ?? ''][$result__value['context'] ?? ''][
                        $result__value['str']
                    ] = $result__value['checked'] == '1' ? true : false;
                }
            }
        }
    }

    function generateGettextFiles()
    {
        if ($this->settings->get('auto_add_translations_to_gettext') === false) {
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
                    ' (str, context, lng, trans, added, checked, shared, discovered_last_time, discovered_last_url_orig, discovered_last_url) VALUES ';
                $query_q = [];
                $query_a = [];
                foreach ($this->data['save']['insert'] as $save__key => $save__value) {
                    if ($save__key < $batch_size * $batch_cur || $save__key >= $batch_size * ($batch_cur + 1)) {
                        continue;
                    }
                    $query_q[] = '(?,?,?,?,?,?,?,?,?,?)';
                    $query_a = array_merge($query_a, [
                        $save__value['str'],
                        $save__value['context'],
                        $save__value['lng'],
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
                    $query_q[] = $this->caseSensitiveCol('str') . ' = ? AND context = ? AND lng = ?';
                    $query_a = array_merge($query_a, [
                        $save__value['str'],
                        $save__value['context'],
                        $save__value['lng']
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

    function trackDiscovered($str, $lng, $context = null)
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
            'lng' => $lng
        ];
    }

    function getExistingTranslationFromCache($str, $lng, $context = null)
    {
        $this->trackDiscovered($str, $lng, $context);
        if (
            $str === '' ||
            $str === null ||
            !array_key_exists($lng, $this->data['cache']) ||
            !array_key_exists($context ?? '', $this->data['cache'][$lng]) ||
            !array_key_exists($str, $this->data['cache'][$lng][$context ?? '']) ||
            $this->data['cache'][$lng][$context ?? ''][$str] === ''
        ) {
            return false;
        }
        return $this->data['cache'][$lng][$context ?? ''][$str];
    }

    function getExistingTranslationReverseFromCache($str, $lng, $context = null)
    {
        if (
            $str === '' ||
            $str === null ||
            !array_key_exists($lng, $this->data['cache_reverse']) ||
            !array_key_exists($context ?? '', $this->data['cache_reverse'][$lng]) ||
            !array_key_exists($str, $this->data['cache_reverse'][$lng][$context ?? '']) ||
            $this->data['cache_reverse'][$lng][$context ?? ''][$str] === ''
        ) {
            return false;
        }
        return $this->data['cache_reverse'][$lng][$context ?? ''][$str];
    }

    function getTranslationFromDb($str, $context = null, $lng = null)
    {
        return $this->db->fetch_row(
            'SELECT * FROM ' .
                $this->table .
                ' WHERE ' .
                $this->caseSensitiveCol('str') .
                ' = ? AND context = ? AND lng = ?',
            $str,
            $context,
            $lng
        );
    }

    function getTranslationsFromDb()
    {
        return $this->db->fetch_all('SELECT * FROM ' . $this->table . ' ORDER BY id ASC');
    }

    function getAllTranslationsFromFiles($lng = null, $order_by_string = true)
    {
        $data = [];
        $query = '';
        $query_args = [];
        $lngs = [];
        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if ($lng !== null && $lng !== $languages__value) {
                continue;
            }
            $lngs[] = $languages__value;
        }

        $query .=
            'SELECT DISTINCT (' .
            $this->caseSensitiveCol($this->table . '.str') .
            ') as str, ' .
            $this->table .
            '.context as context, ';
        foreach ($lngs as $lngs__value) {
            foreach (
                [
                    'trans',
                    'added',
                    'checked',
                    'shared',
                    'discovered_last_time',
                    'discovered_last_url_orig',
                    'discovered_last_url'
                ]
                as $cols__value
            ) {
                $query .= $lngs__value . '.' . $cols__value . ' as ' . $lngs__value . '_' . $cols__value . ', ';
            }
        }
        $query .=
            implode(
                ' AND ',
                array_map(function ($lngs__value) {
                    return $lngs__value . '.shared';
                }, $lngs)
            ) . ' as shared ';
        $query .= 'FROM ' . $this->table . ' ';
        foreach ($lngs as $lngs__value) {
            $query .=
                'LEFT JOIN ' .
                $this->table .
                ' as ' .
                $lngs__value .
                ' ON ' .
                $this->caseSensitiveCol($lngs__value . '.str') .
                ' = ' .
                $this->caseSensitiveCol($this->table . '.str') .
                ' AND COALESCE(' .
                $lngs__value .
                '.context,\'\') = COALESCE(' .
                $this->table .
                '.context,\'\') AND ' .
                $lngs__value .
                '.lng = ? ';
            $query_args[] = $lngs__value;
        }
        $data = $this->db->fetch_all($query, $query_args);

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
        $lng,
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
                        ' WHERE str <> ? AND context = ? AND lng = ? AND trans = ?',
                    $str,
                    $context,
                    $lng,
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
                ' = ? AND context = ? AND lng = ?',
            $str,
            $context,
            $lng
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
                'lng' => $lng,
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

    function setCheckedToAllStringsFromFiles()
    {
        $this->db->query('UPDATE ' . $this->table . ' SET checked = ?', 1);
        return true;
    }

    function editCheckedValue($str, $context = null, $lng, $checked)
    {
        $this->db->query(
            'UPDATE ' .
                $this->table .
                ' SET checked = ? WHERE ' .
                $this->caseSensitiveCol('str') .
                ' = ? AND context = ? AND lng = ?',
            $checked === true ? 1 : 0,
            $str,
            $context,
            $lng
        );
        return true;
    }

    function resetSharedValues()
    {
        $this->db->query('UPDATE ' . $this->table . ' SET shared = ?', 0);
    }

    function deleteStringFromGettext($str, $context, $lng = null)
    {
        $args = [];
        $args['str'] = $str;
        $args['context'] = $context;
        if ($lng !== null) {
            $args['lng'] = $lng;
        }
        $this->db->delete($this->table, $args);
        return true;
    }

    function addTranslationToPoFileAndToCache($str, $trans, $lng, $context = null)
    {
        if ($lng === $this->settings->getSourceLanguageCode()) {
            return;
        }
        $this->data['save']['insert'][] = [
            'str' => $str,
            'context' => $context,
            'lng' => $lng,
            'trans' => $trans,
            'checked' => $this->settings->get('auto_set_new_strings_checked') === true ? 1 : 0,
            'shared' => 0
        ];
        $this->data['cache'][$lng][$context ?? ''][$str] = $trans;
        $this->data['cache_reverse'][$lng][$context ?? ''][$trans] = $str;
    }

    function resetTranslations()
    {
        if ($this->db->sql->engine === 'sqlite') {
            @unlink($this->settings->get('database')['filename']);
        } else {
            $this->db->delete_table($this->table);
        }
    }

    function clearTable($lng = null)
    {
        if ($lng === null) {
            $this->db->clear($this->table);
        } else {
            $this->db->delete($this->table, ['lng' => $lng]);
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
        foreach ($this->settings->getSelectedLanguages() as $languages__key => $languages__value) {
            if (!$this->host->responseCodeIsSuccessful()) {
                continue;
            }
            $url = $this->getUrlTranslationInLanguage($languages__key);
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

    function prepareTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context = null)
    {
        $context = $this->autoDetermineContext($orig, $context);

        if (($context === 'slug' || $context === 'file') && $this->host->urlIsExcluded($orig)) {
            return null;
        }

        if ($this->sourceLngIsCurrentLng() && $this->settings->getSourceLanguageCode() === $lng) {
            if ($context !== 'slug') {
                return null;
            } else {
                return $this->addPrefixToLink($orig, $this->settings->getSourceLanguageCode());
            }
        }

        if ($context === 'slug') {
            $trans = $this->getTranslationOfLinkHrefAndAddDynamicallyIfNeeded($orig, $lng);
            if ($trans === null) {
                return $orig;
            }
            return $trans;
        }
        if ($context === 'file') {
            $trans = $this->getTranslationOfFileAndAddDynamicallyIfNeeded($orig, $lng);
            if ($trans === null) {
                return $orig;
            }
            return $trans;
        }
        if ($context === 'title') {
            foreach (['|', '·', '•', '>', '-', '–', '—', ':', '*', '⋆', '~', '«', '»', '<'] as $delimiters__value) {
                $orig = str_replace(' ', ' ', $orig); // replace hidden &nbsp; chars
                if (mb_strpos($orig, ' ' . $delimiters__value . ' ') !== false) {
                    $orig_parts = explode(' ' . $delimiters__value . ' ', $orig);
                    foreach ($orig_parts as $orig_parts__key => $orig_parts__value) {
                        $trans = $this->getTranslationAndAddDynamicallyIfNeeded($orig_parts__value, $lng, $context);
                        $orig_parts[$orig_parts__key] = $trans;
                    }
                    $trans = implode(' ' . $delimiters__value . ' ', $orig_parts);
                    return $trans;
                }
            }
        }

        if ($this->stringShouldNotBeTranslated($orig, $context)) {
            return null;
        }
        return $this->getTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context);
    }

    function addPrefixToLink($link, $lng)
    {
        return $this->addPrefixToLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng, false);
    }

    function getTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng)
    {
        return $this->addPrefixToLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng, true);
    }

    function addPrefixToLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng, $translate)
    {
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
                    $lng,
                    'slug'
                );
            }
            $link = implode('/', $url_parts);
        }
        $link = (mb_strpos($link, '/') === 0 ? '/' : '') . $lng . '/' . ltrim($link, '/');
        if ($is_absolute_link === true) {
            $link = rtrim($this->host->getCurrentHost(), '/') . '/' . ltrim($link, '/');
        }
        return $link;
    }

    function getTranslationOfFileAndAddDynamicallyIfNeeded($orig, $lng)
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
            $trans = $this->getExistingTranslationFromCache($urls__value, $lng, 'file');
            if ($trans === false) {
                $this->addTranslationToPoFileAndToCache($urls__value, $urls__value, $lng, 'file');
            } elseif ($this->stringIsChecked($urls__value, $lng, 'file')) {
                $orig = str_replace($urls__value, $trans, $orig);
            }
        }
        return $orig;
    }

    function getTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context = null)
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

        $transWithoutAttributes = $this->getExistingTranslationFromCache($origWithoutAttributes, $lng, $context);

        if ($transWithoutAttributes === false) {
            $origWithIds = $this->tags->addIds($orig);
            $transWithIds = $this->autoTranslateString($origWithIds, $lng, $context);
            if ($transWithIds !== null) {
                $transWithoutAttributes = $this->tags->removeAttributesExceptIrregularIds($transWithIds);
                $this->addTranslationToPoFileAndToCache(
                    $origWithoutAttributes,
                    $transWithoutAttributes,
                    $lng,
                    $context
                );
            } else {
                $transWithoutAttributes = $this->tags->removeAttributesExceptIrregularIds($origWithIds);
            }
        }

        $trans = $this->tags->addAttributesAndRemoveIds($transWithoutAttributes, $mappingTable);

        if (!$this->stringIsChecked($origWithoutAttributes, $lng, $context)) {
            return $origWithoutAttributes;
        }

        return $trans;
    }

    function autoTranslateString($orig, $to_lng, $context = null, $from_lng = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->settings->getSourceLanguageCode();
        }

        $trans = null;

        if ($this->settings->get('auto_translation') === true) {
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
                        $trans = __::translate_google($orig, $from_lng, $to_lng, $api_key);
                        //$this->log->generalLog(['SUCCESSFUL TRANSLATION', $orig, $from_lng, $to_lng, $api_key, $trans]);
                        break;
                    } catch (\Throwable $t) {
                        //$this->log->generalLog(['FAILED TRANSLATION (TRIES: ' . $tries . ')',$t->getMessage(),$orig,$from_lng,$to_lng,$api_key,$trans]);
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
                $trans = __::translate_microsoft($orig, $from_lng, $to_lng, $api_key);
                if ($trans === null || $trans === '') {
                    return null;
                }
                $this->log->statsLogIncrease('microsoft', mb_strlen($orig));
            }
            if ($context === 'slug') {
                $trans = $this->utils->slugify($trans, $orig, $to_lng);
            }
        } else {
            $trans = $this->translateStringMock($orig, $to_lng, $context, $from_lng);
        }

        // slug collission detection
        if ($context === 'slug') {
            $counter = 2;
            while ($this->getExistingTranslationReverseFromCache($trans, $to_lng, $context) !== false) {
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

    function translateStringMock($str, $to_lng, $context = null, $from_lng = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->settings->getSourceLanguageCode();
        }
        if ($context === 'slug') {
            $pos = mb_strlen($str) - mb_strlen('-' . $from_lng);
            if (mb_strrpos($str, '-' . $from_lng) === $pos) {
                $str = mb_substr($str, 0, $pos);
            }
            if ($to_lng === $this->settings->getSourceLanguageCode()) {
                return $str;
            }
            return $str . '-' . $to_lng;
        }
        return $str . '-' . $to_lng;
    }

    function stringShouldNotBeTranslated($str, $context = null)
    {
        if ($str === null || $str === true || $str === false || $str === '') {
            return true;
        }
        $str = trim($str);
        $str = trim($str, '"');
        $str = trim($str, '\'');
        if ($str == '') {
            return true;
        }
        // numbers
        if (is_numeric($str)) {
            return true;
        }
        if (preg_match('/[a-zA-Z]/', $str) !== 1) {
            return true;
        }
        // email adresses
        if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        // lng codes
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if ($languages__value === trim(mb_strtolower($str))) {
                return true;
            }
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
        if (
            mb_strpos($str, '(') === 0 &&
            mb_strrpos($str, ')') === mb_strlen($str) - 1 &&
            mb_strpos($str, '=') !== false
        ) {
            return true;
        }
        // detect mathjax/latex
        if (mb_strpos($str, '$$') === 0 && mb_strrpos($str, '$$') === mb_strlen($str) - 2) {
            return true;
        }
        if (mb_strpos($str, '\\(') === 0 && mb_strrpos($str, '\\)') === mb_strlen($str) - 2) {
            return true;
        }
        return false;
    }

    function autoDetermineContext($value, $suggestion = null)
    {
        $context = $suggestion;
        if ($context === null || $context == '') {
            if (mb_strpos($value, $this->host->getCurrentHost()) === 0) {
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

    function stringIsChecked($str, $lng, $context = null)
    {
        if ($this->settings->get('only_show_checked_strings') !== true) {
            return true;
        }
        if ($lng === $this->settings->getSourceLanguageCode()) {
            return true;
        }
        if (
            $str === '' ||
            $str === null ||
            !array_key_exists($lng, $this->data['checked_strings']) ||
            !array_key_exists($context ?? '', $this->data['checked_strings'][$lng]) ||
            !array_key_exists($str, $this->data['checked_strings'][$lng][$context ?? '']) ||
            $this->data['checked_strings'][$lng][$context ?? ''][$str] != '1'
        ) {
            return false;
        }
        return true;
    }

    function getUrlTranslationInLanguage($lng, $url = null)
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
                trim($this->getPathTranslationInLanguage($lng, false, $path), '/'),
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
            $data['str_in_source_lng'] = $this->getExistingTranslationReverseFromCache($str, $from_lng, $context); // str in source lng
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
        $data['checked_from'] = $this->stringIsChecked($data['str_in_source_lng'], $from_lng, $context);
        $data['checked_to'] = $this->stringIsChecked($data['str_in_source_lng'], $to_lng, $context);
        $data['trans'] = $this->getExistingTranslationFromCache($data['str_in_source_lng'], $to_lng, $context);
        return $data;
    }

    function getTranslationInForeignLngAndAddDynamicallyIfNeeded(
        $str,
        $to_lng = null,
        $from_lng = null,
        $context = null
    ) {
        if ($to_lng === null) {
            $to_lng = $this->getCurrentLanguageCode();
        }
        if ($from_lng === null) {
            $from_lng = $this->settings->getSourceLanguageCode();
        }
        $data = $this->getTranslationInForeignLng($str, $to_lng, $from_lng, $context);
        $trans = $data['trans'];
        if ($trans === false) {
            if ($from_lng === $this->settings->getSourceLanguageCode()) {
                $str_in_source = $str;
            } else {
                $str_in_source = $this->autoTranslateString(
                    $str,
                    $this->settings->getSourceLanguageCode(),
                    $context,
                    $from_lng
                );
            }
            $trans = $this->autoTranslateString($str_in_source, $to_lng, $context, $from_lng);
            if ($trans !== null) {
                $this->addTranslationToPoFileAndToCache($str_in_source, $str, $from_lng, $context);
                $this->addTranslationToPoFileAndToCache($str_in_source, $trans, $to_lng, $context);
            } else {
                $trans = $str;
            }
        }
        if ($data['checked_from'] === false || $data['checked_to'] === false) {
            return $str;
        }
        return $trans;
    }

    function getPathTranslationInLanguage($lng, $always_remove_prefix = false, $path = null)
    {
        if ($path === null) {
            $path = $this->host->getCurrentPathWithArgs();
        }
        if ($this->getCurrentLanguageCode() === $lng) {
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
            ($this->settings->getSourceLanguageCode() === $lng && $this->settings->get('prefix_source_lng') === false)
        ) {
            if (@$path_parts[0] === $this->getCurrentLanguageCode()) {
                unset($path_parts[0]);
            }
        } else {
            if (@$path_parts[0] === $this->getCurrentLanguageCode()) {
                $path_parts[0] = $lng;
            } else {
                array_unshift($path_parts, $lng);
            }
        }

        foreach ($path_parts as $path_parts__key => $path_parts__value) {
            if (in_array($path_parts__value, $this->settings->getSelectedLanguageCodes())) {
                continue;
            }
            $data = $this->getTranslationInForeignLng(
                $path_parts__value,
                $lng,
                $this->getCurrentLanguageCode(),
                'slug'
            );
            if ($this->settings->get('only_show_checked_strings') === true) {
                // no string has been found in general (unchecked or checked)
                // this is always the case, if you are on a unchecked url (like /en/impressum)
                // and try to translate that e.g. from english to french
                if ($data['trans'] === false) {
                    $data = $this->getTranslationInForeignLng(
                        $path_parts__value,
                        $lng,
                        $this->settings->getSourceLanguageCode(),
                        'slug'
                    );
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
            $this->prepareTranslationAndAddDynamicallyIfNeeded($this->host->getCurrentUrl(), $languages__value, 'slug');
        }
    }
}
