<?php
namespace vielhuber\gtbabel;

use vielhuber\stringhelper\__;
use vielhuber\dbhelper\dbhelper;

class Data
{
    public $data;
    public $db;
    public $table;
    public $stats;

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
        /* we store null values as the empty string "" */
        /* furthermore unique indexes (for using INSERT OR REPLACE later on) show a lot of caveats */
        /* one is mainly, that mysql supports only a limited length for the unique index */
        /* therefore we call delete_duplicates() after inserting and don't have to add indexes */
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
                            context VARCHAR(20) NOT NULL,
                            lng_source VARCHAR(20) NOT NULL,
                            lng_target VARCHAR(20) NOT NULL,
                            trans TEXT NOT NULL,
                            added TEXT NOT NULL,
                            checked INTEGER NOT NULL,
                            shared INTEGER NOT NULL,
                            discovered_last_time TEXT,
                            discovered_last_url_orig TEXT,
                            discovered_last_url TEXT,
                            translated_by TEXT
                        )'
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
                        context VARCHAR(20) NOT NULL,
                        lng_source VARCHAR(20) NOT NULL,
                        lng_target VARCHAR(20) NOT NULL,
                        trans TEXT NOT NULL,
                        added TEXT NOT NULL,
                        checked TINYINT NOT NULL,
                        shared TINYINT NOT NULL,
                        discovered_last_time TEXT,
                        discovered_last_url_orig TEXT,
                        discovered_last_url TEXT,
                        translated_by TEXT
                    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
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
                    // we never change the encoding of strings (after grabbing from code, translation etc.)
                    // reason: sometimes, "<" must be encoded (if its part of html); sometimes, " must be encoded (if its part of an attribute)
                    // domdocument knows and considers all of this
                    // it's not a problem, if the strings land encoded inside the database (it's necessary!)
                    // however, when looking up existing strings, we always use the decoded version in order
                    // to prevent annoying duplicates in the database
                    $this->data['cache'][$result__value['lng_source'] ?? ''][$result__value['lng_target'] ?? ''][
                        $result__value['context'] ?? ''
                    ][html_entity_decode($result__value['str'])] = $result__value['trans'];
                    $this->data['cache_reverse'][$result__value['lng_source'] ?? ''][
                        $result__value['lng_target'] ?? ''
                    ][$result__value['context'] ?? ''][html_entity_decode($result__value['trans'])] =
                        $result__value['str'];
                    $this->data['checked_strings'][$result__value['lng_source'] ?? ''][
                        $result__value['lng_target'] ?? ''
                    ][$result__value['context'] ?? ''][html_entity_decode($result__value['str'])] =
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

        // prepare args
        $date = $this->utils->getCurrentTime();
        $discovered_last_url_orig = $this->host->getCurrentUrlWithArgsConverted();
        $discovered_last_url = $this->host->getCurrentUrlWithArgs();
        foreach (['discovered_last_url_orig', 'discovered_last_url'] as $url__value) {
            // extract path
            ${$url__value} = $this->host->getPathWithPrefixFromUrl(${$url__value});
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
                $query .=
                    ' INTO ' .
                    $this->table .
                    ' (str, context, lng_source, lng_target, trans, added, checked, shared, discovered_last_time, discovered_last_url_orig, discovered_last_url, translated_by) VALUES ';
                $query_q = [];
                $query_a = [];
                foreach ($this->data['save']['insert'] as $save__key => $save__value) {
                    if ($save__key < $batch_size * $batch_cur || $save__key >= $batch_size * ($batch_cur + 1)) {
                        continue;
                    }
                    $query_q[] = '(?,?,?,?,?,?,?,?,?,?,?,?)';
                    $query_a = array_merge($query_a, [
                        $save__value['str'],
                        $save__value['context'] ?? '',
                        $save__value['lng_source'],
                        $save__value['lng_target'],
                        $save__value['trans'],
                        $date,
                        $save__value['checked'],
                        $save__value['shared'],
                        $date,
                        $discovered_last_url_orig,
                        $discovered_last_url,
                        $save__value['translated_by']
                    ]);
                }
                $query .= implode(',', $query_q);
                $this->db->query($query, $query_a);
            }
            $this->db->delete_duplicates(
                $this->table,
                ['str', 'context', 'lng_source', 'lng_target'],
                true,
                [
                    'id' => 'desc'
                ],
                true
            );
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
                        $save__value['context'] ?? '',
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
        if ($this->host->contentTranslationIsDisabledForCurrentUrl()) {
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
            !array_key_exists(
                html_entity_decode($str),
                $this->data['cache'][$lng_source][$lng_target][$context ?? '']
            ) ||
            $this->data['cache'][$lng_source][$lng_target][$context ?? ''][html_entity_decode($str)] === ''
        ) {
            return false;
        }
        return $this->data['cache'][$lng_source][$lng_target][$context ?? ''][html_entity_decode($str)];
    }

    function getExistingTranslationReverseFromCache($str, $lng_source, $lng_target, $context = null)
    {
        if (
            $str === '' ||
            $str === null ||
            !array_key_exists($lng_source, $this->data['cache_reverse']) ||
            !array_key_exists($lng_target, $this->data['cache_reverse'][$lng_source]) ||
            !array_key_exists($context ?? '', $this->data['cache_reverse'][$lng_source][$lng_target]) ||
            !array_key_exists(
                html_entity_decode($str),
                $this->data['cache_reverse'][$lng_source][$lng_target][$context ?? '']
            ) ||
            $this->data['cache_reverse'][$lng_source][$lng_target][$context ?? ''][html_entity_decode($str)] === ''
        ) {
            return false;
        }
        return $this->data['cache_reverse'][$lng_source][$lng_target][$context ?? ''][html_entity_decode($str)];
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
            $context ?? '',
            $lng_source,
            $lng_target
        );
    }

    function getTranslationsFromDatabase()
    {
        return $this->db->fetch_all('SELECT * FROM ' . $this->table . ' ORDER BY id ASC');
    }

    function getTranslationCountFromDatabase($lng_target = null, $checked = null)
    {
        $query = 'SELECT COUNT(*) FROM ' . $this->table;
        $args = [];
        if ($lng_target !== null || $checked !== null) {
            $query .= ' WHERE ';
            if ($lng_target !== null) {
                $query .= 'lng_target = ?';
                $args[] = $lng_target;
            }
            if ($lng_target !== null && $checked !== null) {
                $query .= ' AND ';
            }
            if ($checked !== null) {
                $query .= 'checked = ?';
                $args[] = $checked === true ? 1 : 0;
            }
        }
        try {
            $count = $this->db->fetch_var($query, $args);
        } catch (\Exception $e) {
            $count = 0;
        }
        $count = intval($count);
        return $count;
    }

    function getGroupedTranslationsFromDatabase(
        $lng_target = null,
        $order_by_string = true,
        $urls = null,
        $time = null,
        $search_term = null,
        $context = null,
        $shared = null,
        $checked = null,
        $take = null,
        $skip = null
    ) {
        $data = [];

        /* the following approach is (surprisingly) much faster than a group by / join of a lot of columns via sql */
        $query = 'SELECT * FROM ' . $this->table . '';
        $query_args = [];
        if ($lng_target !== null) {
            $query .= ' WHERE lng_target = ?';
            $query_args[] = $lng_target;
        }
        $query .= ' ORDER BY id ASC';
        $result = $this->db->fetch_all($query, $query_args);
        $data_grouped = [];
        if (!empty($result)) {
            foreach ($result as $result__key => $result__value) {
                $data_grouped[$result__value['str']][$result__value['context']]['lng_source'] =
                    $result__value['lng_source'];
                $data_grouped[$result__value['str']][$result__value['context']][$result__value['lng_source']] =
                    $result__value['str'];
                $data_grouped[$result__value['str']][$result__value['context']]['context'] = $result__value['context'];
                if (!isset($data_grouped[$result__value['str']][$result__value['context']]['shared'])) {
                    $data_grouped[$result__value['str']][$result__value['context']]['shared'] = 0;
                }
                if ($result__value['shared'] == 1) {
                    $data_grouped[$result__value['str']][$result__value['context']]['shared'] = 1;
                }
                if (!isset($data_grouped[$result__value['str']][$result__value['context']]['order'])) {
                    $data_grouped[$result__value['str']][$result__value['context']]['order'] = $result__key;
                }
                $data_grouped[$result__value['str']][$result__value['context']][$result__value['lng_target']] =
                    $result__value['trans'];
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
                // put this in source lng also
                $data_grouped[$result__value['str']][$result__value['context']]['discovered_last_url_orig'] =
                    $result__value['discovered_last_url_orig'];
                $data_grouped[$result__value['str']][$result__value['context']][
                    $result__value['lng_target'] . '_discovered_last_url'
                ] = $result__value['discovered_last_url'];
                $data_grouped[$result__value['str']][$result__value['context']][
                    $result__value['lng_target'] . '_translated_by'
                ] = $result__value['translated_by'];
            }
        }
        foreach ($data_grouped as $data_grouped__value) {
            foreach ($data_grouped__value as $data_grouped__value__value) {
                $data[] = $data_grouped__value__value;
            }
        }

        $lng_source = $this->settings->getSourceLanguageCode();
        usort($data, function ($a, $b) use ($order_by_string, $lng_source) {
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
                return strnatcasecmp(@$a[$lng_source], @$b[$lng_source]);
            } else {
                return $a['order'] - $b['order'];
            }
        });
        foreach ($data as $data__key => $data__value) {
            unset($data[$data__key]['order']);
        }

        // filter

        if ($urls !== null && $time !== null) {
            $discovery_strings = $this->discoveryLogGetAfter($time, $urls, false);
            $discovery_strings_index = array_map(function ($discovery_strings__value) {
                return __::encode_data([$discovery_strings__value['str'], $discovery_strings__value['context']]);
            }, $discovery_strings);
            foreach ($data as $data__key => $data__value) {
                if (
                    !in_array(
                        __::encode_data([$data__value[$data__value['lng_source']], $data__value['context']]),
                        $discovery_strings_index
                    )
                ) {
                    unset($data[$data__key]);
                }
            }
        }

        if ($search_term !== null && trim($search_term) != '') {
            foreach ($data as $data__key => $data__value) {
                $found = false;
                foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
                    if (
                        @$data__value[$languages__value] != '' &&
                        mb_stripos($data__value[$languages__value], $search_term) !== false
                    ) {
                        $found = true;
                        break;
                    }
                }
                if ($found === false) {
                    unset($data[$data__key]);
                }
            }
        }

        if ($context !== null) {
            foreach ($data as $data__key => $data__value) {
                if ($data__value['context'] != $context) {
                    unset($data[$data__key]);
                }
            }
        }

        if ($shared !== null && $shared !== '') {
            if ($shared === false) {
                $shared = '0';
            }
            if ($shared === true) {
                $shared = '1';
            }
            foreach ($data as $data__key => $data__value) {
                if (
                    ($shared == '0' && $data__value['shared'] == '1') ||
                    ($shared == '1' && $data__value['shared'] != '1')
                ) {
                    unset($data[$data__key]);
                }
            }
        }

        if ($checked !== null && $checked !== '') {
            if ($checked === false) {
                $checked = '0';
            }
            if ($checked === true) {
                $checked = '1';
            }
            foreach ($data as $data__key => $data__value) {
                $all_checked = true;
                foreach ($data__value as $data__value__key => $data__value__value) {
                    if (strpos($data__value__key, 'checked') === false) {
                        continue;
                    }
                    if ($data__value__value != '1') {
                        $all_checked = false;
                        break;
                    }
                }
                if (($all_checked === true && $checked == '0') || ($all_checked !== true && $checked == '1')) {
                    unset($data[$data__key]);
                }
            }
        }

        // pagination
        $count = count($data);
        if ($take !== null) {
            $data = array_slice($data, $skip === null ? 0 : $skip, $take);
        }

        return ['data' => $data, 'count' => $count];
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
        $discovered_last_url = null,
        $translated_by = null
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
                    $context ?? '',
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
            $context ?? '',
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
                    [
                        'added',
                        'discovered_last_time',
                        'discovered_last_url_orig',
                        'discovered_last_url',
                        'translated_by'
                    ]
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
                'context' => $context ?? '',
                'lng_source' => $lng_source,
                'lng_target' => $lng_target,
                'trans' => $trans ?? '',
                'added' => $added ?? $this->utils->getCurrentTime(),
                'checked' => $checked === true || $checked == 1 ? 1 : 0,
                'shared' => $shared === true || $shared == 1 ? 1 : 0,
                'discovered_last_time' => $discovered_last_time,
                'discovered_last_url_orig' => $discovered_last_url_orig,
                'discovered_last_url' => $discovered_last_url,
                'translated_by' => $translated_by
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

    function deleteUncheckedStrings()
    {
        $this->db->query('DELETE FROM ' . $this->table . ' WHERE checked = ?', 0);
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
            $context ?? '',
            $lng_source,
            $lng_target
        );
        return true;
    }

    function resetSharedValues()
    {
        $this->db->query('UPDATE ' . $this->table . ' SET shared = ?', 0);
    }

    function getDistinctContexts()
    {
        return $this->db->fetch_col('SELECT DISTINCT context FROM ' . $this->table . ' ORDER BY context');
    }

    function deleteStringFromDatabase($str, $context, $lng_source, $lng_target = null)
    {
        $args = [];
        $args['str'] = $str;
        $args['context'] = $context ?? '';
        $args['lng_source'] = $lng_source;
        if ($lng_target !== null) {
            $args['lng_target'] = $lng_target;
        }
        $this->db->delete($this->table, $args);
        return true;
    }

    function addTranslationToDatabaseAndToCache(
        $str,
        $trans,
        $lng_source,
        $lng_target,
        $context = null,
        $translated_by_current_service = true
    ) {
        if ($lng_target === $lng_source) {
            return;
        }
        $this->data['save']['insert'][] = [
            'str' => $str,
            'context' => $context ?? '',
            'lng_source' => $lng_source,
            'lng_target' => $lng_target,
            'trans' => $trans,
            'checked' => $this->settings->get('auto_set_new_strings_checked') === true ? 1 : 0,
            'shared' => 0,
            'translated_by' =>
                $translated_by_current_service === true && $this->settings->get('auto_translation') === true
                    ? $this->settings->get('auto_translation_service')
                    : null
        ];
        $this->data['cache'][$lng_source][$lng_target][$context ?? ''][html_entity_decode($str)] = $trans;
        $this->data['cache_reverse'][$lng_source][$lng_target][$context ?? ''][html_entity_decode($trans)] = $str;
    }

    function translateInlineLinks($data)
    {
        foreach ($data as $data__key => $data__value) {
            $data[$data__key] = $this->prepareTranslationAndAddDynamicallyIfNeeded(
                $data__value,
                $this->settings->getSourceLanguageCode(),
                $this->getCurrentLanguageCode(),
                'slug'
            );
        }
        return $data;
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
            $urls = array_map(function ($urls__value) {
                return trim($this->host->getPathWithPrefixFromUrl($urls__value), '/');
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
            $query .=
                ' AND ' .
                ($this->db->sql->engine === 'sqlite'
                    ? 'TRIM(discovered_last_url,\'/\')'
                    : 'TRIM(\'/\' FROM discovered_last_url)') .
                ' IN (' .
                str_repeat('?,', count($urls) - 1) .
                '?)';
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

    function getCurrentLanguageCode()
    {
        if ($this->settings->get('lng_target') !== null) {
            return $this->settings->get('lng_target');
        }

        return $this->host->getLanguageCodeFromUrl($this->host->getCurrentUrl());
    }

    function getLanguagePickerData($with_args = true, $cur_url = null, $hide_active = false)
    {
        $data = [];
        if (!$this->host->responseCodeIsSuccessful()) {
            return $data;
        }
        if ($cur_url === null) {
            $cur_url = $with_args === true ? $this->host->getCurrentUrlWithArgs() : $this->host->getCurrentUrl();
        }
        foreach ($this->settings->getSelectedLanguageCodesLabels() as $languages__key => $languages__value) {
            if (
                $this->publish->isActive() &&
                $this->publish->isPrevented($this->host->getCurrentUrl(), $languages__key)
            ) {
                continue;
            }
            if ($hide_active === true && $this->getCurrentLanguageCode() === $languages__key) {
                continue;
            }
            $trans_url = $this->getUrlTranslationInLanguage($this->getCurrentLanguageCode(), $languages__key, $cur_url);
            $hreflang = $this->settings->getHreflangCodeForLanguage($languages__key);
            $data[] = [
                'code' => $languages__key,
                'hreflang' => $hreflang,
                'label' => $languages__value,
                'url' => $trans_url,
                'active' => rtrim($trans_url, '/') === rtrim($cur_url, '/')
            ];
        }
        return $data;
    }

    function getLanguagePickerHtml($with_args = true, $cur_url = null, $hide_active = false)
    {
        $data = $this->getLanguagePickerData($with_args, $cur_url, $hide_active);
        $html = '';
        $html .= '<ul class="lngpicker">';
        foreach ($data as $data__value) {
            $html .= '<li>';
            $html .= '<a href="' . $data__value['url'] . '"' . ($data__value['active'] ? ' class="active"' : '') . '>';
            $html .= $data__value['label'];
            $html .= '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    function sourceLngIsCurrentLng()
    {
        if ($this->getCurrentLanguageCode() === $this->settings->getSourceLanguageCode()) {
            return true;
        }
        return false;
    }

    function sourceLngIsRefererLng()
    {
        if ($this->host->getRefererLanguageCode() === $this->settings->getSourceLanguageCode()) {
            return true;
        }
        return false;
    }

    function prepareTranslationAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target, $context = null)
    {
        $context = $this->autoDetermineContext($orig, $context);

        if (($context === 'slug' || $context === 'file') && $this->host->contentTranslationIsDisabledForUrl($orig)) {
            return null;
        }

        if ($lng_source === $lng_target) {
            if (
                $context === 'slug' &&
                $this->settings->getSourceLanguageCode() === $lng_source &&
                $this->host->getPrefixForLanguageCode($lng_source) != '' &&
                $this->host->getPrefixFromUrl($orig) != $this->host->getPrefixForLanguageCode($lng_source)
            ) {
                return $this->modifyLink($orig, $lng_source, $lng_target);
            }
            return null;
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
        if ($context === 'title' || $context === 'description') {
            $trans = $this->getTranslationOfTitleDescriptionAndAddDynamicallyIfNeeded(
                $orig,
                $lng_source,
                $lng_target,
                $context
            );
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

    function getTranslationOfTitleDescriptionAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target, $context)
    {
        $orig = str_replace(' ', ' ', $orig); // replace hidden &nbsp; chars
        $delimiters = ['|', '·', '•', '>', '-', '–', '—', ':', '*', '⋆', '~', '«', '»', '<'];
        $delimiters_encoded = [];
        foreach ($delimiters as $delimiters__value) {
            $delimiters_encoded[] = htmlentities($delimiters__value);
        }
        $delimiters = array_merge($delimiters_encoded, $delimiters);
        foreach ($delimiters as $delimiters__value) {
            if (mb_strpos($orig, ' ' . $delimiters__value . ' ') !== false) {
                $orig_parts = explode(' ' . $delimiters__value . ' ', $orig);
                foreach ($orig_parts as $orig_parts__key => $orig_parts__value) {
                    if ($this->stringShouldNotBeTranslated($orig_parts__value, $context)) {
                        continue;
                    }
                    $trans = $this->getTranslationAndAddDynamicallyIfNeeded(
                        $orig_parts__value,
                        $lng_source,
                        $lng_target,
                        $context
                    );
                    $orig_parts[$orig_parts__key] = $trans;
                }
                $trans = implode(' ' . $delimiters__value . ' ', $orig_parts);
                return $trans;
            }
        }
        return $this->getTranslationAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target, $context);
    }

    function modifyLink($link, $lng_source, $lng_target)
    {
        return $this->modifyLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded(
            $link,
            $lng_source,
            $lng_target,
            false
        );
    }

    function getTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng_source, $lng_target)
    {
        return $this->modifyLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded(
            $link,
            $lng_source,
            $lng_target,
            true
        );
    }

    function modifyLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng_source, $lng_target, $translate)
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
        if ($this->host->urlIsStaticFile($link)) {
            return $link;
        }

        $is_absolute_link =
            mb_strpos($link, $this->host->getBaseUrlForLanguageCode($this->settings->getSourceLanguageCode())) === 0;
        if (mb_strpos($link, 'http') !== false && $is_absolute_link === false) {
            return $link;
        }
        if (mb_strpos($link, 'http') === false && mb_strpos($link, ':') !== false) {
            return $link;
        }

        // strip out host/lng
        $link = $this->host->getPathWithoutPrefixFromUrl($link);

        if ($translate === true) {
            if (!$this->host->slugTranslationIsDisabledForUrl($link)) {
                // preserve (and don't translate args)
                $link_arguments = [];
                foreach (['?', '#'] as $delimiter__value) {
                    if (mb_strpos($link, $delimiter__value) !== false) {
                        $link_arguments[$delimiter__value] = mb_substr($link, mb_strpos($link, $delimiter__value));
                        $link = mb_substr($link, 0, mb_strpos($link, $delimiter__value));
                    }
                }
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
                foreach ($link_arguments as $link_arguments__value) {
                    $link .= $link_arguments__value;
                }
            }
        }
        if ($is_absolute_link === true) {
            $link = rtrim($this->host->getBaseUrlWithPrefixForLanguageCode($lng_target), '/') . '/' . ltrim($link, '/');
        } else {
            if ($this->host->getPrefixForLanguageCode($lng_target) != '') {
                $link =
                    (mb_strpos($link, '/') === 0 ? '/' : '') .
                    $this->host->getPrefixForLanguageCode($lng_target) .
                    '/' .
                    ltrim($link, '/');
            }
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
                $urls[] = trim(trim(trim(trim($matches__value), '\''), '"'));
            }
        } else {
            $urls[] = $orig;
        }
        foreach ($urls as $urls__value) {
            // always submit relative urls
            $urls__value = $this->host->getPathWithoutPrefixFromUrl($urls__value);
            $urls__value = trim($urls__value, '/');
            // skip external files
            if (
                strpos($urls__value, 'http') === 0 &&
                strpos($urls__value, $this->host->getBaseUrlForSourceLanguage()) === false
            ) {
                continue;
            }
            if ($this->stringShouldNotBeTranslated($urls__value, 'file')) {
                continue;
            }
            $trans = $this->getExistingTranslationFromCache($urls__value, $lng_source, $lng_target, 'file');
            if ($trans === false) {
                $this->addTranslationToDatabaseAndToCache(
                    $urls__value,
                    $urls__value,
                    $lng_source,
                    $lng_target,
                    'file',
                    false
                );
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
        // cut off args
        $args = null;
        $args_pos = strpos($orig, '?');
        if ($args_pos !== false) {
            $args = substr($orig, $args_pos + 1);
            $orig = substr($orig, 0, $args_pos);
            parse_str($args, $args_array);
            $args_array_trans = [];
            foreach ($args_array as $args_array__key => $args_array__value) {
                if ($this->stringShouldNotBeTranslated($args_array__value, null)) {
                    $args_array_trans[] = $args_array__key . '=' . $args_array__value;
                    continue;
                }
                $args_array__value = $this->prepareTranslationAndAddDynamicallyIfNeeded(
                    $args_array__value,
                    $lng_source,
                    $lng_target,
                    null
                );
                if ($args_array__key === 'body') {
                    $args_array__value = rawurlencode($args_array__value);
                }
                $args_array_trans[] = $args_array__key . '=' . $args_array__value;
            }
            // don't use http_build_query, because "body" does not use rawurlencode then
            $args = '?' . implode('&', $args_array_trans);
        }
        if (trim($orig) != '') {
            $trans = $this->getExistingTranslationFromCache($orig, $lng_source, $lng_target, 'email');
            if ($trans === false) {
                $this->addTranslationToDatabaseAndToCache($orig, $orig, $lng_source, $lng_target, 'email', false);
            } elseif ($this->stringIsChecked($orig, $lng_source, $lng_target, 'email')) {
                $trans = ($is_link ? 'mailto:' : '') . $trans;
                $trans .= $args;
                return $trans;
            }
        }
        $orig = ($is_link ? 'mailto:' : '') . $orig;
        $orig .= $args;
        return $orig;
    }

    function getTranslationAndAddDynamicallyIfNeeded($orig, $lng_source, $lng_target, $context = null)
    {
        /*
        $orig
        - <a href="https://tld.com" class="foo" data-bar="baz">Hallo</a> Welt!
        - Das deutsche <a href="https://1.com">Brot</a> <a href="https://2.com">vermisse</a> ich am meisten.
        - <a class="notranslate foo">Hallo</a> Welt!
        - Das ist ein Link https://tld.com im Text.
        - <span class="logo"></span> Hallo Welt.

        $origWithoutPrefixSuffix
        - <a href="https://tld.com" class="foo" data-bar="baz">Hallo</a> Welt!
        - Das deutsche <a href="https://1.com">Brot</a> <a href="https://2.com">vermisse</a> ich am meisten.
        - <a class="notranslate foo">Hallo</a> Welt!
        - Das ist ein Link https://tld.com im Text.
        - Hallo Welt.

        $origWithoutPrefixSuffixWithoutAttributes
        - <a>Hallo</a> Welt!
        - Das deutsche <a>Brot</a> <a>vermisse</a> ich am meisten.
        - <a class="notranslate">Hallo</a> Welt!
        - Das ist ein Link https://tld.com im Text.
        - Hallo Welt.

        $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks
        - <a>Hallo</a> Welt!
        - Das deutsche <a>Brot</a> <a>vermisse</a> ich am meisten.
        - <a class="notranslate">Hallo</a> Welt!
        - Das ist ein Link {1} im Text.
        - Hallo Welt.

        $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinksWithIds
        - <a p="1">Hallo</a> Welt!
        - Das deutsche <a p="1">Brot</a> <a p="2">vermisse</a> ich am meisten.
        - <a class="notranslate" p="1">Hallo</a> Welt!
        - Das ist ein Link {1} im Text.
        - Hallo Welt.

        $transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinksWithIds
        - <a p="1">Hello</a> world!
        - I <a p="2">miss</a> German <a p="1">bread</a> the most.
        - <a class="notranslate" p="1">Hallo</a> world!
        - This is a link {1} in the text
        - Hallo Welt.

        $transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks
        - <a>Hello</a> world!
        - I <a p="2">miss</a> German <a p="1">bread</a> the most.
        - <a class="notranslate">Hallo</a> world!
        - This is a link {1} in the text
        - Hello world.

        $transWithoutPrefixSuffixWithoutInlineLinks
        - <a href="https://tld.com" class="foo" data-bar="baz">Hello</a> world!
        - I <a href="https://2.com">miss</a> German <a href="https://1.com">bread</a> the most.
        - <a class="notranslate foo">Hallo</a> world!
        - This is a link {1} in the text
        - Hello world.

        $transWithoutPrefixSuffix
        - <a href="https://tld.com" class="foo" data-bar="baz">Hello</a> world!
        - I <a href="https://2.com">miss</a> German <a href="https://1.com">bread</a> the most.
        - <a class="notranslate foo">Hallo</a> world!
        - This is a link https://tld.com/en/ in the text
        - Hello world.

        $trans
        - <a href="https://tld.com" class="foo" data-bar="baz">Hello</a> world!
        - I <a href="https://2.com">miss</a> German <a href="https://1.com">bread</a> the most.
        - <a class="notranslate foo">Hallo</a> world!
        - This is a link https://tld.com/en/ in the text
        - <span class="logo"></span> Hello world.
        */

        [$origWithoutPrefixSuffix, $mappingTablePrefixSuffix] = $this->tags->removePrefixSuffix($orig);

        [$origWithoutPrefixSuffixWithoutAttributes, $mappingTableTags] = $this->tags->removeAttributes(
            $origWithoutPrefixSuffix
        );

        [
            $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks,
            $mappingTableInlineLinks
        ] = $this->tags->removeInlineLinks($origWithoutPrefixSuffixWithoutAttributes);

        $mappingTableInlineLinks = $this->translateInlineLinks($mappingTableInlineLinks);

        $transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks = $this->getExistingTranslationFromCache(
            $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks,
            $lng_source,
            $lng_target,
            $context
        );

        if ($transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks === false) {
            $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinksWithIds = $this->tags->addIds(
                $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks
            );
            $transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinksWithIds = $this->autoTranslateString(
                $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinksWithIds,
                $lng_source,
                $lng_target,
                $context
            );
            if ($transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinksWithIds !== null) {
                $transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks = $this->tags->removeAttributesExceptIrregularIds(
                    $transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinksWithIds
                );
                $this->addTranslationToDatabaseAndToCache(
                    $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks,
                    $transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks,
                    $lng_source,
                    $lng_target,
                    $context,
                    true
                );
            } else {
                $transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks = $this->tags->removeAttributesExceptIrregularIds(
                    $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinksWithIds
                );
            }
        }

        $transWithoutPrefixSuffixWithoutInlineLinks = $this->tags->addAttributesAndRemoveIds(
            $transWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks,
            $mappingTableTags
        );

        $transWithoutPrefixSuffix = $this->tags->addInlineLinks(
            $transWithoutPrefixSuffixWithoutInlineLinks,
            $mappingTableInlineLinks
        );

        $trans = $this->tags->addPrefixSuffix($transWithoutPrefixSuffix, $mappingTablePrefixSuffix);

        if (
            !$this->stringIsChecked(
                $origWithoutPrefixSuffixWithoutAttributesWithoutInlineLinks,
                $lng_source,
                $lng_target,
                $context
            )
        ) {
            return $orig;
        }

        return $trans;
    }

    function autoTranslateString($orig, $lng_source, $lng_target, $context = null)
    {
        if ($lng_source === null) {
            $lng_source = $this->settings->getSourceLanguageCode();
        }

        $trans = null;

        $service = $this->settings->get('auto_translation_service');

        if ($this->settings->get('auto_translation') === true) {
            // determine lng codes
            $lng_source_service = $this->settings->getApiLngCodeForService($service, $lng_source);
            $lng_target_service = $this->settings->getApiLngCodeForService($service, $lng_target);
            if ($lng_source_service === null || $lng_target_service === null) {
                return null;
            }
            // check for throttling
            if ($this->statsThrottlingIsActive($service)) {
                return null;
            }
            if ($service === 'google') {
                $api_key = $this->settings->get('google_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                if ($api_key == '') {
                    return null;
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
            }

            if ($service === 'microsoft') {
                $api_key = $this->settings->get('microsoft_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                if ($api_key == '') {
                    return null;
                }
                try {
                    $trans = __::translate_microsoft($orig, $lng_source_service, $lng_target_service, $api_key);
                } catch (\Throwable $t) {
                    $trans = null;
                }
                if ($trans === null || $trans === '') {
                    return null;
                }
            }

            if ($service === 'deepl') {
                $api_key = $this->settings->get('deepl_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                if ($api_key == '') {
                    return null;
                }
                try {
                    $trans = __::translate_deepl($orig, $lng_source_service, $lng_target_service, $api_key);
                } catch (\Throwable $t) {
                    $trans = null;
                }
                if ($trans === null || $trans === '') {
                    return null;
                }
            }

            // increase stats
            $this->statsIncreaseCharLengthForService($service, mb_strlen($orig));

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

    function removeLineBreaksAndPrepareString($orig)
    {
        $str = $orig;
        $str = __::trim_whitespace($str);
        $str = str_replace(['&#13;', "\r"], '', $str); // replace nasty carriage returns \r
        $str = preg_replace('/[\t]+/', ' ', $str); // replace multiple tab spaces with one tab space
        $parts = explode(PHP_EOL, $str);
        foreach ($parts as $parts__key => $parts__value) {
            $parts__value = __::trim_whitespace($parts__value);
            if ($parts__value == '') {
                unset($parts[$parts__key]);
            } else {
                $parts[$parts__key] = $parts__value;
            }
        }
        $str = implode(' ', $parts);
        return $str;
    }

    function reintroduceOuterLineBreaks($str, $orig_withoutlb, $orig_with_lb)
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
            if ($lng_target === $lng_source) {
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
        if (preg_match('/^[a-z](\)|\])$/', $str)) {
            return true;
        }
        // lng codes
        if ($context === 'slug') {
            $lngs = $this->settings->getSelectedLanguageCodes();
            if ($lngs !== null) {
                if (in_array(strtolower($str), $lngs)) {
                    return true;
                }
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
        // parse errors
        if (mb_stripos($str, 'parse error') !== false || mb_stripos($str, 'syntax error') !== false) {
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
        if ($context !== 'email' && $context !== 'slug' && $context !== 'file') {
            // don't ignore root relative links beginning with "/"
            if (strpos($str, ' ') === false && strpos($str, '/') === false) {
                if (strpos($str, '_') !== false) {
                    return true;
                }
                if (strpos($str, '--') !== false) {
                    return true;
                }
                if (strpos($str, '.') !== false) {
                    return true;
                }
                if (preg_match('/[0-9]+[A-Z]+/', $str)) {
                    return true;
                }
                if (preg_match('/[A-Z]+[0-9]+/', $str)) {
                    return true;
                }
            }
        }
        return false;
    }

    function autoDetermineContext($value, $suggestion = null)
    {
        $context = $suggestion;
        if ($context === null || $context == '') {
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $context = 'email';
            } elseif (mb_strpos($value, $this->host->getBaseUrlForSourceLanguage()) === 0) {
                // absolute internal links
                $context = 'slug|file';
            } elseif (
                // values beginning with external http
                mb_strpos($value, 'http') === 0 &&
                mb_strpos($value, ' ') === false
            ) {
                $context = 'slug';
            } elseif (preg_match('/^[a-z-\/]+(\.[a-z]{1,4})$/', $value)) {
                // foo.html
                $context = 'slug|file';
            } elseif (preg_match('/^\/[a-z-_\/\.]+$/', $value)) {
                // /foo/bar
                $context = 'slug|file';
            }
        }
        if ($context === 'slug|file') {
            $value_modified = $value;
            if (!preg_match('/^[a-zA-Z]+?:.+$/', $value_modified)) {
                $value_modified = $this->host->getBaseUrlForSourceLanguage() . '/' . $value_modified;
            }
            $value_modified = str_replace(['.php', '.html'], '', $value_modified);
            if (mb_strpos($value_modified, '?') !== false) {
                $value_modified = mb_substr($value_modified, 0, mb_strpos($value_modified, '?'));
            }
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
        if ($lng_target === $lng_source) {
            return true;
        }
        if (
            $str === '' ||
            $str === null ||
            !array_key_exists($lng_source, $this->data['checked_strings']) ||
            !array_key_exists($lng_target, $this->data['checked_strings'][$lng_source]) ||
            !array_key_exists($context ?? '', $this->data['checked_strings'][$lng_source][$lng_target]) ||
            !array_key_exists(
                html_entity_decode($str),
                $this->data['checked_strings'][$lng_source][$lng_target][$context ?? '']
            ) ||
            $this->data['checked_strings'][$lng_source][$lng_target][$context ?? ''][html_entity_decode($str)] != '1'
        ) {
            return false;
        }
        return true;
    }

    function getUrlTranslationInLanguage($from_lng, $to_lng, $url = null)
    {
        if ($url === null) {
            $url = $this->host->getCurrentUrlWithArgs();
        }
        $path = $this->host->getPathWithoutPrefixFromUrl($url);
        return trim(
            trim($this->host->getBaseUrlWithPrefixForLanguageCode($to_lng), '/') .
                '/' .
                trim($this->getPathTranslationInLanguage($from_lng, $to_lng, $path), '/'),
            '/'
        ) . (mb_strpos($path, '?') === false ? '/' : '');
    }

    function getTranslationInForeignLng($str, $to_lng, $from_lng = null, $context = null)
    {
        $data = [
            'trans' => false,
            'str_in_lng_source' => false,
            'checked_from' => true,
            'checked_to' => true
        ];
        if ($from_lng === $this->settings->getSourceLanguageCode()) {
            $data['str_in_lng_source'] = $str;
        } else {
            $data['str_in_lng_source'] = $this->getExistingTranslationReverseFromCache(
                $str,
                $this->settings->getSourceLanguageCode(),
                $from_lng,
                $context
            );
        }
        if ($data['str_in_lng_source'] === false) {
            return $data;
        }
        if (
            $to_lng === $this->settings->getSourceLanguageCode() ||
            $this->stringShouldNotBeTranslated($data['str_in_lng_source'], $context)
        ) {
            $data['trans'] = $data['str_in_lng_source'];
            return $data;
        }
        $data['checked_from'] = $this->stringIsChecked(
            $data['str_in_lng_source'],
            $this->settings->getSourceLanguageCode(),
            $from_lng,
            $context
        );
        $data['checked_to'] = $this->stringIsChecked(
            $data['str_in_lng_source'],
            $this->settings->getSourceLanguageCode(),
            $to_lng,
            $context
        );
        $data['trans'] = $this->getExistingTranslationFromCache(
            $data['str_in_lng_source'],
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
                $this->addTranslationToDatabaseAndToCache(
                    $str_in_source,
                    $str,
                    $this->settings->getSourceLanguageCode(),
                    $lng_source,
                    $context,
                    true
                );
            }
            $trans = $this->autoTranslateString(
                $str_in_source,
                $this->settings->getSourceLanguageCode(),
                $lng_target,
                $context
            );
            if ($trans !== null) {
                $this->addTranslationToDatabaseAndToCache(
                    $str_in_source,
                    $trans,
                    $this->settings->getSourceLanguageCode(),
                    $lng_target,
                    $context,
                    true
                );
            } else {
                $trans = $str;
            }
        }
        if ($data['checked_from'] === false || $data['checked_to'] === false) {
            return $str;
        }
        return $trans;
    }

    function getPathTranslationInLanguage($from_lng, $to_lng, $path = null)
    {
        if ($path == '') {
            return $path;
        }
        if ($from_lng === $to_lng) {
            return $path;
        }
        if ($this->host->slugTranslationIsDisabledForUrl($path)) {
            return $path;
        }
        $path_parts = explode('/', $path);
        foreach ($path_parts as $path_parts__key => $path_parts__value) {
            if ($path_parts[$path_parts__key] == '') {
                unset($path_parts[$path_parts__key]);
            }
        }
        $path_parts = array_values($path_parts);

        foreach ($path_parts as $path_parts__key => $path_parts__value) {
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
                    $trans = $data['str_in_lng_source'];
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

    function addCurrentUrlToTranslations($force = false)
    {
        if ($this->host->currentUrlIsStaticFile()) {
            return;
        }
        if (!$this->sourceLngIsCurrentLng()) {
            return;
        }
        if ($this->host->isAjaxRequest()) {
            return;
        }
        if ($this->host->slugTranslationIsDisabledForCurrentUrl()) {
            return;
        }
        /* on wp environments, this triggers also on 404s because it is too early called */
        /* we therefore stop here and trigger it later manually */
        if ($force === false && $this->utils->isWordPress()) {
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

    function statsGetTranslatedCharsByService($since = null)
    {
        $data = [];
        // sometimes the db does not yet exist
        try {
            $args = [];
            if ($since !== null) {
                $args[] = $since;
            }
            $data_raw = $this->db->fetch_all(
                'SELECT translated_by, SUM(' .
                    ($this->db->sql->engine === 'sqlite' ? 'LENGTH' : 'CHAR_LENGTH') .
                    '(str)) as length FROM ' .
                    $this->table .
                    ' WHERE translated_by IS NOT NULL' .
                    ($since !== null ? ' AND added > ?' : '') .
                    ' GROUP BY translated_by',
                $args
            );
            if (!empty($data_raw)) {
                foreach ($data_raw as $data_raw__value) {
                    $data[] = [
                        'service' => $data_raw__value['translated_by'],
                        'label' => $this->statsGetLabelForService($data_raw__value['translated_by']),
                        'length' => intval($data_raw__value['length']),
                        'costs' => $this->statsGetCosts($data_raw__value['translated_by'], $data_raw__value['length'])
                    ];
                }
            }
        } catch (\Exception $e) {
        }
        return $data;
    }

    function statsGetTranslatedCharsByServiceCompact($since = null)
    {
        $data = [];
        foreach ($this->statsGetServices() as $services__key => $services__value) {
            $data[$services__key] = 0;
        }
        $data_raw = $this->statsGetTranslatedCharsByService($since);
        foreach ($data_raw as $data_raw__value) {
            $data[$data_raw__value['service']] = $data_raw__value['length'];
        }
        return $data;
    }

    function statsGetCosts($service, $length)
    {
        if ($service === 'google') {
            return round($length * (20 / 1000000) * 0.92, 2);
        }
        if ($service === 'microsoft') {
            return round($length * (8.433 / 1000000), 2);
        }
        if ($service === 'deepl') {
            return round($length * (20 / 1000000), 2);
        }
        return 0;
    }

    function statsGetServices()
    {
        return [
            'google' => 'Google Translation API',
            'microsoft' => 'Microsoft Translation API',
            'deepl' => 'DeepL Translation API'
        ];
    }

    function statsGetLabelForService($service)
    {
        return $this->statsGetServices()[$service];
    }

    function statsLoadOnce()
    {
        if ($this->stats !== null) {
            return;
        }
        $this->stats = $this->statsGetTranslatedCharsByServiceCompact(date('Y-m-d H:i:s', strtotime('now - 30 days')));
    }

    function statsThrottlingIsActive($service)
    {
        $this->statsLoadOnce();
        $length = $this->settings->get($service . '_throttle_chars_per_month');
        if ($length == '') {
            return false;
        }
        return $this->stats[$service] > $length;
    }

    function statsIncreaseCharLengthForService($service, $length)
    {
        $this->statsLoadOnce();
        $this->stats[$service] += $length;
    }

    function statsReset()
    {
        $this->stats = null;
    }
}
