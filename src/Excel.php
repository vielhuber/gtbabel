<?php
namespace vielhuber\gtbabel;

use vielhuber\excelhelper\excelhelper;

class Excel
{
    function __construct(Data $data = null, Settings $settings = null)
    {
        $this->data = $data ?: new Data();
        $this->settings = $settings ?: new Settings();
    }

    function export($direct_output = true)
    {
        $files = [];
        $filename_prefix_tmp = tempnam(sys_get_temp_dir(), 'gtbabel_');
        $filename_zip = $filename_prefix_tmp . '.zip';

        $translations = $this->data->getGroupedTranslationsFromDatabase()['data'];
        foreach ($this->settings->getSelectedLanguageCodes() as $lng_source__value) {
            foreach ($this->settings->getSelectedLanguageCodes() as $lng_target__value) {
                if ($lng_source__value === $lng_target__value) {
                    continue;
                }
                $data = [];
                $data_line = [];
                $data_line[] = 'str';
                $data_line[] = 'trans';
                $data_line[] = 'context';
                $data[] = $data_line;
                foreach ($translations as $translations__value) {
                    if ($lng_source__value !== $translations__value['lng_source']) {
                        continue;
                    }
                    $data_line = [];
                    $data_line[] = $translations__value[$lng_source__value];
                    foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
                        if ($lng_target__value !== $languages__value) {
                            continue;
                        }
                        if (isset($translations__value[$languages__value])) {
                            $data_line[] = $translations__value[$languages__value];
                        } else {
                            $data_line[] = '';
                        }
                    }
                    $data_line[] = $translations__value['context'];
                    $data[] = $data_line;
                }
                if (count($data) <= 1) {
                    continue;
                }
                excelhelper::write([
                    'file' => $filename_prefix_tmp . '_' . $lng_source__value . '_' . $lng_target__value . '.xlsx',
                    'engine' => 'phpspreadsheet',
                    'output' => 'save',
                    'style_header' => false,
                    'autosize_columns' => false,
                    'auto_borders' => false,
                    'remove_empty_cols' => false,
                    'data' => $data
                ]);
                $files[] = [
                    $filename_prefix_tmp . '_' . $lng_source__value . '_' . $lng_target__value . '.xlsx',
                    $lng_source__value . '/' . $lng_target__value . '.xlsx'
                ];
            }
        }

        if ($direct_output === false) {
            return array_map(function ($files__value) {
                return $files__value[0];
            }, $files);
        }

        $zip = new \ZipArchive();
        $zip->open($filename_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($files as $files__value) {
            $zip->addFile($files__value[0], $files__value[1]);
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($filename_zip));
        header('Content-Disposition: attachment; filename="gtbabel-excel-' . date('Y-m-d-H-i-s') . '.zip"');
        readfile($filename_zip);
        @unlink($filename_zip);
        die();
    }

    function import($filename, $lng_source, $lng_target)
    {
        $translations = excelhelper::read([
            'file' => $filename,
            'first_line' => true,
            'format_cells' => false,
            'all_sheets' => false,
            'friendly_keys' => false
        ]);
        foreach ($translations as $translations__key => $translations__value) {
            if ($translations__key === 0) {
                continue;
            }
            if ($translations__value[0] == '') {
                continue;
            }
            $str = $translations__value[0];
            $trans = $translations__value[1];
            $context = $translations__value[2];
            $this->data->editTranslation($str, $context, $lng_source, $lng_target, $trans);
        }
    }
}
